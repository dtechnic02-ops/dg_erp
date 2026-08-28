<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\AccountingEntry;
use App\Models\ChartAccount;
use App\Models\Company;
use App\Models\Customer;
use App\Models\FinancialYear;
use App\Models\OpeningBalance;
use App\Models\Supplier;
use App\Models\User;
use App\Services\OpeningBalanceService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

class OpeningBalanceModuleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        foreach (['opening_balance_audit_events','opening_balance_lines','opening_balances','accounting_period_locks','accounting_entry_lines','accounting_entries','account_transactions','customer_transactions','supplier_transactions','journal_items','journals','chart_accounts','accounts','customers','suppliers','permission_role','user_permissions','permissions','financial_years','users','roles','companies'] as $table) Schema::dropIfExists($table);
        Schema::create('companies', fn (Blueprint $t) => [$t->id(),$t->string('company_name'),$t->string('status')->default('active'),$t->timestamps()]);
        Schema::create('roles', fn (Blueprint $t) => [$t->id(),$t->string('name'),$t->timestamps()]);
        Schema::create('users', fn (Blueprint $t) => [$t->id(),$t->string('name'),$t->string('email')->nullable(),$t->string('password')->nullable(),$t->unsignedBigInteger('company_id'),$t->unsignedBigInteger('role_id')->nullable(),$t->rememberToken(),$t->timestamps()]);
        Schema::create('permissions', fn (Blueprint $t) => [$t->id(),$t->string('name'),$t->string('scope')->default('company'),$t->timestamps()]);
        Schema::create('permission_role', fn (Blueprint $t) => [$t->id(),$t->unsignedBigInteger('permission_id'),$t->unsignedBigInteger('role_id'),$t->timestamps()]);
        Schema::create('user_permissions', fn (Blueprint $t) => [$t->id(),$t->unsignedBigInteger('user_id'),$t->unsignedBigInteger('permission_id'),$t->boolean('is_allowed')->default(true),$t->timestamps()]);
        Schema::create('financial_years', fn (Blueprint $t) => [$t->id(),$t->unsignedBigInteger('company_id'),$t->string('name'),$t->date('start_date'),$t->date('end_date'),$t->boolean('is_active'),$t->unsignedBigInteger('created_by')->nullable(),$t->timestamps()]);
        Schema::create('accounts', fn (Blueprint $t) => [$t->id(),$t->unsignedBigInteger('company_id'),$t->string('account_name'),$t->string('account_type')->default('Bank'),$t->decimal('opening_balance',20,4)->default(0),$t->decimal('current_balance',20,4)->default(0),$t->string('status')->default('active'),$t->timestamps()]);
        foreach (['customers','suppliers'] as $table) Schema::create($table, fn (Blueprint $t) => [$t->id(),$t->unsignedBigInteger('company_id'),$t->string('name'),$t->decimal('opening_balance',20,4)->default(0),$t->decimal('current_balance',20,4)->default(0),$t->string('status')->default('active'),$t->timestamps()]);
        Schema::create('chart_accounts', function (Blueprint $t) {$t->id();$t->unsignedBigInteger('company_id');$t->string('code');$t->string('name');$t->string('account_class');$t->string('account_category')->nullable();$t->string('normal_balance');$t->string('system_code')->nullable();$t->unsignedTinyInteger('level')->default(3);$t->boolean('is_system')->default(false);$t->boolean('is_control')->default(false);$t->boolean('allow_manual_entry')->default(true);$t->string('status')->default('active');$t->timestamps();$t->softDeletes();});
        Schema::create('journals', function (Blueprint $t) {$t->id();$t->unsignedBigInteger('company_id');$t->unsignedBigInteger('financial_year_id');$t->string('journal_no');$t->date('journal_date');$t->string('reference_no')->nullable();$t->string('source_module')->nullable();$t->string('source_type')->nullable();$t->unsignedBigInteger('source_id')->nullable();$t->string('source_key')->nullable();$t->decimal('total_amount',20,4);$t->text('note')->nullable();$t->unsignedBigInteger('created_by');$t->unsignedBigInteger('updated_by')->nullable();$t->unsignedBigInteger('posted_by')->nullable();$t->timestamp('posted_at')->nullable();$t->unsignedBigInteger('cancelled_by')->nullable();$t->date('cancelled_date')->nullable();$t->text('cancel_reason')->nullable();$t->unsignedBigInteger('reversed_by')->nullable();$t->timestamp('reversed_at')->nullable();$t->unsignedBigInteger('reversal_of_journal_id')->nullable();$t->integer('status');$t->timestamps();$t->unique(['company_id','source_key']);});
        Schema::create('journal_items', function (Blueprint $t) {$t->id();$t->unsignedBigInteger('company_id');$t->unsignedBigInteger('journal_id');$t->unsignedBigInteger('account_id')->nullable();$t->unsignedBigInteger('chart_account_id')->nullable();$t->string('sub_ledger_type')->nullable();$t->unsignedBigInteger('sub_ledger_id')->nullable();$t->string('type');$t->decimal('amount',20,4);$t->text('note')->nullable();$t->integer('status');$t->timestamps();});
        Schema::create('accounting_entries', function (Blueprint $t) {$t->id();$t->unsignedBigInteger('company_id');$t->unsignedBigInteger('financial_year_id');$t->string('entry_number');$t->date('entry_date');$t->string('reference_number')->nullable();$t->string('source_module');$t->string('source_type')->nullable();$t->unsignedBigInteger('source_id')->nullable();$t->string('source_event')->nullable();$t->string('source_key')->nullable();$t->text('description')->nullable();$t->string('status');$t->unsignedBigInteger('reversal_of_id')->nullable();$t->timestamp('posted_at')->nullable();$t->unsignedBigInteger('posted_by')->nullable();$t->unsignedBigInteger('created_by')->nullable();$t->unsignedBigInteger('updated_by')->nullable();$t->timestamps();$t->unique(['company_id','source_key']);});
        Schema::create('accounting_entry_lines', function (Blueprint $t) {$t->id();$t->unsignedBigInteger('accounting_entry_id');$t->unsignedBigInteger('chart_account_id');$t->unsignedBigInteger('operational_account_id')->nullable();$t->unsignedInteger('line_number');$t->text('description')->nullable();$t->decimal('debit',20,4);$t->decimal('credit',20,4);$t->string('subledger_type')->nullable();$t->unsignedBigInteger('subledger_id')->nullable();$t->timestamps();});
        foreach (['customer_transactions'=>'customer_id','supplier_transactions'=>'supplier_id'] as $table=>$party) Schema::create($table, function (Blueprint $t) use ($party) {$t->id();$t->unsignedBigInteger('company_id');$t->unsignedBigInteger('financial_year_id');$t->unsignedBigInteger($party);$t->date('transaction_date');$t->string('voucher_no');$t->string('reference_type');$t->unsignedBigInteger('reference_id');$t->unsignedBigInteger('journal_item_id')->nullable();$t->unsignedBigInteger('reversed_transaction_id')->nullable();$t->string('reference_no')->nullable();$t->text('description')->nullable();$t->decimal('debit',20,4);$t->decimal('credit',20,4);$t->decimal('balance',20,4)->default(0);$t->text('remarks')->nullable();$t->unsignedBigInteger('created_by')->nullable();$t->integer('status');$t->timestamps();});
        Schema::create('account_transactions', function (Blueprint $t) {$t->id();$t->unsignedBigInteger('company_id');$t->unsignedBigInteger('financial_year_id');$t->unsignedBigInteger('account_id');$t->date('transaction_date');$t->string('voucher_no');$t->string('reference_type');$t->unsignedBigInteger('reference_id');$t->unsignedBigInteger('journal_item_id')->nullable();$t->unsignedBigInteger('reversed_transaction_id')->nullable();$t->text('description')->nullable();$t->decimal('debit',20,4);$t->decimal('credit',20,4);$t->decimal('balance',20,4)->default(0);$t->unsignedBigInteger('created_by')->nullable();$t->integer('status');$t->timestamps();});
        Schema::create('opening_balances', function (Blueprint $t) {$t->id();$t->unsignedBigInteger('company_id');$t->unsignedBigInteger('financial_year_id');$t->date('business_date');$t->string('type');$t->string('reference_number');$t->text('remarks')->nullable();$t->string('status');$t->string('active_key')->nullable()->default('active');$t->uuid('request_key');$t->unsignedBigInteger('journal_id')->nullable();$t->unsignedBigInteger('accounting_entry_id')->nullable();foreach(['created_by','submitted_by','approved_by','posted_by','cancelled_by','reversed_by','locked_by'] as $c)$t->unsignedBigInteger($c)->nullable();foreach(['submitted_at','approved_at','posted_at','cancelled_at','reversed_at','locked_at'] as $c)$t->timestamp($c)->nullable();$t->text('cancellation_reason')->nullable();$t->text('reversal_reason')->nullable();$t->text('lock_reason')->nullable();$t->boolean('is_locked')->default(false);$t->timestamps();$t->unique(['company_id','request_key']);$t->unique(['company_id','financial_year_id','active_key']);});
        Schema::create('opening_balance_lines', function (Blueprint $t) {$t->id();$t->unsignedBigInteger('opening_balance_id');$t->unsignedBigInteger('chart_account_id');$t->unsignedBigInteger('operational_account_id')->nullable();$t->unsignedInteger('line_number');foreach(['debit','credit','base_debit','base_credit'] as $c)$t->decimal($c,20,4)->default(0);$t->string('subledger_type')->nullable();$t->unsignedBigInteger('subledger_id')->nullable();$t->text('description')->nullable();$t->string('line_reference')->nullable();$t->string('currency')->nullable();$t->decimal('exchange_rate',20,8)->nullable();$t->timestamps();});
        Schema::create('opening_balance_audit_events', function (Blueprint $t) {$t->id();$t->unsignedBigInteger('company_id');$t->unsignedBigInteger('financial_year_id');$t->unsignedBigInteger('opening_balance_id');$t->string('event');$t->string('previous_status')->nullable();$t->string('new_status')->nullable();$t->unsignedBigInteger('user_id');$t->text('reason')->nullable();$t->json('metadata')->nullable();$t->timestamp('occurred_at');$t->timestamps();});
        Schema::create('accounting_period_locks', function (Blueprint $t) {$t->id();$t->unsignedBigInteger('company_id');$t->unsignedBigInteger('financial_year_id');$t->date('date_from');$t->date('date_to');$t->boolean('is_locked')->default(true);$t->text('reason');$t->unsignedBigInteger('locked_by');$t->timestamps();});
        $this->seedContext();
    }

    public function test_balanced_asset_liability_equity_posting_creates_journal_entry_and_audit(): void
    {
        $opening=$this->approved($this->basicLines());$opening=$this->service()->post($opening,1,2);
        $this->assertSame('posted',$opening->status);$this->assertNotNull($opening->journal_id);$this->assertNotNull($opening->accounting_entry_id);
        $entry=AccountingEntry::with('lines')->findOrFail($opening->accounting_entry_id);$this->assertSame(1,$entry->financial_year_id);$this->assertSame('100.1234',number_format((float)$entry->lines->sum('debit'),4,'.',''));$this->assertSame('100.1234',number_format((float)$entry->lines->sum('credit'),4,'.',''));
        $this->assertDatabaseHas('opening_balance_audit_events',['opening_balance_id'=>$opening->id,'event'=>'posted']);
    }

    public function test_unbalanced_zero_negative_and_both_sided_lines_are_rejected(): void
    {
        foreach ([[['chart_account_id'=>1,'debit'=>'1','credit'=>'0'],['chart_account_id'=>3,'debit'=>'0','credit'=>'2']],[['chart_account_id'=>1,'debit'=>'0','credit'=>'0'],['chart_account_id'=>3,'debit'=>'0','credit'=>'0']],[['chart_account_id'=>1,'debit'=>'-1','credit'=>'0'],['chart_account_id'=>3,'debit'=>'0','credit'=>'1']],[['chart_account_id'=>1,'debit'=>'1','credit'=>'1'],['chart_account_id'=>3,'debit'=>'0','credit'=>'1']]] as $lines) {
            try {$this->service()->create(1,1,$this->payload($lines));$this->fail('Invalid lines accepted.');} catch (ValidationException) {$this->assertSame(0,OpeningBalance::count());}
        }
    }

    public function test_level_inactive_foreign_and_income_accounts_are_rejected(): void
    {
        foreach ([4,5,6,7] as $id) {try {$this->service()->create(1,1,$this->payload([['chart_account_id'=>$id,'debit'=>'10','credit'=>'0'],['chart_account_id'=>3,'debit'=>'0','credit'=>'10']]));$this->fail('Ineligible account accepted.');} catch (ValidationException) {$this->assertSame(0,OpeningBalance::count());}}
    }

    public function test_inactive_financial_year_out_of_year_date_and_blocked_company_are_rejected(): void
    {
        foreach ([['financial_year_id'=>2],['business_date'=>'2027-01-01']] as $override) {try {$this->service()->create(1,1,array_replace($this->payload($this->basicLines()),$override));$this->fail('Invalid context accepted.');} catch (ValidationException) {$this->assertSame(0,OpeningBalance::count());}}
        Company::find(1)->update(['status'=>'blocked']);$this->expectException(RuntimeException::class);$this->service()->create(1,1,$this->payload($this->basicLines()));
    }

    public function test_locked_accounting_period_rejects_business_date_without_records(): void
    {
        DB::table('accounting_period_locks')->insert(['company_id'=>1,'financial_year_id'=>1,'date_from'=>'2026-01-01','date_to'=>'2026-01-31','is_locked'=>1,'reason'=>'Month closed','locked_by'=>2]);
        try {$this->service()->create(1,1,$this->payload($this->basicLines()));$this->fail('Locked period accepted.');} catch (ValidationException $e) {$this->assertStringContainsString('locked accounting period',$e->getMessage());$this->assertSame(0,OpeningBalance::count());}
    }

    public function test_duplicate_request_and_second_active_company_year_opening_are_rejected(): void
    {
        $payload=$this->payload($this->basicLines());$this->service()->create(1,1,$payload);
        try {$this->service()->create(1,1,$payload);$this->fail('Duplicate request accepted.');} catch (ValidationException) {$this->assertSame(1,OpeningBalance::count());}
        $payload['request_key']='22222222-2222-4222-8222-222222222222';$this->expectException(ValidationException::class);$this->service()->create(1,1,$payload);
    }

    public function test_maker_cannot_self_approve_and_only_valid_transitions_are_allowed(): void
    {
        $opening=$this->service()->create(1,1,$this->payload($this->basicLines()));$opening=$this->service()->submit($opening,1,1);
        try {$this->service()->approve($opening,1,1);$this->fail('Self approval accepted.');} catch (RuntimeException) {$this->assertSame('submitted',$opening->fresh()->status);}
        $approved=$this->service()->approve($opening,1,2);$this->assertSame('approved',$approved->status);
        $this->expectException(RuntimeException::class);$this->service()->approve($approved,1,2);
    }

    public function test_creator_cannot_see_approve_action_but_different_authorized_user_can(): void
    {
        $this->seedApprovalPermissions();
        Schema::create('company_subscriptions', function (Blueprint $table) {$table->id();$table->unsignedBigInteger('company_id');$table->string('status');$table->json('hidden_modules')->nullable();$table->boolean('is_all_modules_enabled')->default(true);$table->timestamps();});
        DB::table('company_subscriptions')->insert(['company_id'=>1,'status'=>'active','is_all_modules_enabled'=>1]);
        $opening=$this->service()->submit($this->service()->create(1,1,$this->payload($this->basicLines())),1,1);
        $this->withoutMiddleware([\App\Http\Middleware\EnsureCompanyUser::class,\App\Http\Middleware\CheckSubscription::class,\App\Http\Middleware\UpdateLastSeen::class]);
        $approveUrl=route('company.opening-balances.approve',$opening);

        $this->actingAs(User::findOrFail(1))->get(route('company.opening-balances.show',$opening))->assertOk()->assertDontSee($approveUrl,false);
        $this->get(route('company.opening-balances.index'))->assertOk()->assertDontSee($approveUrl,false);

        $this->actingAs(User::findOrFail(2))->get(route('company.opening-balances.show',$opening))->assertOk()->assertSee($approveUrl,false);
        $this->get(route('company.opening-balances.index'))->assertOk()->assertSee($approveUrl,false);
    }

    public function test_forged_self_approval_and_invalid_approval_workflow_redirect_with_normal_error(): void
    {
        $this->seedApprovalPermissions();
        $opening=$this->service()->submit($this->service()->create(1,1,$this->payload($this->basicLines())),1,1);
        $this->withoutMiddleware([\App\Http\Middleware\EnsureCompanyUser::class,\App\Http\Middleware\CheckSubscription::class,\App\Http\Middleware\UpdateLastSeen::class]);
        $showUrl=route('company.opening-balances.show',$opening);

        $this->actingAs(User::findOrFail(1))->from($showUrl)->post(route('company.opening-balances.approve',$opening))
            ->assertRedirect($showUrl)->assertSessionHas('error','The maker cannot approve their own Opening Balance.');
        $this->assertSame(OpeningBalance::STATUS_SUBMITTED,$opening->fresh()->status);

        $opening->update(['status'=>OpeningBalance::STATUS_DRAFT]);
        $this->actingAs(User::findOrFail(2))->from($showUrl)->post(route('company.opening-balances.approve',$opening))
            ->assertRedirect($showUrl)->assertSessionHas('error','Only a submitted Opening Balance may be approved.');
        $this->assertSame(OpeningBalance::STATUS_DRAFT,$opening->fresh()->status);
    }

    public function test_customer_and_supplier_openings_create_correct_subledgers(): void
    {
        $lines=[['chart_account_id'=>1,'debit'=>'75.0000','credit'=>'0','subledger_type'=>'customer','subledger_id'=>1],['chart_account_id'=>3,'debit'=>'0','credit'=>'75.0000'],['chart_account_id'=>3,'debit'=>'25.0000','credit'=>'0'],['chart_account_id'=>2,'debit'=>'0','credit'=>'25.0000','subledger_type'=>'supplier','subledger_id'=>1]];
        $opening=$this->service()->post($this->approved($lines),1,2);
        $this->assertDatabaseHas('customer_transactions',['reference_id'=>$opening->id,'debit'=>75]);$this->assertDatabaseHas('supplier_transactions',['reference_id'=>$opening->id,'credit'=>25]);
        $this->assertSame('75.0000',number_format((float)Customer::find(1)->current_balance,4,'.',''));$this->assertSame('-25.0000',number_format((float)Supplier::find(1)->current_balance,4,'.',''));
    }

    public function test_cross_company_subledger_and_operational_account_are_rejected(): void
    {
        foreach ([[['chart_account_id'=>1,'debit'=>'10','credit'=>'0','subledger_type'=>'customer','subledger_id'=>2],['chart_account_id'=>3,'debit'=>'0','credit'=>'10']],[['chart_account_id'=>1,'debit'=>'10','credit'=>'0','operational_account_id'=>2],['chart_account_id'=>3,'debit'=>'0','credit'=>'10']]] as $lines) {try {$this->service()->create(1,1,$this->payload($lines));$this->fail('Cross-company reference accepted.');} catch (ValidationException) {$this->assertSame(0,OpeningBalance::count());}}
    }

    public function test_cash_bank_posting_creates_exactly_one_transaction_and_balanced_entry(): void
    {
        $opening=$this->service()->post($this->approved([['chart_account_id'=>1,'debit'=>'50.5555','credit'=>'0','operational_account_id'=>1],['chart_account_id'=>3,'debit'=>'0','credit'=>'50.5555']]),1,2);
        $this->assertSame(1,DB::table('account_transactions')->where('reference_id',$opening->id)->count());$this->assertSame('50.5555',number_format((float)Account::find(1)->current_balance,4,'.',''));
        $entry=$opening->accountingEntry()->with('lines')->first();$this->assertEquals($entry->lines->sum('debit'),$entry->lines->sum('credit'));
    }

    public function test_complete_reversal_is_exactly_once_and_restores_subledger_cash_and_ledger(): void
    {
        $lines=[['chart_account_id'=>1,'debit'=>'60','credit'=>'0','operational_account_id'=>1],['chart_account_id'=>3,'debit'=>'0','credit'=>'100'],['chart_account_id'=>1,'debit'=>'40','credit'=>'0','subledger_type'=>'customer','subledger_id'=>1]];
        $opening=$this->service()->post($this->approved($lines),1,2);$opening=$this->service()->reverse($opening,1,2,'Approved correction');
        $this->assertSame('reversed',$opening->status);$this->assertSame('0.0000',number_format((float)Account::find(1)->current_balance,4,'.',''));$this->assertSame('0.0000',number_format((float)Customer::find(1)->current_balance,4,'.',''));
        $this->assertSame(2,DB::table('account_transactions')->where('reference_id',$opening->id)->count());$this->assertSame(2,AccountingEntry::where('source_id',$opening->id)->count());
        $this->assertNotNull(DB::table('account_transactions')->whereNotNull('reversed_transaction_id')->value('reversed_transaction_id'));
        $this->expectException(RuntimeException::class);$this->service()->reverse($opening->fresh(),1,2,'Again');
    }

    public function test_missing_original_auxiliary_transaction_rolls_back_reversal_atomically(): void
    {
        $opening=$this->service()->post($this->approved([['chart_account_id'=>1,'debit'=>'20','credit'=>'0','operational_account_id'=>1],['chart_account_id'=>3,'debit'=>'0','credit'=>'20']]),1,2);
        DB::table('account_transactions')->delete();
        try {$this->service()->reverse($opening,1,2,'Correction');$this->fail('Missing source accepted.');} catch (RuntimeException) {$this->assertSame('posted',$opening->fresh()->status);$this->assertSame(1,AccountingEntry::count());$this->assertSame(1,DB::table('journals')->count());}
    }

    public function test_cancel_lock_and_posted_immutability_rules_are_enforced(): void
    {
        $draft=$this->service()->create(1,1,$this->payload($this->basicLines()));$locked=$this->service()->setLock($draft,1,1,true,'Review hold');
        try {$this->service()->submit($locked,1,1);$this->fail('Locked draft submitted.');} catch (RuntimeException) {$this->assertTrue($locked->fresh()->is_locked);}
        $unlocked=$this->service()->setLock($locked,1,2,false,'Review completed');$cancelled=$this->service()->cancel($unlocked,1,1,'Duplicate draft');$this->assertSame('cancelled',$cancelled->status);
        $this->expectException(RuntimeException::class);$this->service()->submit($cancelled,1,1);
    }

    public function test_every_opening_balance_route_has_its_required_permission(): void
    {
        $expected=['index'=>'view','create'=>'create','store'=>'create','show'=>'view','edit'=>'edit-draft','update'=>'edit-draft','submit'=>'submit','approve'=>'approve','post'=>'post','cancel'=>'cancel','reverse'=>'reverse','lock'=>'lock','unlock'=>'unlock','audit'=>'audit-view','print'=>'print'];
        foreach($expected as $action=>$permission){$route=app('router')->getRoutes()->getByName('company.opening-balances.'.$action);$this->assertNotNull($route);$this->assertContains('permission:opening-balance.'.$permission,$route->gatherMiddleware());}
    }

    public function test_company_sidebar_shows_permission_guarded_opening_balance_link_and_active_state(): void
    {
        $this->seedApprovalPermissions();
        Schema::create('company_subscriptions', function (Blueprint $table) {$table->id();$table->unsignedBigInteger('company_id');$table->string('status');$table->json('hidden_modules')->nullable();$table->boolean('is_all_modules_enabled')->default(true);$table->timestamps();});
        DB::table('company_subscriptions')->insert(['company_id'=>1,'status'=>'active','is_all_modules_enabled'=>1]);
        $opening=$this->service()->create(1,1,$this->payload($this->basicLines()));
        $this->withoutMiddleware([\App\Http\Middleware\EnsureCompanyUser::class,\App\Http\Middleware\CheckSubscription::class,\App\Http\Middleware\UpdateLastSeen::class]);
        $indexUrl=route('company.opening-balances.index');

        $show=$this->actingAs(User::findOrFail(1))->get(route('company.opening-balances.show',$opening));
        $show->assertOk()->assertSee($indexUrl,false)->assertSee('dg-sidebar-active">Opening Balance',false);
        $this->get($indexUrl)->assertOk()->assertSee($indexUrl,false)->assertSee('dg-sidebar-active">Opening Balance',false);

        $this->actingAs(User::findOrFail(3));
        $sidebar=view('company.partials.sidebar')->render();
        $this->assertStringNotContainsString($indexUrl,$sidebar);
    }

    public function test_unauthorized_staff_is_rejected_by_every_opening_balance_http_action(): void
    {
        $staff=User::findOrFail(3);
        $opening=$this->service()->create(1,1,$this->payload($this->basicLines()));
        $this->withoutMiddleware([\App\Http\Middleware\EnsureCompanyUser::class,\App\Http\Middleware\CheckSubscription::class,\App\Http\Middleware\UpdateLastSeen::class]);
        $requests=[['get','company.opening-balances.index',[]],['get','company.opening-balances.create',[]],['post','company.opening-balances.store',[]],['get','company.opening-balances.show',[$opening]],['get','company.opening-balances.edit',[$opening]],['put','company.opening-balances.update',[$opening]],['post','company.opening-balances.submit',[$opening]],['post','company.opening-balances.approve',[$opening]],['post','company.opening-balances.post',[$opening]],['post','company.opening-balances.cancel',[$opening]],['post','company.opening-balances.reverse',[$opening]],['post','company.opening-balances.lock',[$opening]],['post','company.opening-balances.unlock',[$opening]],['get','company.opening-balances.audit',[$opening]],['get','company.opening-balances.print',[$opening]]];
        foreach($requests as [$method,$name,$parameters]){$response=$this->actingAs($staff)->{$method}(route($name,$parameters));$response->assertForbidden();}
    }

    public function test_chart_account_only_opening_balance_journal_renders_in_show_and_voucher(): void
    {
        Schema::create('company_subscriptions', function (Blueprint $t) {$t->id();$t->unsignedBigInteger('company_id');$t->string('status');$t->json('hidden_modules')->nullable();$t->boolean('is_all_modules_enabled')->default(true);$t->timestamps();});
        DB::table('company_subscriptions')->insert(['company_id'=>1,'status'=>'active','is_all_modules_enabled'=>1]);
        $this->actingAs(User::findOrFail(1));
        $opening=$this->approved($this->basicLines());$opening=$this->service()->post($opening,1,2);
        $journal=\App\Models\Journal::with(['items.account','items.chartAccount','financialYear','createdBy','updatedByUser'])->findOrFail($opening->journal_id);
        foreach(['company.journal.show','company.journal.voucher-print'] as $view){$html=view($view,['journal'=>$journal,'errors'=>new \Illuminate\Support\ViewErrorBag])->render();$this->assertStringContainsString('<td>1130 — AR/Cash</td>', $html);$this->assertStringContainsString('100.1234', $html);}
    }

    public function test_main_opening_stays_single_while_different_new_account_openings_share_the_financial_year(): void
    {
        $this->service()->create(1,1,$this->payload($this->basicLines()));
        $cash=$this->service()->create(1,1,$this->newAccountPayload(3,'10.0000','cash'));
        $bank=$this->service()->create(1,1,$this->newAccountPayload(1,'20.0000','bank'));

        $this->assertSame('active',OpeningBalance::where('type','initial')->value('active_key'));
        $this->assertSame('na:0000000000000003',$cash->active_key);
        $this->assertSame('na:0000000000000001',$bank->active_key);
        $this->assertSame(3,OpeningBalance::count());

        $duplicateMain=$this->payload($this->basicLines());
        $duplicateMain['request_key']='99999999-9999-4999-8999-999999999999';
        $duplicateMain['reference_number']='OB-002';
        $this->expectException(ValidationException::class);
        $this->service()->create(1,1,$duplicateMain);
    }

    public function test_duplicate_new_account_identity_and_foreign_or_other_accounts_are_rejected(): void
    {
        $this->service()->create(1,1,$this->newAccountPayload(1,'25.0000','first'));

        foreach ([
            $this->newAccountPayload(1,'30.0000','duplicate'),
            $this->newAccountPayload(2,'30.0000','foreign'),
            $this->newAccountPayload(6,'30.0000','other'),
        ] as $payload) {
            try {
                $this->service()->create(1,1,$payload);
                $this->fail('Invalid new-account Opening Balance was accepted.');
            } catch (ValidationException) {
                $this->assertSame(1,OpeningBalance::count());
            }
        }
    }

    public function test_cash_bank_atm_and_wallet_use_the_approved_automatic_chart_mappings(): void
    {
        foreach ([3=>'CASH_IN_HAND',1=>'BANK_ACCOUNTS',4=>'BANK_ACCOUNTS',5=>'BANK_ACCOUNTS'] as $accountId=>$systemCode) {
            $opening=$this->service()->create(1,1,$this->newAccountPayload($accountId,'12.3400','map-'.$accountId));
            $operational=$opening->lines->firstWhere('operational_account_id',$accountId);
            $equity=$opening->lines->firstWhere('operational_account_id',null);
            $this->assertSame($systemCode,ChartAccount::findOrFail($operational->chart_account_id)->system_code);
            $this->assertSame('12.3400',$operational->debit);
            $this->assertSame('0.0000',$operational->credit);
            $this->assertSame('OPENING_BALANCE_EQUITY',ChartAccount::findOrFail($equity->chart_account_id)->system_code);
            $this->assertSame('12.3400',$equity->credit);
        }
    }

    public function test_new_account_posting_is_exactly_once_recalculates_balance_and_reversal_restores_it(): void
    {
        $opening=$this->service()->create(1,1,$this->newAccountPayload(1,'45.6700','post'));
        $opening=$this->service()->submit($opening,1,1);
        try {$this->service()->approve($opening,1,1);$this->fail('Maker approved own new-account opening.');} catch (RuntimeException) {$this->assertSame('submitted',$opening->fresh()->status);}
        $opening=$this->service()->approve($opening,1,2);
        $opening=$this->service()->post($opening,1,2);

        $this->assertSame(1,DB::table('journals')->where('source_id',$opening->id)->where('source_type','opening_balance')->count());
        $this->assertSame(1,DB::table('accounting_entries')->where('source_id',$opening->id)->where('source_event','posted')->count());
        $this->assertSame(1,DB::table('account_transactions')->where('reference_id',$opening->id)->where('reference_type','opening_balance')->count());
        $this->assertSame('45.6700',number_format((float)Account::find(1)->current_balance,4,'.',''));
        $this->assertSame('0.0000',number_format((float)Account::find(1)->opening_balance,4,'.',''));
        $this->assertSame('45.6700',$this->service()->postedOperationalBalances(1,[1])->get(1));

        try {$this->service()->update($opening,1,2,$this->newAccountPayload(1,'50.0000','edit'));$this->fail('Posted opening was edited.');} catch (RuntimeException) {$this->assertSame('posted',$opening->fresh()->status);}

        $opening=$this->service()->reverse($opening,1,2,'Approved correction');
        $this->assertSame('0.0000',number_format((float)Account::find(1)->current_balance,4,'.',''));
        $this->assertSame(2,DB::table('account_transactions')->where('reference_id',$opening->id)->count());
        $this->assertFalse($this->service()->postedOperationalBalances(1,[1])->has(1));
    }

    public function test_new_account_credit_line_tampering_is_rejected_before_submit(): void
    {
        $opening=$this->service()->create(1,1,$this->newAccountPayload(1,'15.0000','credit'));
        $operational=$opening->lines->firstWhere('operational_account_id',1);
        $equity=$opening->lines->firstWhere('operational_account_id',null);
        $operational->update(['debit'=>0,'credit'=>15]);
        $equity->update(['debit'=>15,'credit'=>0]);

        $this->expectException(ValidationException::class);
        $this->service()->submit($opening->fresh(),1,1);
    }

    public function test_new_account_ignores_forged_client_lines_and_rejects_non_positive_amounts(): void
    {
        $payload=$this->newAccountPayload(1,'19.2500','forged');
        $payload['lines']=[
            ['chart_account_id'=>7,'debit'=>'999.0000','credit'=>'0.0000'],
            ['chart_account_id'=>3,'debit'=>'0.0000','credit'=>'1.0000'],
        ];
        $opening=$this->service()->create(1,1,$payload);
        $operational=$opening->lines->firstWhere('operational_account_id',1);
        $equity=$opening->lines->firstWhere('operational_account_id',null);

        $this->assertCount(2,$opening->lines);
        $this->assertSame('BANK_ACCOUNTS',ChartAccount::findOrFail($operational->chart_account_id)->system_code);
        $this->assertSame('19.2500',$operational->debit);
        $this->assertSame('19.2500',$equity->credit);
        $this->assertSame('New account opening',$operational->description);

        foreach (['0','-1'] as $amount) {
            try {
                $this->service()->create(1,1,$this->newAccountPayload(3,$amount,'invalid-'.$amount));
                $this->fail('A non-positive new-account opening amount was accepted.');
            } catch (ValidationException) {
                $this->assertSame(1,OpeningBalance::count());
            }
        }
    }

    public function test_draft_new_account_edit_regenerates_lines_from_operational_account_and_amount(): void
    {
        $opening=$this->service()->create(1,1,$this->newAccountPayload(1,'10.0000','draft'));
        $updated=$this->service()->update($opening,1,1,$this->newAccountPayload(3,'27.5000','changed'));
        $operational=$updated->lines->firstWhere('operational_account_id',3);
        $equity=$updated->lines->firstWhere('operational_account_id',null);

        $this->assertSame('na:0000000000000003',$updated->active_key);
        $this->assertSame('CASH_IN_HAND',ChartAccount::findOrFail($operational->chart_account_id)->system_code);
        $this->assertSame('27.5000',$operational->debit);
        $this->assertSame('27.5000',$equity->credit);
        $this->assertSame('New account opening',$updated->remarks);
    }

    public function test_new_account_request_and_form_use_the_simplified_operational_inputs(): void
    {
        $payload=$this->newAccountPayload(1,'125.5000','request');
        $request=\App\Http\Requests\OpeningBalanceRequest::create('/opening-balances','POST',$payload);
        $validator=\Illuminate\Support\Facades\Validator::make($payload,$request->rules());
        $this->assertFalse($validator->fails(),json_encode($validator->errors()->toArray()));

        $this->withSession(['_old_input'=>['type'=>'new_account','new_account_id'=>1,'new_account_amount'=>'125.5000']]);
        $html=view('company.opening_balance.partials.form',[
            'financialYears'=>FinancialYear::where('company_id',1)->get(),
            'chartAccounts'=>ChartAccount::where('company_id',1)->get(),
            'customers'=>Customer::where('company_id',1)->get(),
            'suppliers'=>Supplier::where('company_id',1)->get(),
            'operationalAccounts'=>Account::where('company_id',1)->get(),
            'newAccountOperationalAccounts'=>Account::where('company_id',1)->whereIn('account_type',['Cash','Bank','ATM','Wallet'])->get(),
            'openingBalanceAccountPicker'=>[
                'CASH / BANK'=>collect([['value'=>'operational:1','label'=>'Bank — Bank']]),
                'CUSTOMERS'=>collect([['value'=>'customer:1','label'=>'C1']]),
                'SUPPLIERS'=>collect([['value'=>'supplier:1','label'=>'S1']]),
                'LEDGER / CHART ACCOUNTS'=>collect([['value'=>'chart:3','label'=>'3140 — OB Equity']]),
            ],
            'errors'=>new \Illuminate\Support\ViewErrorBag,
        ])->render();

        $this->assertStringContainsString('id="dg-ob-new-account-section"',$html);
        $this->assertStringContainsString('id="dg-ob-lines-section"',$html);
        $this->assertStringContainsString('id="new_account_id" name="new_account_id"',$html);
        $this->assertStringContainsString('id="new_account_amount" name="new_account_amount"',$html);
        $this->assertStringContainsString('Posting Preview',$html);
        $this->assertStringNotContainsString('Other — Other',$html);
        $this->assertStringContainsString('name="lines[0][account_picker]"',$html);
        $this->assertStringContainsString('Opening Balance Voucher',$html);
        $this->assertStringNotContainsString('name="lines[0][chart_account_id]"',$html);
        $this->assertStringNotContainsString('name="lines[0][subledger_id]"',$html);
        $this->assertStringNotContainsString('name="lines[0][operational_account_id]"',$html);
    }

    public function test_main_opening_account_picker_resolves_operational_party_and_chart_accounts(): void
    {
        $cases=[
            ['operational:3','chart:3','CASH_IN_HAND',3,null,null],
            ['operational:1','chart:3','BANK_ACCOUNTS',1,null,null],
            ['customer:1','chart:3','ACCOUNTS_RECEIVABLE',null,'customer',1],
            ['chart:3','supplier:1','OPENING_BALANCE_EQUITY',null,null,null],
            ['chart:8','chart:3','CASH_IN_HAND',null,null,null],
        ];

        foreach ($cases as $index=>[$debitPicker,$creditPicker,$expectedCode,$operationalId,$partyType,$partyId]) {
            $payload=$this->pickerPayload($debitPicker,$creditPicker,'picker-'.$index);
            $opening=$this->service()->create(1,1,$payload);
            $debit=$opening->lines->firstWhere('debit','40.0000');
            $this->assertSame($expectedCode,ChartAccount::findOrFail($debit->chart_account_id)->system_code);
            $this->assertSame($operationalId,$debit->operational_account_id);
            $this->assertSame($partyType,$debit->subledger_type);
            $this->assertSame($partyId,$debit->subledger_id);

            if ($creditPicker==='supplier:1') {
                $credit=$opening->lines->firstWhere('credit','40.0000');
                $this->assertSame('ACCOUNTS_PAYABLE',ChartAccount::findOrFail($credit->chart_account_id)->system_code);
                $this->assertSame('supplier',$credit->subledger_type);
                $this->assertSame(1,$credit->subledger_id);
            }
            $opening->update(['status'=>OpeningBalance::STATUS_CANCELLED,'active_key'=>null]);
        }
    }

    public function test_main_opening_picker_rejects_foreign_inactive_and_forged_entity_data(): void
    {
        Customer::create(['company_id'=>1,'name'=>'Inactive Customer','status'=>'inactive']);
        foreach (['operational:2','customer:'.Customer::where('name','Inactive Customer')->value('id'),'operational:6','chart:6'] as $picker) {
            $payload=$this->pickerPayload($picker,'chart:3','invalid-'.str_replace(':','-',$picker));
            try {
                $this->service()->create(1,1,$payload);
                $this->fail('A foreign or ineligible Account Picker entity was accepted.');
            } catch (ValidationException) {
                $this->assertSame(0,OpeningBalance::whereNotIn('status',[OpeningBalance::STATUS_CANCELLED])->count());
            }
        }

        $payload=$this->pickerPayload('operational:1','chart:3','forged');
        $payload['lines'][0]['chart_account_id']=7;
        $payload['lines'][0]['operational_account_id']=6;
        $opening=$this->service()->create(1,1,$payload);
        $debit=$opening->lines->firstWhere('debit','40.0000');
        $this->assertSame('BANK_ACCOUNTS',ChartAccount::findOrFail($debit->chart_account_id)->system_code);
        $this->assertSame(1,$debit->operational_account_id);
    }

    private function approved(array $lines): OpeningBalance {$o=$this->service()->create(1,1,$this->payload($lines));$o=$this->service()->submit($o,1,1);return $this->service()->approve($o,1,2);}
    private function service(): OpeningBalanceService {return app(OpeningBalanceService::class);}
    private function payload(array $lines): array {return ['financial_year_id'=>1,'business_date'=>'2026-01-01','type'=>'initial','reference_number'=>'OB-001','remarks'=>'Opening','request_key'=>'11111111-1111-4111-8111-111111111111','lines'=>$lines];}
    private function newAccountPayload(int $accountId,string $amount,string $suffix): array {return ['financial_year_id'=>1,'business_date'=>'2026-01-01','type'=>'new_account','reference_number'=>'NA-'.strtoupper($suffix),'remarks'=>'New account opening','request_key'=>(string)\Illuminate\Support\Str::uuid(),'new_account_id'=>$accountId,'new_account_amount'=>$amount];}
    private function pickerPayload(string $debitPicker,string $creditPicker,string $suffix): array {return ['financial_year_id'=>1,'business_date'=>'2026-01-01','type'=>'initial','reference_number'=>'PK-'.strtoupper($suffix),'remarks'=>'Picker opening','request_key'=>(string)\Illuminate\Support\Str::uuid(),'lines'=>[['account_picker'=>$debitPicker,'debit'=>'40.0000','credit'=>'0','description'=>'Debit'],['account_picker'=>$creditPicker,'debit'=>'0','credit'=>'40.0000','description'=>'Credit']]];}
    private function seedApprovalPermissions(): void {DB::table('permissions')->insert([['id'=>1,'name'=>'module_opening_balance','scope'=>'company'],['id'=>2,'name'=>'opening-balance.approve','scope'=>'company'],['id'=>3,'name'=>'opening-balance.view','scope'=>'company']]);}
    private function basicLines(): array {return [['chart_account_id'=>1,'debit'=>'100.1234','credit'=>'0'],['chart_account_id'=>3,'debit'=>'0','credit'=>'100.1234']];}

    private function seedContext(): void
    {
        Company::insert([['id'=>1,'company_name'=>'One','status'=>'active'],['id'=>2,'company_name'=>'Two','status'=>'active']]);
        DB::table('roles')->insert([['id'=>2,'name'=>'Company Admin'],['id'=>3,'name'=>'Staff']]);
        User::insert([['id'=>1,'name'=>'Maker','company_id'=>1,'role_id'=>2],['id'=>2,'name'=>'Checker','company_id'=>1,'role_id'=>2],['id'=>3,'name'=>'Unauthorized Staff','company_id'=>1,'role_id'=>3]]);
        FinancialYear::insert([['id'=>1,'company_id'=>1,'name'=>'FY26','start_date'=>'2026-01-01','end_date'=>'2026-12-31','is_active'=>1],['id'=>2,'company_id'=>1,'name'=>'FY25','start_date'=>'2025-01-01','end_date'=>'2025-12-31','is_active'=>0]]);
        foreach ([['id'=>1,'company_id'=>1,'code'=>'1130','name'=>'AR/Cash','account_class'=>'asset','normal_balance'=>'debit','system_code'=>'ACCOUNTS_RECEIVABLE','level'=>3,'is_control'=>0,'status'=>'active'],['id'=>2,'company_id'=>1,'code'=>'2110','name'=>'AP','account_class'=>'liability','normal_balance'=>'credit','system_code'=>'ACCOUNTS_PAYABLE','level'=>3,'is_control'=>1,'status'=>'active'],['id'=>3,'company_id'=>1,'code'=>'3140','name'=>'OB Equity','account_class'=>'equity','normal_balance'=>'credit','system_code'=>'OPENING_BALANCE_EQUITY','level'=>3,'is_control'=>0,'allow_manual_entry'=>0,'status'=>'active'],['id'=>4,'company_id'=>1,'code'=>'1000','name'=>'Root','account_class'=>'asset','normal_balance'=>'debit','level'=>1,'is_control'=>0,'status'=>'active'],['id'=>5,'company_id'=>1,'code'=>'1190','name'=>'Inactive','account_class'=>'asset','normal_balance'=>'debit','level'=>3,'is_control'=>0,'status'=>'inactive'],['id'=>6,'company_id'=>2,'code'=>'1191','name'=>'Foreign','account_class'=>'asset','normal_balance'=>'debit','level'=>3,'is_control'=>0,'status'=>'active'],['id'=>7,'company_id'=>1,'code'=>'4000','name'=>'Income','account_class'=>'income','normal_balance'=>'credit','level'=>3,'is_control'=>0,'status'=>'active'],['id'=>8,'company_id'=>1,'code'=>'1110','name'=>'Cash in Hand','account_class'=>'asset','normal_balance'=>'debit','system_code'=>'CASH_IN_HAND','level'=>3,'is_control'=>0,'allow_manual_entry'=>1,'status'=>'active'],['id'=>9,'company_id'=>1,'code'=>'1120','name'=>'Bank Accounts','account_class'=>'asset','normal_balance'=>'debit','system_code'=>'BANK_ACCOUNTS','level'=>3,'is_control'=>0,'allow_manual_entry'=>1,'status'=>'active']] as $row) ChartAccount::create($row);
        Customer::insert([['id'=>1,'company_id'=>1,'name'=>'C1','status'=>'active'],['id'=>2,'company_id'=>2,'name'=>'C2','status'=>'active']]);Supplier::insert([['id'=>1,'company_id'=>1,'name'=>'S1','status'=>'active']]);Account::insert([['id'=>1,'company_id'=>1,'account_name'=>'Bank','account_type'=>'Bank','status'=>'active'],['id'=>2,'company_id'=>2,'account_name'=>'Foreign Bank','account_type'=>'Bank','status'=>'active'],['id'=>3,'company_id'=>1,'account_name'=>'Cash','account_type'=>'Cash','status'=>'active'],['id'=>4,'company_id'=>1,'account_name'=>'ATM','account_type'=>'ATM','status'=>'active'],['id'=>5,'company_id'=>1,'account_name'=>'Wallet','account_type'=>'Wallet','status'=>'active'],['id'=>6,'company_id'=>1,'account_name'=>'Other','account_type'=>'Other','status'=>'active']]);
    }
}
