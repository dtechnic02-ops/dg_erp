<?php

namespace Tests\Feature;

use App\Models\LoanAccount;
use App\Models\LoanPayment;
use App\Models\LoanSavingLedger;
use App\Models\AccountTransaction;
use App\Http\Controllers\Company\LoanAccountController;
use App\Services\AccountBalanceService;
use App\Services\Accounting\AccountingPostingService;
use App\Services\Accounting\Integrations\LoanAccountingIntegrationService;
use App\Services\Money;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class LoanAccountingIntegrationTest extends TestCase
{
    private LoanAccountingIntegrationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Schema::dropAllTables();

        Schema::create('accounts', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('company_id'); $table->string('account_type');
            $table->string('account_name'); $table->integer('status')->default(1); $table->decimal('current_balance', 18, 2)->default(0); $table->timestamps();
        });
        Schema::create('chart_accounts', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('company_id'); $table->string('system_code'); $table->string('status')->default('active');
            $table->timestamps(); $table->softDeletes();
        });
        Schema::create('loan_accounts', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('company_id'); $table->unsignedBigInteger('financial_year_id'); $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('party_account_id'); $table->string('loan_no'); $table->string('loan_type'); $table->date('start_date');
            $table->decimal('principal_amount', 18, 2); $table->decimal('remaining_principal', 18, 2); $table->unsignedBigInteger('created_by')->nullable();
            $table->integer('status')->default(1); $table->timestamps();
        });
        Schema::create('loan_payments', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('company_id'); $table->unsignedBigInteger('financial_year_id'); $table->unsignedBigInteger('loan_account_id');
            $table->unsignedBigInteger('account_id')->nullable(); $table->date('payment_date'); $table->string('reference_no');
            $table->decimal('principal_amount', 18, 2)->default(0); $table->decimal('interest_amount', 18, 2)->default(0);
            $table->decimal('fine_amount', 18, 2)->default(0); $table->decimal('saving_amount', 18, 2)->default(0);
            $table->decimal('total_amount', 18, 2); $table->decimal('remaining_principal', 18, 2); $table->unsignedBigInteger('created_by')->nullable();
            $table->integer('status')->default(1); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('loan_saving_ledgers', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('company_id'); $table->unsignedBigInteger('financial_year_id'); $table->unsignedBigInteger('loan_account_id');
            $table->unsignedBigInteger('loan_payment_id')->nullable(); $table->unsignedBigInteger('account_id')->nullable(); $table->string('type');
            $table->decimal('amount', 18, 2); $table->decimal('balance_after', 18, 2); $table->date('date');
            $table->unsignedBigInteger('created_by')->nullable(); $table->integer('status')->default(1); $table->timestamps();
        });
        Schema::create('accounting_entries', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('company_id'); $table->unsignedBigInteger('financial_year_id')->nullable(); $table->string('entry_number');
            $table->date('entry_date'); $table->string('reference_number')->nullable(); $table->string('source_module'); $table->string('source_type');
            $table->unsignedBigInteger('source_id'); $table->string('source_event'); $table->string('source_key'); $table->text('description')->nullable();
            $table->string('status'); $table->unsignedBigInteger('reversal_of_id')->nullable(); $table->timestamp('posted_at')->nullable();
            $table->unsignedBigInteger('posted_by')->nullable(); $table->timestamps(); $table->unique(['company_id', 'source_key']);
        });
        Schema::create('account_transactions', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('company_id'); $table->unsignedBigInteger('financial_year_id');
            $table->unsignedBigInteger('account_id'); $table->date('transaction_date'); $table->string('voucher_no');
            $table->string('reference_type'); $table->unsignedBigInteger('reference_id'); $table->unsignedBigInteger('journal_item_id')->nullable();
            $table->unsignedBigInteger('reversed_transaction_id')->nullable(); $table->text('description')->nullable();
            $table->decimal('debit', 18, 2)->default(0); $table->decimal('credit', 18, 2)->default(0);
            $table->decimal('balance', 18, 2)->default(0); $table->unsignedBigInteger('created_by')->nullable();
            $table->integer('status')->default(1); $table->timestamps();
        });
        Schema::create('accounting_entry_lines', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('accounting_entry_id'); $table->unsignedBigInteger('chart_account_id');
            $table->unsignedBigInteger('operational_account_id')->nullable(); $table->unsignedInteger('line_number'); $table->text('description')->nullable();
            $table->decimal('debit', 20, 4); $table->decimal('credit', 20, 4); $table->string('subledger_type')->nullable();
            $table->unsignedBigInteger('subledger_id')->nullable(); $table->timestamps();
        });

        DB::table('accounts')->insert(['id' => 10, 'company_id' => 1, 'account_type' => 'Cash', 'account_name' => 'Cash', 'status' => 1, 'current_balance' => 10000, 'created_at' => now(), 'updated_at' => now()]);
        foreach (['CASH_IN_HAND','BANK_ACCOUNTS','LOAN_RECEIVABLE','LOAN_PAYABLE','LOAN_INTEREST_EXPENSE','LOAN_FINE_EXPENSE','LOAN_INTEREST_INCOME','LOAN_FINE_INCOME','LOAN_COMPULSORY_SAVING_ASSET'] as $index => $code) {
            DB::table('chart_accounts')->insert(['id' => $index + 1, 'company_id' => 1, 'system_code' => $code, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        }
        $this->service = new LoanAccountingIntegrationService(new AccountingPostingService());
    }

    public function test_loan_taken_and_given_creation_post_balanced_control_accounts(): void
    {
        $taken = $this->loan(1, LoanAccount::TYPE_TAKEN, '100.00');
        $given = $this->loan(2, LoanAccount::TYPE_GIVEN, '75.25');
        $this->service->postLoanCreation($taken);
        $this->service->postLoanCreation($given);
        $this->assertEntryBalanced(1);
        $this->assertEntryBalanced(2);
        $this->assertLine(1, 'CASH_IN_HAND', '100.0000', '0.0000');
        $this->assertLine(1, 'LOAN_PAYABLE', '0.0000', '100.0000');
        $this->assertLine(2, 'LOAN_RECEIVABLE', '75.2500', '0.0000');
        $this->assertLine(2, 'CASH_IN_HAND', '0.0000', '75.2500');
    }

    public function test_loan_taken_account_payment_separates_all_components_and_saving_asset(): void
    {
        $loan = $this->loan(1, LoanAccount::TYPE_TAKEN, '1000.00');
        $payment = $this->payment(1, $loan, '100.00', '10.00', '2.00', '25.00', '137.00', true);
        $this->service->postPayment($payment);
        $this->assertEntryBalanced(1);
        $this->assertLine(1, 'LOAN_PAYABLE', '100.0000', '0.0000');
        $this->assertLine(1, 'LOAN_INTEREST_EXPENSE', '10.0000', '0.0000');
        $this->assertLine(1, 'LOAN_FINE_EXPENSE', '2.0000', '0.0000');
        $this->assertLine(1, 'LOAN_COMPULSORY_SAVING_ASSET', '25.0000', '0.0000');
        $this->assertLine(1, 'CASH_IN_HAND', '0.0000', '137.0000');
    }

    public function test_loan_given_recovery_posts_income_and_rejects_saving(): void
    {
        $loan = $this->loan(1, LoanAccount::TYPE_GIVEN, '500.00');
        $payment = $this->payment(1, $loan, '100.00', '8.00', '2.00', '0.00', '110.00', true);
        $this->service->postPayment($payment);
        $this->assertLine(1, 'CASH_IN_HAND', '110.0000', '0.0000');
        $this->assertLine(1, 'LOAN_RECEIVABLE', '0.0000', '100.0000');
        $this->assertLine(1, 'LOAN_INTEREST_INCOME', '0.0000', '8.0000');
        $this->assertLine(1, 'LOAN_FINE_INCOME', '0.0000', '2.0000');

        $bad = $this->payment(2, $loan, '5.00', '0.00', '0.00', '1.00', '6.00', true);
        $this->expectException(RuntimeException::class);
        $this->service->postPayment($bad);
    }

    public function test_saving_funded_loan_taken_payment_has_no_operational_account_line(): void
    {
        $loan = $this->loan(1, LoanAccount::TYPE_TAKEN, '500.00');
        $payment = $this->payment(1, $loan, '40.00', '5.00', '1.00', '0.00', '46.00', false);
        DB::table('loan_saving_ledgers')->insert(['company_id' => 1, 'financial_year_id' => 1, 'loan_account_id' => 1, 'loan_payment_id' => 1, 'account_id' => 10, 'type' => LoanSavingLedger::TYPE_LOAN_SETTLEMENT, 'amount' => 46, 'balance_after' => 54, 'date' => '2026-01-10', 'status' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $this->service->postPayment($payment->fresh(['loanAccount', 'account', 'savingLedgers']));
        $this->assertDatabaseCount('accounting_entry_lines', 4);
        $this->assertSame(0, DB::table('accounting_entry_lines')->whereNotNull('operational_account_id')->count());
        $this->assertLine(1, 'LOAN_COMPULSORY_SAVING_ASSET', '0.0000', '46.0000');
    }

    public function test_saving_withdrawal_increases_cash_and_credits_asset(): void
    {
        $loan = $this->loan(1, LoanAccount::TYPE_TAKEN, '500.00');
        $id = DB::table('loan_saving_ledgers')->insertGetId(['company_id' => 1, 'financial_year_id' => 1, 'loan_account_id' => $loan->id, 'account_id' => 10, 'type' => LoanSavingLedger::TYPE_WITHDRAW, 'amount' => 20, 'balance_after' => 80, 'date' => '2026-01-11', 'status' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $this->service->postSavingWithdrawal(LoanSavingLedger::findOrFail($id));
        $this->assertLine(1, 'CASH_IN_HAND', '20.0000', '0.0000');
        $this->assertLine(1, 'LOAN_COMPULSORY_SAVING_ASSET', '0.0000', '20.0000');
    }

    public function test_duplicate_posting_and_double_reversal_are_rejected(): void
    {
        $loan = $this->loan(1, LoanAccount::TYPE_TAKEN, '100.00');
        $this->service->postLoanCreation($loan);
        $this->service->reverse('loan_account', 1, 1, 1, '2026-01-12', LoanAccount::EVENT_CREATED, 'L-1', null);
        $this->expectException(RuntimeException::class);
        $this->service->reverse('loan_account', 1, 1, 1, '2026-01-12', LoanAccount::EVENT_CREATED, 'L-1', null);
    }

    public function test_decimal_rounding_boundaries_are_exact_and_principal_limits_compare_safely(): void
    {
        $this->assertSame('0.30', Money::add('0.10', '0.20'));
        $this->assertSame('999999999.99', Money::subtract('1000000000.00', '0.01'));
        $this->assertSame(1, Money::compare('10.01', '10.00'));
    }

    public function test_cash_movement_has_exactly_one_source_linked_transaction_and_one_reversal(): void
    {
        $original = AccountBalanceService::createTransaction([
            'company_id' => 1, 'financial_year_id' => 1, 'account_id' => 10,
            'transaction_date' => '2026-01-10', 'voucher_no' => 'L-1',
            'reference_type' => 'LoanAccount', 'reference_id' => 1,
            'debit' => '100.00', 'credit' => '0.00',
        ]);
        $reversal = AccountBalanceService::reverseTransaction($original, 'loan_account_cancel', 'cancel', '2026-01-11', 1);

        $this->assertDatabaseCount('account_transactions', 2);
        $this->assertSame($original->id, (int) $reversal->reversed_transaction_id);
        $this->assertSame('0.00', number_format((float) DB::table('accounts')->where('id', 10)->value('current_balance'), 2, '.', ''));

        $this->expectException(RuntimeException::class);
        AccountBalanceService::reverseTransaction($original, 'loan_account_cancel', 'cancel twice', '2026-01-11', 1);
    }

    public function test_insufficient_cash_rejects_transaction_without_partial_balance_or_record(): void
    {
        DB::table('accounts')->where('id', 10)->update(['current_balance' => 0]);

        try {
            AccountBalanceService::createTransaction([
                'company_id' => 1, 'financial_year_id' => 1, 'account_id' => 10,
                'transaction_date' => '2026-01-10', 'voucher_no' => 'P-1',
                'reference_type' => 'LoanPayment', 'reference_id' => 1,
                'debit' => '0.00', 'credit' => '0.01',
            ]);
            $this->fail('Insufficient Cash/Bank must be rejected.');
        } catch (\Exception $exception) {
            $this->assertStringContainsString('Insufficient account balance', $exception->getMessage());
        }

        $this->assertDatabaseCount('account_transactions', 0);
        $this->assertSame('0.00', number_format((float) DB::table('accounts')->where('id', 10)->value('current_balance'), 2, '.', ''));
    }

    public function test_missing_or_duplicate_original_cash_transaction_fails_strict_loan_resolution(): void
    {
        $loan = $this->loan(1, LoanAccount::TYPE_TAKEN, '100.00');
        $method = new \ReflectionMethod(LoanAccountController::class, 'strictOriginalAccountTransaction');
        $controller = new LoanAccountController();

        try {
            $method->invoke($controller, $loan, 1);
            $this->fail('Missing original transaction must fail.');
        } catch (\ReflectionException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            $this->assertStringContainsString('exactly one active original', $exception->getMessage());
        }

        foreach ([1, 2] as $id) {
            DB::table('account_transactions')->insert([
                'id' => $id, 'company_id' => 1, 'financial_year_id' => 1, 'account_id' => 10,
                'transaction_date' => '2026-01-01', 'voucher_no' => 'L-1-' . $id,
                'reference_type' => 'LoanAccount', 'reference_id' => 1, 'debit' => 100, 'credit' => 0,
                'balance' => 100, 'status' => 1, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $this->expectException(RuntimeException::class);
        $method->invoke($controller, $loan, 1);
    }

    public function test_inactive_loan_and_cross_company_operational_account_are_rejected_without_posting(): void
    {
        $loan = $this->loan(1, LoanAccount::TYPE_TAKEN, '100.00');
        DB::table('loan_accounts')->where('id', 1)->update(['status' => LoanAccount::STATUS_CANCELLED]);
        $payment = $this->payment(1, $loan, '10.00', '0.00', '0.00', '0.00', '10.00', true);

        try {
            $this->service->postPayment($payment->fresh(['loanAccount', 'account', 'savingLedgers']));
            $this->fail('Inactive Loan payment must be rejected.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('active company Loan', $exception->getMessage());
        }
        $this->assertDatabaseCount('accounting_entries', 0);

        DB::table('loan_accounts')->where('id', 1)->update(['status' => LoanAccount::STATUS_ACTIVE]);
        DB::table('accounts')->where('id', 10)->update(['company_id' => 2]);
        $this->expectException(RuntimeException::class);
        $this->service->postPayment($payment->fresh(['loanAccount', 'account', 'savingLedgers']));
    }

    public function test_original_and_reversal_entries_remain_balanced_and_source_linked(): void
    {
        $loan = $this->loan(1, LoanAccount::TYPE_TAKEN, '123.45');
        $this->service->postLoanCreation($loan);
        $this->service->reverse('loan_account', 1, 1, 1, '2026-01-12', LoanAccount::EVENT_CREATED, 'L-1', null);
        $reversal = DB::table('accounting_entries')->where('reversal_of_id', 1)->first();

        $this->assertEntryBalanced(1);
        $this->assertEntryBalanced($reversal->id);
        $this->assertSame(1, (int) $reversal->reversal_of_id);
        $this->assertSame('reversed', DB::table('accounting_entries')->where('id', 1)->value('status'));
    }

    private function loan(int $id, string $type, string $amount): LoanAccount
    {
        DB::table('loan_accounts')->insert(['id' => $id, 'company_id' => 1, 'financial_year_id' => 1, 'account_id' => 10, 'party_account_id' => 20, 'loan_no' => 'L-' . $id, 'loan_type' => $type, 'start_date' => '2026-01-01', 'principal_amount' => $amount, 'remaining_principal' => $amount, 'status' => 1, 'created_at' => now(), 'updated_at' => now()]);
        return LoanAccount::with('account')->findOrFail($id);
    }

    private function payment(int $id, LoanAccount $loan, string $principal, string $interest, string $fine, string $saving, string $total, bool $accountFunded): LoanPayment
    {
        DB::table('loan_payments')->insert(['id' => $id, 'company_id' => 1, 'financial_year_id' => 1, 'loan_account_id' => $loan->id, 'account_id' => $accountFunded ? 10 : null, 'payment_date' => '2026-01-10', 'reference_no' => 'P-' . $id, 'principal_amount' => $principal, 'interest_amount' => $interest, 'fine_amount' => $fine, 'saving_amount' => $saving, 'total_amount' => $total, 'remaining_principal' => Money::subtract($loan->remaining_principal, $principal), 'status' => 1, 'created_at' => now(), 'updated_at' => now()]);
        return LoanPayment::with(['loanAccount', 'account', 'savingLedgers'])->findOrFail($id);
    }

    private function assertEntryBalanced(int $entryId): void
    {
        $debit = DB::table('accounting_entry_lines')->where('accounting_entry_id', $entryId)->sum('debit');
        $credit = DB::table('accounting_entry_lines')->where('accounting_entry_id', $entryId)->sum('credit');
        $this->assertSame(number_format((float) $debit, 4, '.', ''), number_format((float) $credit, 4, '.', ''));
    }

    private function assertLine(int $entryId, string $systemCode, string $debit, string $credit): void
    {
        $line = DB::table('accounting_entry_lines')->join('chart_accounts', 'chart_accounts.id', '=', 'accounting_entry_lines.chart_account_id')
            ->where('accounting_entry_id', $entryId)->where('system_code', $systemCode)->where('debit', $debit)->where('credit', $credit)->first();
        $this->assertNotNull($line, "Missing {$systemCode} {$debit}/{$credit} line.");
    }
}
