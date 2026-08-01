<?php

namespace Tests\Feature\Company;

use App\Models\Account;
use App\Models\AccountingEntry;
use App\Models\AccountTransaction;
use App\Models\ChartAccount;
use App\Models\Company;
use App\Models\FinancialYear;
use App\Models\LoanAccount;
use App\Models\LoanPayment;
use App\Models\LoanSavingLedger;
use App\Models\PartyAccount;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesCompanyRouteTestFoundation;
use Tests\TestCase;

class LoanEndToEndIntegrityTest extends TestCase
{
    use CreatesCompanyRouteTestFoundation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createCompanyRouteTestSchema();
        $this->createLoanSchema();
    }

    public function test_insufficient_saving_and_principal_above_remaining_are_atomic(): void
    {
        $context = $this->context();
        $loan = $this->createLoan($context);
        $before = $this->snapshot($loan, $context);

        $this->post(route('company.loan-payment.store'), $this->paymentPayload($context, $loan, [
            'payment_source' => 'saving', 'account_id' => null, 'principal_amount' => '1.00',
        ]))->assertSessionHas('error', 'Insufficient saving balance.');
        $this->assertSnapshot($before, $loan, $context);
        $this->assertDatabaseCount('loan_payments', 0);

        $this->post(route('company.loan-payment.store'), $this->paymentPayload($context, $loan, [
            'principal_amount' => '100.01',
        ]))->assertSessionHas('error', 'Principal exceeds remaining.');
        $this->assertSnapshot($before, $loan, $context);
        $this->assertDatabaseCount('loan_payments', 0);
    }

    public function test_cross_company_loan_party_and_account_are_rejected_without_posting(): void
    {
        $context = $this->context();
        $foreign = Company::create(['company_name' => 'Foreign', 'status' => 'active']);
        $foreignParty = PartyAccount::create($this->partyData($foreign->id, $context['user']->id, 'F-1'));
        $foreignAccount = Account::create(['company_id' => $foreign->id, 'account_type' => 'Cash', 'account_name' => 'Foreign Cash', 'current_balance' => 100, 'status' => 1]);
        $foreignFy = FinancialYear::create(['company_id' => $foreign->id, 'name' => 'Foreign FY', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true]);
        $foreignLoan = LoanAccount::create($this->loanData($foreign->id, $foreignFy->id, $foreignParty->id, $foreignAccount->id, $context['user']->id, 'FOREIGN'));

        foreach ([
            ['party_account_id' => $foreignParty->id],
            ['account_id' => $foreignAccount->id],
        ] as $override) {
            $this->post(route('company.loan-account.store'), array_merge($this->loanPayload($context), $override))->assertSessionHasErrors();
        }

        $this->post(route('company.loan-payment.store'), $this->paymentPayload($context, $foreignLoan))->assertSessionHasErrors();
        $this->assertDatabaseCount('loan_payments', 0);
        $this->assertSame(1, LoanAccount::where('company_id', $foreign->id)->count());
        $this->assertSame(0, AccountingEntry::where('company_id', $context['company']->id)->count());
        $this->assertSame(0, AccountTransaction::where('company_id', $context['company']->id)->count());
    }

    public function test_inactive_financial_year_and_out_of_year_business_date_are_rejected(): void
    {
        $context = $this->context();
        $context['fy']->update(['is_active' => false]);

        $this->post(route('company.loan-account.store'), $this->loanPayload($context))
            ->assertSessionHas('error', 'Please activate financial year first.');
        $this->assertDatabaseCount('loan_accounts', 0);

        $context['fy']->update(['is_active' => true]);
        $this->post(route('company.loan-account.store'), $this->loanPayload($context, ['start_date' => '2027-01-01']))
            ->assertSessionHas('error', 'Loan date must be inside the active financial year.');
        $this->assertDatabaseCount('loan_accounts', 0);
        $this->assertDatabaseCount('account_transactions', 0);
        $this->assertDatabaseCount('accounting_entries', 0);
    }

    public function test_payment_against_cancelled_loan_and_prior_year_cancellation_are_atomic(): void
    {
        $context = $this->context();
        $loan = $this->createLoan($context);
        $loan->update(['status' => LoanAccount::STATUS_CANCELLED]);
        $before = $this->snapshot($loan, $context);

        $this->post(route('company.loan-payment.store'), $this->paymentPayload($context, $loan))
            ->assertSessionHas('error', 'Payment is allowed only for an active eligible Loan.');
        $this->assertSnapshot($before, $loan, $context);

        $loan->update(['status' => LoanAccount::STATUS_ACTIVE]);
        $oldFy = FinancialYear::create(['company_id' => $context['company']->id, 'name' => 'FY 2025', 'start_date' => '2025-01-01', 'end_date' => '2025-12-31', 'is_active' => false]);
        $loan->update(['financial_year_id' => $oldFy->id]);
        $before = $this->snapshot($loan, $context);

        $this->post(route('company.loan-account.cancel', $loan->id), $this->cancelPayload())
            ->assertSessionHas('error', 'Only a Loan in the active financial year may be cancelled.');
        $this->assertSnapshot($before, $loan, $context);
        $this->assertSame(LoanAccount::STATUS_ACTIVE, $loan->fresh()->status);
    }

    public function test_complete_loan_creation_cancellation_reverses_every_effect_once(): void
    {
        $context = $this->context();
        $loan = $this->createLoan($context);

        $this->post(route('company.loan-account.cancel', $loan->id), $this->cancelPayload())->assertSessionHas('success', 'Loan cancelled.');

        $this->assertSame(LoanAccount::STATUS_CANCELLED, $loan->fresh()->status);
        $this->assertMoney('100.00', $loan->fresh()->remaining_principal);
        $this->assertMoney('0.00', $this->savingBalance($loan));
        $this->assertMoney('0.00', $context['account']->fresh()->current_balance);
        $this->assertMoney('0.00', $context['party']->fresh()->current_balance);
        $this->assertFinancialReversal('LoanAccount', $loan->id, 'loan_account', LoanAccount::EVENT_CREATED);
    }

    public function test_complete_loan_payment_cancellation_reverses_every_effect_once(): void
    {
        $context = $this->context();
        $loan = $this->createLoan($context);
        $payment = $this->createPayment($context, $loan, ['principal_amount' => '30.00', 'saving_amount' => '10.00']);

        $this->post(route('company.loan-payment.cancel', $payment->id), $this->cancelPayload())->assertSessionHas('success', 'Payment cancelled successfully.');

        $this->assertSame(LoanPayment::STATUS_CANCELLED, $payment->fresh()->status);
        $this->assertSame(LoanAccount::STATUS_ACTIVE, $loan->fresh()->status);
        $this->assertMoney('100.00', $loan->fresh()->remaining_principal);
        $this->assertMoney('0.00', $this->savingBalance($loan));
        $this->assertMoney('100.00', $context['account']->fresh()->current_balance);
        $this->assertMoney('100.00', $context['party']->fresh()->current_balance);
        $this->assertFinancialReversal('LoanPayment', $payment->id, 'loan_payment', LoanPayment::EVENT_CREATED);
    }

    public function test_complete_saving_withdrawal_cancellation_reverses_every_effect_once(): void
    {
        $context = $this->context();
        $loan = $this->createLoan($context);
        $this->createPayment($context, $loan, ['principal_amount' => '0.00', 'saving_amount' => '50.00']);
        $beforePrincipal = $loan->fresh()->remaining_principal;
        $beforeParty = $context['party']->fresh()->current_balance;

        $this->post(route('company.loan-saving-withdraw.store'), [
            'loan_account_id' => $loan->id, 'financial_year_id' => $context['fy']->id,
            'account_id' => $context['account']->id, 'amount' => '20.00', 'date' => '2026-06-20',
            'request_key' => (string) Str::uuid(),
        ])->assertSessionHas('success', 'Saving withdrawn.');
        $withdrawal = LoanSavingLedger::where('type', LoanSavingLedger::TYPE_WITHDRAW)->sole();

        $this->post(route('company.loan-saving-withdraw.cancel', $withdrawal->id), $this->cancelPayload())
            ->assertSessionHas('success', 'Saving withdrawal cancelled.');

        $this->assertSame(LoanSavingLedger::STATUS_INACTIVE, $withdrawal->fresh()->status);
        $this->assertSame(LoanAccount::STATUS_ACTIVE, $loan->fresh()->status);
        $this->assertMoney($beforePrincipal, $loan->fresh()->remaining_principal);
        $this->assertMoney('50.00', $this->savingBalance($loan));
        $this->assertMoney('50.00', $context['account']->fresh()->current_balance);
        $this->assertMoney($beforeParty, $context['party']->fresh()->current_balance);
        $this->assertFinancialReversal('LoanSavingWithdraw', $withdrawal->id, 'loan_saving_withdrawal', LoanSavingLedger::EVENT_WITHDRAWN);
    }

    public function test_duplicate_request_key_is_rejected_without_duplicate_financial_records(): void
    {
        $context = $this->context();
        $key = (string) Str::uuid();
        $payload = $this->loanPayload($context, ['request_key' => $key]);
        $this->post(route('company.loan-account.store'), $payload)->assertSessionHas('success', 'Loan created.');
        $loan = LoanAccount::sole();
        $before = $this->snapshot($loan, $context);

        $this->post(route('company.loan-account.store'), $payload)
            ->assertSessionHas('error', 'This Loan request has already been processed.');

        $this->assertDatabaseCount('loan_accounts', 1);
        $this->assertSnapshot($before, $loan, $context);
        $this->assertSame(1, AccountTransaction::where('reference_type', 'LoanAccount')->count());
        $this->assertSame(1, AccountingEntry::where('source_type', 'loan_account')->count());
    }

    private function createLoanSchema(): void
    {
        Schema::create('party_accounts', function (Blueprint $t): void {$t->id();$t->unsignedBigInteger('company_id');$t->string('account_no');$t->string('name');$t->string('phone')->nullable();$t->text('address')->nullable();$t->decimal('opening_balance',18,2)->default(0);$t->decimal('current_balance',18,2)->default(0);$t->string('type');$t->string('photo')->nullable();$t->string('id_card')->nullable();$t->string('document')->nullable();$t->text('note')->nullable();$t->date('due_date')->nullable();$t->unsignedBigInteger('created_by');$t->unsignedBigInteger('updated_by')->nullable();$t->unsignedBigInteger('deleted_by')->nullable();$t->integer('status')->default(1);$t->timestamps();$t->softDeletes();});
        Schema::create('loan_accounts', function (Blueprint $t): void {$t->id();$t->unsignedBigInteger('company_id');$t->unsignedBigInteger('financial_year_id');$t->uuid('request_key')->nullable();$t->string('loan_no');$t->string('loan_name');$t->string('loan_type');$t->unsignedBigInteger('party_account_id');$t->unsignedBigInteger('account_id');foreach(['principal_amount','interest_rate','remaining_principal'] as $c)$t->decimal($c,18,2)->default(0);$t->date('start_date');$t->date('end_date')->nullable();$t->date('next_payment_date')->nullable();$t->string('attachment')->nullable();$t->text('note')->nullable();$t->unsignedBigInteger('created_by');$t->unsignedBigInteger('updated_by')->nullable();$t->unsignedBigInteger('cancelled_by')->nullable();$t->timestamp('cancelled_at')->nullable();$t->string('cancel_reason',500)->nullable();$t->integer('status')->default(1);$t->timestamps();$t->unique(['company_id','loan_no']);$t->unique(['company_id','request_key']);});
        Schema::create('loan_payments', function (Blueprint $t): void {$t->id();$t->unsignedBigInteger('company_id');$t->unsignedBigInteger('financial_year_id');$t->unsignedBigInteger('loan_account_id');$t->unsignedBigInteger('account_id')->nullable();$t->date('payment_date');$t->date('next_payment_date')->nullable();foreach(['principal_amount','interest_amount','fine_amount','saving_amount','total_amount','remaining_principal'] as $c)$t->decimal($c,18,2)->default(0);$t->string('reference_no');$t->uuid('request_key')->nullable();$t->string('attachment')->nullable();$t->text('note')->nullable();$t->unsignedBigInteger('created_by');$t->unsignedBigInteger('updated_by')->nullable();$t->unsignedBigInteger('cancelled_by')->nullable();$t->date('cancelled_date')->nullable();$t->string('cancel_reason',500)->nullable();$t->integer('status')->default(1);$t->timestamps();$t->softDeletes();$t->unique(['company_id','reference_no']);$t->unique(['company_id','request_key']);});
        Schema::create('loan_saving_ledgers', function (Blueprint $t): void {$t->id();$t->unsignedBigInteger('company_id');$t->unsignedBigInteger('financial_year_id');$t->unsignedBigInteger('loan_account_id');$t->unsignedBigInteger('loan_payment_id')->nullable();$t->uuid('request_key')->nullable();$t->unsignedBigInteger('account_id')->nullable();$t->string('type');$t->decimal('amount',18,2)->default(0);$t->decimal('balance_after',18,2)->default(0);$t->date('date');$t->string('attachment')->nullable();$t->text('note')->nullable();$t->unsignedBigInteger('created_by');$t->unsignedBigInteger('updated_by')->nullable();$t->unsignedBigInteger('cancelled_by')->nullable();$t->date('cancelled_date')->nullable();$t->string('cancel_reason',500)->nullable();$t->integer('status')->default(1);$t->timestamps();$t->unique(['company_id','request_key']);});
    }

    private function context(): array
    {
        $role = $this->createCompanyDashboardRole(); $company = $this->createActiveCompany(); $user = $this->createCompanyAdmin($company, $role);
        $plan = $this->createActiveSubscriptionPlan(); $this->createOperationalCompanySubscription($company, $plan); $fy = $this->createActiveFinancialYear($company, $user); $this->authenticateCompanyAdmin($user);
        $party = PartyAccount::create($this->partyData($company->id, $user->id, 'P-1'));
        $account = Account::create(['company_id'=>$company->id,'account_type'=>'Cash','account_name'=>'Loan Cash','current_balance'=>0,'status'=>1]);
        $this->createLoanCharts($company, $user);
        return compact('company','user','fy','party','account');
    }

    private function createLoanCharts(Company $company, User $user): void
    {
        foreach (['CASH_IN_HAND','BANK_ACCOUNTS','LOAN_RECEIVABLE','LOAN_PAYABLE','LOAN_INTEREST_EXPENSE','LOAN_FINE_EXPENSE','LOAN_INTEREST_INCOME','LOAN_FINE_INCOME','LOAN_COMPULSORY_SAVING_ASSET'] as $i => $code) {
            ChartAccount::create(['company_id'=>$company->id,'code'=>(string)(1000+$i),'name'=>$code,'account_class'=>str_contains($code,'INCOME')?'income':(str_contains($code,'EXPENSE')?'expense':($code==='LOAN_PAYABLE'?'liability':'asset')),'normal_balance'=>str_contains($code,'INCOME')||$code==='LOAN_PAYABLE'?'credit':'debit','system_code'=>$code,'status'=>'active','created_by'=>$user->id]);
        }
    }

    private function createLoan(array $c): LoanAccount
    {
        $this->post(route('company.loan-account.store'), $this->loanPayload($c))->assertSessionHas('success', 'Loan created.');
        return LoanAccount::sole();
    }

    private function createPayment(array $c, LoanAccount $loan, array $override = []): LoanPayment
    {
        $this->post(route('company.loan-payment.store'), $this->paymentPayload($c, $loan, $override))->assertSessionHas('success', 'Payment saved.');
        return LoanPayment::latest('id')->firstOrFail();
    }

    private function loanPayload(array $c, array $override = []): array
    {
        return array_merge(['financial_year_id'=>$c['fy']->id,'loan_name'=>'Test Loan','loan_type'=>LoanAccount::TYPE_TAKEN,'party_account_id'=>$c['party']->id,'account_id'=>$c['account']->id,'principal_amount'=>'100.00','interest_rate'=>'0.00','start_date'=>'2026-06-15','request_key'=>(string)Str::uuid()], $override);
    }

    private function paymentPayload(array $c, LoanAccount $loan, array $override = []): array
    {
        return array_merge(['loan_account_id'=>$loan->id,'financial_year_id'=>$c['fy']->id,'payment_source'=>'account','account_id'=>$c['account']->id,'payment_date'=>'2026-06-16','principal_amount'=>'10.00','interest_amount'=>'0.00','fine_amount'=>'0.00','saving_amount'=>'0.00','request_key'=>(string)Str::uuid()], $override);
    }

    private function cancelPayload(): array { return ['cancel_date'=>'2026-06-21','cancel_reason'=>'Verified cancellation']; }
    private function partyData(int $companyId, int $userId, string $number): array { return ['company_id'=>$companyId,'account_no'=>$number,'name'=>$number,'opening_balance'=>0,'current_balance'=>0,'type'=>'person','created_by'=>$userId,'status'=>1]; }
    private function loanData(int $companyId, int $fyId, int $partyId, int $accountId, int $userId, string $number): array { return ['company_id'=>$companyId,'financial_year_id'=>$fyId,'request_key'=>(string)Str::uuid(),'loan_no'=>$number,'loan_name'=>$number,'loan_type'=>LoanAccount::TYPE_TAKEN,'party_account_id'=>$partyId,'account_id'=>$accountId,'principal_amount'=>100,'interest_rate'=>0,'remaining_principal'=>100,'start_date'=>'2026-06-15','created_by'=>$userId,'status'=>1]; }
    private function savingBalance(LoanAccount $loan): mixed { return LoanSavingLedger::where('loan_account_id',$loan->id)->where('status',1)->latest('id')->value('balance_after') ?? 0; }
    private function snapshot(LoanAccount $loan, array $c): array { return ['loan_status'=>$loan->fresh()->status,'principal'=>$loan->fresh()->remaining_principal,'saving'=>$this->savingBalance($loan),'cash'=>$c['account']->fresh()->current_balance,'party'=>$c['party']->fresh()->current_balance,'payments'=>LoanPayment::count(),'transactions'=>AccountTransaction::count(),'entries'=>AccountingEntry::count()]; }
    private function assertSnapshot(array $before, LoanAccount $loan, array $c): void { $this->assertSame($before, $this->snapshot($loan,$c)); }
    private function assertMoney(mixed $expected, mixed $actual): void { $this->assertSame(number_format((float)$expected,2,'.',''),number_format((float)$actual,2,'.','')); }

    private function assertFinancialReversal(string $referenceType, int $sourceId, string $accountingType, string $originalEvent): void
    {
        $originalTransactions = AccountTransaction::where('reference_type',$referenceType)->where('reference_id',$sourceId)->whereNull('reversed_transaction_id')->get();
        $this->assertCount(1,$originalTransactions);
        $reversals = AccountTransaction::where('reversed_transaction_id',$originalTransactions->first()->id)->get();
        $this->assertCount(1,$reversals);
        $originalEntry = AccountingEntry::where('source_type',$accountingType)->where('source_id',$sourceId)->where('source_event',$originalEvent)->sole();
        $reversalEntry = AccountingEntry::where('reversal_of_id',$originalEntry->id)->sole();
        $this->assertSame('reversed',$originalEntry->status);
        $this->assertSame('posted',$reversalEntry->status);
        foreach ([$originalEntry,$reversalEntry] as $entry) {
            $debit = DB::table('accounting_entry_lines')->where('accounting_entry_id',$entry->id)->sum('debit');
            $credit = DB::table('accounting_entry_lines')->where('accounting_entry_id',$entry->id)->sum('credit');
            $this->assertMoney($debit,$credit);
        }
    }
}
