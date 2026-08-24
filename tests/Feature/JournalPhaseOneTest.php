<?php

namespace Tests\Feature;

use App\Models\ChartAccount;
use App\Models\Journal;
use App\Services\JournalService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

class JournalPhaseOneTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        foreach (['journal_audit_events','journal_items','journal_number_sequences','journals','accounting_period_locks','chart_accounts','accounts','financial_years','users','companies'] as $table) Schema::dropIfExists($table);
        Schema::create('companies', fn (Blueprint $t) => [$t->id(), $t->string('company_name'), $t->timestamps()]);
        Schema::create('users', fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('company_id'), $t->string('name'), $t->timestamps()]);
        Schema::create('financial_years', fn (Blueprint $t) => [$t->id(),$t->unsignedBigInteger('company_id'),$t->string('name'),$t->date('start_date'),$t->date('end_date'),$t->boolean('is_active'),$t->boolean('is_closed')->default(false),$t->boolean('is_locked')->default(false),$t->timestamps()]);
        Schema::create('chart_accounts', fn (Blueprint $t) => [$t->id(),$t->unsignedBigInteger('company_id'),$t->string('code'),$t->string('name'),$t->unsignedTinyInteger('level'),$t->boolean('allow_manual_entry'),$t->string('status'),$t->boolean('is_locked')->default(false),$t->timestamps(),$t->softDeletes()]);
        Schema::create('accounts', fn (Blueprint $t) => [$t->id(),$t->unsignedBigInteger('company_id'),$t->string('account_name'),$t->integer('status'),$t->timestamps()]);
        Schema::create('accounting_period_locks', fn (Blueprint $t) => [$t->id(),$t->unsignedBigInteger('company_id'),$t->unsignedBigInteger('financial_year_id'),$t->date('date_from'),$t->date('date_to'),$t->boolean('is_locked'),$t->timestamps()]);
        Schema::create('journals', function (Blueprint $t) {$t->id();$t->unsignedBigInteger('company_id');$t->unsignedBigInteger('financial_year_id');$t->string('journal_no');$t->date('journal_date');$t->string('journal_type');$t->string('reference_no')->nullable();$t->text('description');$t->text('remarks')->nullable();$t->text('note')->nullable();$t->string('source_module')->nullable();$t->string('source_type')->nullable();$t->unsignedBigInteger('source_id')->nullable();$t->string('source_key')->nullable();$t->uuid('request_key');$t->decimal('total_amount',20,4);$t->unsignedBigInteger('created_by');$t->unsignedBigInteger('updated_by')->nullable();foreach(['submitted_by','approved_by','rejected_by','posted_by','cancelled_by','reversed_by','locked_by','unlocked_by'] as $c)$t->unsignedBigInteger($c)->nullable();foreach(['submitted_at','approved_at','rejected_at','posted_at','cancelled_at','reversed_at','locked_at','unlocked_at'] as $c)$t->timestamp($c)->nullable();foreach(['rejection_reason','cancellation_reason','cancel_reason','reversal_reason','lock_reason','unlock_reason'] as $c)$t->text($c)->nullable();$t->date('cancelled_date')->nullable();$t->unsignedBigInteger('reversal_of_journal_id')->nullable();$t->boolean('is_locked')->default(false);$t->string('status');$t->timestamps();$t->unique(['company_id','financial_year_id','journal_no']);$t->unique(['company_id','request_key']);});
        Schema::create('journal_items', function (Blueprint $t) {$t->id();$t->unsignedBigInteger('company_id');$t->unsignedBigInteger('journal_id');$t->unsignedBigInteger('chart_account_id');$t->unsignedBigInteger('account_id')->nullable();$t->string('type');$t->decimal('amount',20,4);$t->decimal('debit',20,4);$t->decimal('credit',20,4);$t->text('description')->nullable();$t->string('reference')->nullable();$t->unsignedInteger('line_number');$t->string('sub_ledger_type')->nullable();$t->unsignedBigInteger('sub_ledger_id')->nullable();$t->text('note')->nullable();$t->integer('status');$t->timestamps();});
        Schema::create('journal_number_sequences', fn (Blueprint $t) => [$t->unsignedBigInteger('company_id'),$t->unsignedBigInteger('financial_year_id'),$t->unsignedBigInteger('next_number'),$t->timestamps(),$t->primary(['company_id','financial_year_id'])]);
        Schema::create('journal_audit_events', function (Blueprint $t) {$t->id();$t->unsignedBigInteger('company_id');$t->unsignedBigInteger('financial_year_id');$t->unsignedBigInteger('journal_id');$t->string('event');$t->string('previous_status')->nullable();$t->string('new_status')->nullable();$t->unsignedBigInteger('actor_id');$t->timestamp('event_at');$t->text('reason')->nullable();$t->json('metadata')->nullable();});
        DB::table('companies')->insert([['id'=>1,'company_name'=>'A'],['id'=>2,'company_name'=>'B']]);
        DB::table('users')->insert(['id'=>1,'company_id'=>1,'name'=>'Maker']);
        DB::table('financial_years')->insert(['id'=>1,'company_id'=>1,'name'=>'FY','start_date'=>'2026-01-01','end_date'=>'2026-12-31','is_active'=>1,'is_closed'=>0,'is_locked'=>0]);
        foreach ([[1,1,'1000','Cash',3,1,'active',0],[2,1,'2000','Capital',3,1,'active',0],[3,2,'3000','Foreign',3,1,'active',0],[4,1,'4000','Control',2,1,'active',0],[5,1,'5000','Inactive',3,1,'inactive',0],[6,1,'6000','Locked',3,1,'active',1]] as $a) DB::table('chart_accounts')->insert(array_combine(['id','company_id','code','name','level','allow_manual_entry','status','is_locked'],$a));
    }

    public function test_draft_creation_is_four_decimal_balanced_numbered_and_audited(): void
    {
        $journal=$this->service()->createDraft($this->payload(),1,1);
        $this->assertSame(Journal::STATUS_DRAFT,$journal->status);$this->assertSame('100.1234',$journal->total_amount);$this->assertSame(2,$journal->items->count());$this->assertSame('created',$journal->auditEvents->first()->event);$this->assertMatchesRegularExpression('/00000001$/',$journal->journal_no);
        $second=$this->service()->createDraft($this->payload(['request_key'=>(string)Str::uuid()]),1,1);$this->assertMatchesRegularExpression('/00000002$/',$second->journal_no);$this->assertNotSame($journal->journal_no,$second->journal_no);
    }

    public function test_draft_edit_and_posted_edit_rejection(): void
    {
        $journal=$this->service()->createDraft($this->payload(),1,1);$updated=$this->service()->updateDraft($journal,$this->payload(['description'=>'Updated','request_key'=>null]),1);$this->assertSame('Updated',$updated->description);$this->assertSame(2,$updated->auditEvents()->count());
        $updated->update(['status'=>Journal::STATUS_POSTED]);$this->expectException(RuntimeException::class);$this->service()->updateDraft($updated,$this->payload(['request_key'=>null]),1);
    }

    public function test_line_shape_and_balance_validation(): void
    {
        foreach ([
            [$this->payload(['lines'=>array_slice($this->lines(),0,1)])],
            [$this->payload(['lines'=>[['chart_account_id'=>1,'debit'=>'1.0000','credit'=>'1.0000'],['chart_account_id'=>2,'debit'=>'0.0000','credit'=>'1.0000']]])],
            [$this->payload(['lines'=>[['chart_account_id'=>1,'debit'=>'0.0000','credit'=>'0.0000'],['chart_account_id'=>2,'debit'=>'0.0000','credit'=>'0.0000']]])],
            [$this->payload(['lines'=>[['chart_account_id'=>1,'debit'=>'1.0001','credit'=>'0.0000'],['chart_account_id'=>2,'debit'=>'0.0000','credit'=>'1.0000']]])],
        ] as [$payload]) {try{$this->service()->createDraft($payload,1,1);$this->fail('Invalid lines accepted.');}catch(ValidationException){$this->assertSame(0,Journal::count());}}
    }

    public function test_chart_account_company_level_status_and_lock_are_enforced(): void
    {
        foreach([3,4,5,6] as $id){$payload=$this->payload(['request_key'=>(string)Str::uuid(),'lines'=>[['chart_account_id'=>$id,'debit'=>'100.0000','credit'=>'0.0000'],['chart_account_id'=>2,'debit'=>'0.0000','credit'=>'100.0000']]]);try{$this->service()->createDraft($payload,1,1);$this->fail('Invalid Chart Account accepted.');}catch(ValidationException){$this->assertTrue(true);}}
    }

    public function test_financial_year_date_period_lock_and_idempotency_are_enforced(): void
    {
        $request=$this->payload();$this->service()->createDraft($request,1,1);try{$this->service()->createDraft($request,1,1);$this->fail('Duplicate request accepted.');}catch(ValidationException){$this->assertSame(1,Journal::count());}
        DB::table('financial_years')->where('id',1)->update(['is_active'=>0]);$this->reject($this->payload(['request_key'=>(string)Str::uuid()]));DB::table('financial_years')->where('id',1)->update(['is_active'=>1,'is_closed'=>1]);$this->reject($this->payload(['request_key'=>(string)Str::uuid()]));DB::table('financial_years')->where('id',1)->update(['is_closed'=>0,'is_locked'=>1]);$this->reject($this->payload(['request_key'=>(string)Str::uuid()]));DB::table('financial_years')->where('id',1)->update(['is_locked'=>0]);$this->reject($this->payload(['request_key'=>(string)Str::uuid(),'journal_date'=>'2027-01-01']));DB::table('accounting_period_locks')->insert(['company_id'=>1,'financial_year_id'=>1,'date_from'=>'2026-06-01','date_to'=>'2026-06-30','is_locked'=>1]);$this->reject($this->payload(['request_key'=>(string)Str::uuid()]));
    }

    public function test_status_transition_rules_are_centralized(): void
    {
        $this->service()->assertTransition(Journal::STATUS_DRAFT,Journal::STATUS_SUBMITTED);$this->expectException(RuntimeException::class);$this->service()->assertTransition(Journal::STATUS_POSTED,Journal::STATUS_DRAFT);
    }

    public function test_phase_two_submit_approve_reject_cancel_and_lock_workflow(): void
    {
        DB::table('users')->insert([['id'=>2,'company_id'=>1,'name'=>'Checker'],['id'=>3,'company_id'=>1,'name'=>'Poster']]);
        $service=$this->service();
        $journal=$service->createDraft($this->payload(),1,1);
        $submitted=$service->submit($journal,1);
        $this->assertSame(Journal::STATUS_SUBMITTED,$submitted->status);
        $this->expectException(RuntimeException::class);
        $service->approve($submitted,1);
    }

    public function test_phase_two_authorized_approval_rejection_cancel_and_lock_audit(): void
    {
        DB::table('users')->insert(['id'=>2,'company_id'=>1,'name'=>'Checker']);
        $service=$this->service();
        $first=$service->submit($service->createDraft($this->payload(),1,1),1);
        $approved=$service->approve($first,2);
        $this->assertSame(Journal::STATUS_APPROVED,$approved->status);

        $second=$service->submit($service->createDraft($this->payload(['request_key'=>(string)Str::uuid()]),1,1),1);
        $rejected=$service->reject($second,2,'Correction required');
        $this->assertSame(Journal::STATUS_DRAFT,$rejected->status);
        $this->assertSame('Correction required',$rejected->rejection_reason);
        $service->setLock($rejected,2,true,'Review hold');
        $service->setLock($rejected->fresh(),2,false,'Review complete');
        $cancelled=$service->cancel($rejected->fresh(),2,'Not required');
        $this->assertSame(Journal::STATUS_CANCELLED,$cancelled->status);
        $this->assertSame(['created','submitted','rejected','locked','unlocked','cancelled'],$cancelled->auditEvents()->pluck('event')->all());
    }

    public function test_source_generated_opening_balance_journal_cannot_be_mutated(): void
    {
        $journal=$this->service()->createDraft($this->payload(),1,1);
        $journal->update(['source_module'=>'opening_balance']);
        $this->expectException(RuntimeException::class);
        $this->service()->submit($journal->fresh(),1);
    }

    private function reject(array $payload): void {try{$this->service()->createDraft($payload,1,1);$this->fail('Invalid financial context accepted.');}catch(ValidationException){$this->assertTrue(true);}}
    private function service(): JournalService{return app(JournalService::class);}
    private function lines(): array{return [['chart_account_id'=>1,'debit'=>'100.1234','credit'=>'0.0000','description'=>'Debit'],['chart_account_id'=>2,'debit'=>'0.0000','credit'=>'100.1234','description'=>'Credit']];}
    private function payload(array $overrides=[]): array{return array_replace(['financial_year_id'=>1,'journal_date'=>'2026-06-15','journal_type'=>Journal::TYPE_GENERAL,'reference_no'=>'REF','description'=>'Draft','remarks'=>'Phase 1','request_key'=>(string)Str::uuid(),'lines'=>$this->lines()],$overrides);}
}
