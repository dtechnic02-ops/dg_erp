<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\Contra;
use App\Models\User;
use App\Services\AccountBalanceService;
use App\Services\ContraPostingService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class ContraPostingServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        foreach (['account_transactions', 'contras', 'accounts', 'users'] as $table) Schema::dropIfExists($table);
        Schema::create('users', function (Blueprint $table) {$table->id();$table->string('name');$table->string('email')->nullable();$table->string('password')->nullable();$table->unsignedBigInteger('company_id');$table->rememberToken();$table->timestamps();});
        Schema::create('accounts', function (Blueprint $table) {$table->id();$table->unsignedBigInteger('company_id');$table->string('account_name');$table->string('account_type');$table->decimal('opening_balance',18,2)->default(0);$table->decimal('current_balance',18,2)->default(0);$table->string('status')->default('active');$table->timestamps();});
        Schema::create('contras', function (Blueprint $table) {$table->id();$table->unsignedBigInteger('company_id');$table->unsignedBigInteger('financial_year_id');$table->string('contra_no');$table->date('contra_date');$table->unsignedBigInteger('from_account_id');$table->unsignedBigInteger('to_account_id');$table->decimal('amount',18,2);$table->string('transfer_type')->nullable();$table->string('reference_no')->nullable();$table->text('note')->nullable();$table->string('attachment')->nullable();$table->unsignedBigInteger('created_by');$table->tinyInteger('status')->default(1);$table->timestamps();});
        Schema::create('account_transactions', function (Blueprint $table) {$table->id();$table->unsignedBigInteger('company_id');$table->unsignedBigInteger('financial_year_id');$table->unsignedBigInteger('account_id');$table->date('transaction_date');$table->string('voucher_no')->nullable();$table->string('reference_type')->nullable();$table->unsignedBigInteger('reference_id')->nullable();$table->unsignedBigInteger('journal_item_id')->nullable();$table->unsignedBigInteger('reversed_transaction_id')->nullable();$table->text('description')->nullable();$table->decimal('debit',18,2)->default(0);$table->decimal('credit',18,2)->default(0);$table->decimal('balance',18,2)->default(0);$table->unsignedBigInteger('created_by')->nullable();$table->tinyInteger('status')->default(1);$table->timestamps();$table->unique('reversed_transaction_id');});

        User::forceCreate(['id'=>1,'name'=>'Admin','email'=>'admin@example.test','password'=>'password','company_id'=>1]);
        Account::forceCreate(['id'=>1,'company_id'=>1,'account_name'=>'Hand Cash','account_type'=>'Cash','status'=>'active']);
        Account::forceCreate(['id'=>3,'company_id'=>1,'account_name'=>'Global Bank','account_type'=>'Bank','status'=>'active']);
        Account::forceCreate(['id'=>4,'company_id'=>2,'account_name'=>'Foreign Cash','account_type'=>'Cash','status'=>'active']);
        $this->actingAs(User::findOrFail(1));
        AccountBalanceService::createTransaction(['company_id'=>1,'financial_year_id'=>10,'account_id'=>3,'transaction_date'=>'2026-01-01','voucher_no'=>'OB-1','reference_type'=>'opening_balance','reference_id'=>1,'description'=>'Opening','debit'=>100000,'credit'=>0,'created_by'=>1],false);
    }

    public function test_bank_to_cash_posts_two_transactions_and_purchase_payment_uses_recalculated_cash_balance(): void
    {
        $contra=$this->contra();
        $posted=$this->service()->post($contra,1,1);

        $from=AccountTransaction::where('reference_type','contra')->where('reference_id',$contra->id)->where('account_id',3)->firstOrFail();
        $to=AccountTransaction::where('reference_type','contra')->where('reference_id',$contra->id)->where('account_id',1)->firstOrFail();
        $this->assertEquals(0,$from->debit);$this->assertEquals(10000,$from->credit);
        $this->assertEquals(10000,$to->debit);$this->assertEquals(0,$to->credit);
        $this->assertSame(10,$from->financial_year_id);$this->assertSame('2026-02-01',$from->transaction_date);
        $this->assertSame('CON-1-2026-0001',$from->voucher_no);$this->assertSame('contra',$from->reference_type);$this->assertSame($contra->id,$from->reference_id);
        $this->assertSame('bank_to_cash',$posted->transfer_type);
        $this->assertEquals(90000,Account::findOrFail(3)->current_balance);
        $this->assertEquals(10000,Account::findOrFail(1)->current_balance);

        AccountBalanceService::createTransaction(['company_id'=>1,'financial_year_id'=>10,'account_id'=>1,'transaction_date'=>'2026-02-02','voucher_no'=>'PP-1','reference_type'=>'purchase_payment','reference_id'=>1,'description'=>'Purchase Payment','debit'=>0,'credit'=>700,'created_by'=>1]);
        $this->assertEquals(9300,Account::findOrFail(1)->current_balance);
    }

    public function test_duplicate_posting_is_blocked_without_creating_extra_ledger_rows(): void
    {
        $contra=$this->contra();$this->service()->post($contra,1,1);
        try {$this->service()->post($contra,1,1);$this->fail('Duplicate Contra posting was accepted.');} catch (RuntimeException $exception) {$this->assertSame('This Contra has already been posted to the account ledger.',$exception->getMessage());}
        $this->assertSame(2,AccountTransaction::where('reference_type','contra')->where('reference_id',$contra->id)->count());
        $this->assertEquals(90000,Account::findOrFail(3)->current_balance);$this->assertEquals(10000,Account::findOrFail(1)->current_balance);
    }

    public function test_cross_company_same_account_and_unsupported_account_are_rejected(): void
    {
        foreach ([[3,4],[3,3]] as [$from,$to]) {
            $contra=$this->contra($from,$to);
            try {$this->service()->post($contra,1,1);$this->fail('Invalid Contra accounts were accepted.');} catch (RuntimeException) {$this->assertSame(0,AccountTransaction::where('reference_type','contra')->count());}
            $contra->delete();
        }
        Account::forceCreate(['id'=>5,'company_id'=>1,'account_name'=>'Other','account_type'=>'Other','status'=>'active']);
        $contra=$this->contra(3,5);
        $this->expectException(RuntimeException::class);$this->service()->post($contra,1,1);
    }

    public function test_contra_cancellation_creates_opposite_history_and_restores_both_balances(): void
    {
        $contra=$this->service()->post($this->contra(),1,1);
        $cancelled=$this->service()->reverse($contra,1,1);

        $this->assertSame(0,(int)$cancelled->status);
        $this->assertSame(2,AccountTransaction::where('reference_type','contra')->where('reference_id',$contra->id)->count());
        $this->assertSame(2,AccountTransaction::where('reference_type','contra_cancel')->where('reference_id',$contra->id)->count());
        $this->assertEquals(100000,Account::findOrFail(3)->current_balance);
        $this->assertEquals(0,Account::findOrFail(1)->current_balance);
        $this->expectException(RuntimeException::class);$this->service()->reverse($cancelled,1,1);
    }

    public function test_contra_edit_reverses_original_posting_and_reposts_without_overwriting_history(): void
    {
        $contra=$this->service()->post($this->contra(),1,1);
        $originals=AccountTransaction::where('reference_type','contra')->where('reference_id',$contra->id)->orderBy('id')->get();
        $originalIds=$originals->pluck('id')->all();

        $updated=$this->service()->updatePosting($contra,1,1,[
            'contra_date'=>'2026-02-03',
            'from_account_id'=>3,'to_account_id'=>1,'amount'=>5000,'reference_no'=>'UPDATED','note'=>'Updated','attachment'=>null,
        ]);

        $this->assertSame('bank_to_cash',$updated->transfer_type);
        $this->assertEquals(95000,Account::findOrFail(3)->current_balance);
        $this->assertEquals(5000,Account::findOrFail(1)->current_balance);
        $this->assertSame(4,AccountTransaction::where('reference_type','contra')->where('reference_id',$contra->id)->count());
        $this->assertSame(2,AccountTransaction::where('reference_type','contra_update_reversal')->where('reference_id',$contra->id)->count());

        $originalFrom=AccountTransaction::findOrFail($originalIds[0]);
        $originalTo=AccountTransaction::findOrFail($originalIds[1]);
        $this->assertEquals(10000,$originalFrom->credit);
        $this->assertEquals(0,$originalFrom->debit);
        $this->assertEquals(10000,$originalTo->debit);
        $this->assertEquals(0,$originalTo->credit);
        $this->assertSame('2026-02-01',$originalFrom->transaction_date);
        $this->assertSame('2026-02-01',$originalTo->transaction_date);

        $updateReversals=AccountTransaction::where('reference_type','contra_update_reversal')->where('reference_id',$contra->id)->get();
        $this->assertEqualsCanonicalizing($originalIds,$updateReversals->pluck('reversed_transaction_id')->all());
        $latest=AccountTransaction::where('reference_type','contra')->where('reference_id',$contra->id)->whereNotIn('id',$originalIds)->orderBy('id')->get();
        $this->assertSame(2,$latest->count());
        $this->assertEquals(5000,$latest->firstWhere('account_id',3)->credit);
        $this->assertEquals(5000,$latest->firstWhere('account_id',1)->debit);
        $this->assertSame('2026-02-03',$latest->first()->transaction_date);

        $cancelled=$this->service()->reverse($updated,1,1);
        $this->assertSame(0,(int)$cancelled->status);
        $cancelReversals=AccountTransaction::where('reference_type','contra_cancel')->where('reference_id',$contra->id)->get();
        $this->assertSame(2,$cancelReversals->count());
        $this->assertEqualsCanonicalizing($latest->pluck('id')->all(),$cancelReversals->pluck('reversed_transaction_id')->all());
        $this->assertEquals(100000,Account::findOrFail(3)->current_balance);
        $this->assertEquals(0,Account::findOrFail(1)->current_balance);
        $this->assertSame(9,AccountTransaction::count());

        $this->expectException(RuntimeException::class);
        $this->service()->reverse($cancelled,1,1);
    }

    private function contra(int $from=3,int $to=1): Contra
    {
        return Contra::create(['company_id'=>1,'financial_year_id'=>10,'contra_no'=>'CON-1-2026-0001','contra_date'=>'2026-02-01','from_account_id'=>$from,'to_account_id'=>$to,'amount'=>10000,'transfer_type'=>'bank_to_bank','created_by'=>1,'status'=>1]);
    }

    private function service(): ContraPostingService { return app(ContraPostingService::class); }
}
