<?php

namespace Tests\Feature;

use App\Models\AccountingEntry;
use App\Models\Journal;
use App\Models\JournalAuditEvent;
use App\Models\User;
use App\Services\JournalService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class JournalPhaseTwoTest extends OpeningBalanceModuleTest
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::table('financial_years', fn (Blueprint $t) => [$t->boolean('is_closed')->default(false), $t->boolean('is_locked')->default(false)]);
        Schema::table('chart_accounts', fn (Blueprint $t) => $t->boolean('is_locked')->default(false));
        Schema::table('journals', function (Blueprint $t) {
            $t->string('journal_type')->nullable();$t->text('description')->nullable();$t->text('remarks')->nullable();$t->uuid('request_key')->nullable();
            foreach(['submitted_by','approved_by','rejected_by','locked_by','unlocked_by'] as $c)$t->unsignedBigInteger($c)->nullable();
            foreach(['submitted_at','approved_at','rejected_at','cancelled_at','locked_at','unlocked_at'] as $c)$t->timestamp($c)->nullable();
            foreach(['rejection_reason','cancellation_reason','reversal_reason','lock_reason','unlock_reason'] as $c)$t->text($c)->nullable();
            $t->boolean('is_locked')->default(false);
        });
        Schema::table('journal_items', function (Blueprint $t) {$t->decimal('debit',20,4)->default(0);$t->decimal('credit',20,4)->default(0);$t->text('description')->nullable();$t->string('reference')->nullable();$t->unsignedInteger('line_number')->nullable();});
        Schema::create('journal_number_sequences', fn (Blueprint $t) => [$t->unsignedBigInteger('company_id'),$t->unsignedBigInteger('financial_year_id'),$t->unsignedBigInteger('next_number'),$t->timestamps(),$t->primary(['company_id','financial_year_id'])]);
        Schema::create('journal_audit_events', function (Blueprint $t) {$t->id();$t->unsignedBigInteger('company_id');$t->unsignedBigInteger('financial_year_id');$t->unsignedBigInteger('journal_id');$t->string('event');$t->string('previous_status')->nullable();$t->string('new_status')->nullable();$t->unsignedBigInteger('actor_id');$t->timestamp('event_at');$t->text('reason')->nullable();$t->json('metadata')->nullable();});
        DB::table('chart_accounts')->insert([
            ['id'=>8,'company_id'=>1,'code'=>'1120','name'=>'Bank','account_class'=>'asset','normal_balance'=>'debit','system_code'=>'BANK_ACCOUNTS','level'=>3,'is_control'=>0,'allow_manual_entry'=>1,'status'=>'active','created_at'=>now(),'updated_at'=>now()],
            ['id'=>9,'company_id'=>1,'code'=>'1110','name'=>'Cash','account_class'=>'asset','normal_balance'=>'debit','system_code'=>'CASH_IN_HAND','level'=>3,'is_control'=>0,'allow_manual_entry'=>1,'status'=>'active','created_at'=>now(),'updated_at'=>now()],
        ]);
    }

    public function test_approved_posting_creates_one_exact_balanced_official_entry_visible_to_ledger_queries(): void
    {
        $journal=$this->approved($this->lines('100.1234'));
        $posted=$this->service()->post($journal,3);
        $entry=AccountingEntry::with('lines')->where('source_type','manual_journal')->where('source_id',$posted->id)->sole();
        $this->assertSame(Journal::STATUS_POSTED,$posted->status);
        $this->assertSame(1,$entry->financial_year_id);
        $this->assertSame(2,$entry->lines->count());
        $this->assertSame(['100.1234','0.0000'],$entry->lines->pluck('debit')->all());
        $this->assertSame(['0.0000','100.1234'],$entry->lines->pluck('credit')->all());
        $this->assertSame('100.1234',$this->sum('debit'));
        $this->assertSame('100.1234',$this->sum('credit'));
        $this->assertSame(1,JournalAuditEvent::where('journal_id',$posted->id)->where('event','posted')->count());
    }

    public function test_duplicate_posting_is_rejected_without_partial_records(): void
    {
        $posted=$this->service()->post($this->approved($this->lines()),3);
        $counts=$this->counts();
        try{$this->service()->post($posted->fresh(),3);$this->fail('Duplicate posting accepted.');}catch(RuntimeException){$this->assertSame($counts,$this->counts());}
    }

    public function test_posting_failure_rolls_back_accounting_journal_status_auxiliary_and_audit(): void
    {
        $journal=$this->approved([['chart_account_id'=>1,'debit'=>'10','credit'=>'0','subledger_type'=>'supplier','subledger_id'=>1],['chart_account_id'=>3,'debit'=>'0','credit'=>'10']]);
        $before=$this->counts();
        try{$this->service()->post($journal,3);$this->fail('Invalid control account accepted.');}catch(RuntimeException){$this->assertSame(Journal::STATUS_APPROVED,$journal->fresh()->status);$this->assertSame($before,$this->counts());}
    }

    public function test_cash_customer_and_supplier_auxiliaries_are_exactly_once_and_company_scoped(): void
    {
        $cash=$this->service()->post($this->approved([['chart_account_id'=>8,'account_id'=>1,'debit'=>'50.5555','credit'=>'0'],['chart_account_id'=>3,'debit'=>'0','credit'=>'50.5555']]),3);
        $this->assertSame(1,DB::table('account_transactions')->where('reference_type','ManualJournal')->where('reference_id',$cash->id)->count());
        $this->assertSame('50.5555',$this->decimal(DB::table('accounts')->where('id',1)->value('current_balance')));
        $customer=$this->service()->post($this->approved([['chart_account_id'=>1,'debit'=>'20','credit'=>'0','subledger_type'=>'customer','subledger_id'=>1],['chart_account_id'=>3,'debit'=>'0','credit'=>'20']]),3);
        $supplier=$this->service()->post($this->approved([['chart_account_id'=>3,'debit'=>'30','credit'=>'0'],['chart_account_id'=>2,'debit'=>'0','credit'=>'30','subledger_type'=>'supplier','subledger_id'=>1]]),3);
        $this->assertSame(1,DB::table('customer_transactions')->where('reference_id',$customer->id)->count());
        $this->assertSame(1,DB::table('supplier_transactions')->where('reference_id',$supplier->id)->count());
        foreach([[2,'account_id'],[2,'customer']] as [$foreign,$kind]){
            $lines=$kind==='account_id'?[['chart_account_id'=>1,'account_id'=>$foreign,'debit'=>'1','credit'=>'0'],['chart_account_id'=>3,'debit'=>'0','credit'=>'1']]:[['chart_account_id'=>1,'debit'=>'1','credit'=>'0','subledger_type'=>'customer','subledger_id'=>$foreign],['chart_account_id'=>3,'debit'=>'0','credit'=>'1']];
            try{$this->service()->createDraft($this->payload($lines),1,1);$this->fail('Cross-company reference accepted.');}catch(ValidationException|RuntimeException){$this->assertTrue(true);}
        }
    }

    public function test_complete_reversal_is_exactly_once_and_restores_all_effects(): void
    {
        $posted=$this->service()->post($this->approved([['chart_account_id'=>8,'account_id'=>1,'debit'=>'60','credit'=>'0'],['chart_account_id'=>3,'debit'=>'0','credit'=>'100'],['chart_account_id'=>1,'debit'=>'40','credit'=>'0','subledger_type'=>'customer','subledger_id'=>1]]),3);
        $reversal=$this->service()->reverse($posted,2,'Correction approved');
        $this->assertSame(Journal::STATUS_REVERSED,$posted->fresh()->status);
        $this->assertSame(1,Journal::where('reversal_of_journal_id',$posted->id)->count());
        $this->assertSame(1,AccountingEntry::where('reversal_of_id','!=',null)->count());
        $this->assertSame(1,DB::table('account_transactions')->whereNotNull('reversed_transaction_id')->count());
        $this->assertSame(1,DB::table('customer_transactions')->whereNotNull('reversed_transaction_id')->count());
        $this->assertSame('0.0000',$this->decimal(DB::table('accounts')->where('id',1)->value('current_balance')));
        $this->assertSame('0.0000',$this->decimal(DB::table('customers')->where('id',1)->value('current_balance')));
        $this->assertSame('100.0000',$this->decimal($reversal->items->sum('credit')));
        try{$this->service()->reverse($posted->fresh(),2,'Again');$this->fail('Double reversal accepted.');}catch(RuntimeException){$this->assertSame(1,Journal::where('reversal_of_journal_id',$posted->id)->count());}
    }

    public function test_missing_or_duplicate_original_accounting_and_auxiliary_fail_reversal_atomically(): void
    {
        foreach(['missing_entry','duplicate_entry','missing_auxiliary'] as $case){
            $posted=$this->service()->post($this->approved([['chart_account_id'=>8,'account_id'=>1,'debit'=>'10','credit'=>'0'],['chart_account_id'=>3,'debit'=>'0','credit'=>'10']]),3);
            if($case==='missing_entry'){DB::table('accounting_entry_lines')->delete();DB::table('accounting_entries')->delete();}
            if($case==='duplicate_entry'){ $e=AccountingEntry::where('source_id',$posted->id)->first()->replicate();$e->source_key='duplicate:'.Str::uuid();$e->save(); }
            if($case==='missing_auxiliary')DB::table('account_transactions')->where('reference_id',$posted->id)->delete();
            $before=$this->counts();try{$this->service()->reverse($posted,2,'Integrity test');$this->fail($case.' accepted.');}catch(RuntimeException){$this->assertSame($before,$this->counts());$this->assertSame(Journal::STATUS_POSTED,$posted->fresh()->status);}
            $this->resetPostedData();
        }
    }

    public function test_prior_year_and_period_locked_reversal_are_rejected_without_mutation(): void
    {
        $posted=$this->service()->post($this->approved($this->lines()),3);DB::table('financial_years')->where('id',1)->update(['is_active'=>0]);$this->assertReversalRejected($posted);
        DB::table('financial_years')->where('id',1)->update(['is_active'=>1]);DB::table('accounting_period_locks')->insert(['company_id'=>1,'financial_year_id'=>1,'date_from'=>'2026-01-01','date_to'=>'2026-12-31','is_locked'=>1,'reason'=>'Closed','locked_by'=>2]);$this->assertReversalRejected($posted);
    }

    public function test_audit_events_cannot_be_updated_or_deleted(): void
    {
        $event=$this->service()->createDraft($this->payload($this->lines()),1,1)->auditEvents()->first();
        try{$event->update(['reason'=>'tampered']);$this->fail('Audit update accepted.');}catch(RuntimeException){$this->assertNull($event->fresh()->reason);}
        $this->expectException(RuntimeException::class);$event->delete();
    }

    public function test_all_eight_workflow_routes_enforce_exact_permissions_and_authorized_actions_succeed(): void
    {
        $this->withoutMiddleware([\App\Http\Middleware\EnsureCompanyUser::class,\App\Http\Middleware\CheckSubscription::class,\App\Http\Middleware\EnsureSubscriptionModule::class,\App\Http\Middleware\UpdateLastSeen::class]);
        $draft=$this->service()->createDraft($this->payload($this->lines()),1,2);
        foreach(['submit','approve','reject','post','cancel','reverse','lock','unlock'] as $action){
            $data=in_array($action,['reject','cancel','reverse','lock','unlock'],true)?['reason'=>'Authorized reason']:[];
            $this->actingAs(User::findOrFail(3))->post(route('company.journal.'.$action,$draft->id),$data)->assertForbidden();
        }

        $submit=$this->service()->createDraft($this->payload($this->lines()),1,2);$this->actingAs(User::findOrFail(1))->post(route('company.journal.submit',$submit))->assertRedirect();$this->assertSame('submitted',$submit->fresh()->status);
        $approve=$this->service()->submit($this->service()->createDraft($this->payload($this->lines()),1,2),2);$this->actingAs(User::findOrFail(1))->post(route('company.journal.approve',$approve))->assertRedirect();$this->assertSame('approved',$approve->fresh()->status);
        $reject=$this->service()->submit($this->service()->createDraft($this->payload($this->lines()),1,2),2);$this->actingAs(User::findOrFail(1))->post(route('company.journal.reject',$reject),['reason'=>'Correction'])->assertRedirect();$this->assertSame('draft',$reject->fresh()->status);
        $cancel=$this->service()->createDraft($this->payload($this->lines()),1,2);$this->actingAs(User::findOrFail(1))->post(route('company.journal.cancel',$cancel),['reason'=>'Duplicate'])->assertRedirect();$this->assertSame('cancelled',$cancel->fresh()->status);
        $lock=$this->service()->createDraft($this->payload($this->lines()),1,2);$this->actingAs(User::findOrFail(1))->post(route('company.journal.lock',$lock),['reason'=>'Review'])->assertRedirect();$this->assertTrue($lock->fresh()->is_locked);$this->actingAs(User::findOrFail(1))->post(route('company.journal.unlock',$lock),['reason'=>'Reviewed'])->assertRedirect();$this->assertFalse($lock->fresh()->is_locked);
        $post=$this->approvedForHttp();$this->actingAs(User::findOrFail(1))->post(route('company.journal.post',$post))->assertRedirect();$this->assertSame('posted',$post->fresh()->status);
        $this->actingAs(User::findOrFail(1))->post(route('company.journal.reverse',$post),['reason'=>'Correction'])->assertRedirect();$this->assertSame('reversed',$post->fresh()->status);
    }

    public function test_journal_request_hardening_and_operational_account_mappings_are_enforced(): void
    {
        foreach ([
            [['chart_account_id'=>1,'debit'=>'10','credit'=>'0','subledger_type'=>'customer'],['chart_account_id'=>3,'debit'=>'0','credit'=>'10']],
            [['chart_account_id'=>1,'debit'=>'10','credit'=>'0','subledger_id'=>1],['chart_account_id'=>3,'debit'=>'0','credit'=>'10']],
            [['chart_account_id'=>1,'debit'=>'10','credit'=>'0','subledger_type'=>'employee','subledger_id'=>1],['chart_account_id'=>3,'debit'=>'0','credit'=>'10']],
        ] as $lines) {
            $request=\App\Http\Requests\JournalRequest::create('/', 'POST', $this->payload($lines));$request->setUserResolver(fn()=>User::findOrFail(1));$validator=Validator::make($request->all(),$request->rules());$request->withValidator($validator);$this->assertTrue($validator->fails());
        }
        $this->assertSame('posted',$this->service()->post($this->approved([['chart_account_id'=>8,'account_id'=>1,'debit'=>'10','credit'=>'0'],['chart_account_id'=>3,'debit'=>'0','credit'=>'10']]),3)->status);
        foreach ([['Cash',9],['ATM',8],['Wallet',8]] as [$type,$chart]) {DB::table('accounts')->where('id',1)->update(['account_type'=>$type]);$this->assertSame('posted',$this->service()->post($this->approved([['chart_account_id'=>$chart,'account_id'=>1,'debit'=>'10','credit'=>'0'],['chart_account_id'=>3,'debit'=>'0','credit'=>'10']]),3)->status);}
        DB::table('accounts')->where('id',1)->update(['account_type'=>'Bank']);
        try{$this->service()->post($this->approved([['chart_account_id'=>8,'debit'=>'10','credit'=>'0'],['chart_account_id'=>3,'debit'=>'0','credit'=>'10']]),3);$this->fail('Bank Chart Account without operational account accepted.');}catch(ValidationException){$this->assertTrue(true);}
        DB::table('accounts')->where('id',1)->update(['account_type'=>'Cash']);
        try{$this->service()->post($this->approved([['chart_account_id'=>8,'account_id'=>1,'debit'=>'10','credit'=>'0'],['chart_account_id'=>3,'debit'=>'0','credit'=>'10']]),3);$this->fail('Mismatched operational Account accepted.');}catch(ValidationException){$this->assertTrue(true);}
        DB::table('accounts')->where('id',1)->update(['account_type'=>'Bank']);
    }

    public function test_journal_service_uses_the_journal_accounting_integration_layer(): void
    {
        $dependency=(new \ReflectionMethod(JournalService::class, '__construct'))->getParameters()[0]->getType()?->getName();
        $this->assertSame(\App\Services\Accounting\Integrations\JournalAccountingIntegrationService::class,$dependency);
        $this->assertNotSame(\App\Services\Accounting\AccountingPostingService::class,$dependency);
    }

    private function service():JournalService{return app(JournalService::class);}
    private function approved(array $lines):Journal{$j=$this->service()->createDraft($this->payload($lines),1,1);$j=$this->service()->submit($j,1);return $this->service()->approve($j,2);}
    private function approvedForHttp():Journal{$j=$this->service()->createDraft($this->payload($this->lines()),1,2);$j=$this->service()->submit($j,2);return $this->service()->approve($j,3);}
    private function payload(array $lines):array{return ['financial_year_id'=>1,'journal_date'=>'2026-06-15','journal_type'=>'general','reference_no'=>'REF','description'=>'Manual Journal','remarks'=>'Test','request_key'=>(string)Str::uuid(),'lines'=>$lines];}
    private function lines(string $amount='10.0000'):array{return [['chart_account_id'=>1,'debit'=>$amount,'credit'=>'0'],['chart_account_id'=>3,'debit'=>'0','credit'=>$amount]];}
    private function decimal(mixed $v):string{return number_format((float)$v,4,'.','');}
    private function sum(string $column):string{return $this->decimal(DB::table('accounting_entry_lines')->sum($column));}
    private function counts():array{return [Journal::count(),DB::table('journal_items')->count(),AccountingEntry::count(),DB::table('accounting_entry_lines')->count(),DB::table('account_transactions')->count(),DB::table('customer_transactions')->count(),DB::table('supplier_transactions')->count(),JournalAuditEvent::count()];}
    private function assertReversalRejected(Journal $journal):void{$before=$this->counts();try{$this->service()->reverse($journal,2,'Closed period');$this->fail('Invalid reversal accepted.');}catch(ValidationException|RuntimeException){$this->assertSame($before,$this->counts());}}
    private function resetPostedData():void{foreach(['accounting_entry_lines','accounting_entries','account_transactions','customer_transactions','supplier_transactions','journal_audit_events','journal_items','journals','journal_number_sequences'] as $table)DB::table($table)->delete();DB::table('accounts')->update(['current_balance'=>0]);DB::table('customers')->update(['current_balance'=>0]);DB::table('suppliers')->update(['current_balance'=>0]);}
}
