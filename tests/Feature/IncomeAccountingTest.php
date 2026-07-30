<?php

namespace Tests\Feature;

use App\Models\AccountingEntry;
use App\Models\AccountTransaction;
use App\Models\Income;
use App\Services\Accounting\Integrations\IncomeAccountingIntegrationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class IncomeAccountingTest extends TestCase
{
    private const COMPANY_ID = 1;
    private const FOREIGN_COMPANY_ID = 2;
    private const FINANCIAL_YEAR_ID = 1;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['accounting_entry_lines', 'accounting_entries', 'account_transactions', 'incomes', 'income_categories', 'chart_accounts', 'accounts', 'financial_years'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('chart_accounts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('code');
            $table->string('name');
            $table->string('account_class');
            $table->string('system_code')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('income_categories', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->integer('status')->default(1);
            $table->timestamps();
        });

        (require base_path('database/migrations/2026_07_28_000203_add_chart_account_id_to_income_categories_table.php'))->up();

        Schema::create('accounts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('account_type');
            $table->decimal('current_balance', 20, 4)->default(0);
            $table->integer('status')->default(1);
            $table->timestamps();
        });

        Schema::create('financial_years', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(true);
        });

        Schema::create('incomes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('financial_year_id');
            $table->unsignedBigInteger('income_category_id');
            $table->string('income_no');
            $table->string('title');
            $table->unsignedBigInteger('account_id');
            $table->decimal('amount', 20, 4);
            $table->date('income_date');
            $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->integer('status')->default(1);
            $table->timestamps();
        });

        Schema::create('account_transactions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('financial_year_id');
            $table->unsignedBigInteger('account_id');
            $table->date('transaction_date');
            $table->string('voucher_no');
            $table->string('reference_type');
            $table->unsignedBigInteger('reference_id');
            $table->text('description')->nullable();
            $table->decimal('debit', 20, 4)->default(0);
            $table->decimal('credit', 20, 4)->default(0);
            $table->decimal('balance', 20, 4)->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->integer('status')->default(1);
            $table->timestamps();
        });

        Schema::create('accounting_entries', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('entry_number');
            $table->date('entry_date');
            $table->string('reference_number')->nullable();
            $table->string('source_module');
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('source_event')->nullable();
            $table->string('source_key')->nullable();
            $table->text('description')->nullable();
            $table->string('status');
            $table->unsignedBigInteger('reversal_of_id')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'source_key']);
        });

        Schema::create('accounting_entry_lines', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('accounting_entry_id');
            $table->unsignedBigInteger('chart_account_id');
            $table->unsignedBigInteger('operational_account_id')->nullable();
            $table->unsignedInteger('line_number');
            $table->text('description')->nullable();
            $table->decimal('debit', 20, 4)->default(0);
            $table->decimal('credit', 20, 4)->default(0);
            $table->string('subledger_type')->nullable();
            $table->unsignedBigInteger('subledger_id')->nullable();
            $table->timestamps();
        });

        DB::table('financial_years')->insert([
            ['id' => 1, 'company_id' => 1, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => 1],
            ['id' => 2, 'company_id' => 2, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => 1],
        ]);

        foreach ([1, 2] as $companyId) {
            $this->chart($companyId, '1110', 'asset', 'CASH_IN_HAND');
            $this->chart($companyId, '1120', 'asset', 'BANK_ACCOUNTS');
        }
    }

    public function test_cash_bank_atm_and_wallet_income_post_balanced_entries_with_the_exact_category_chart_account(): void
    {
        $incomeChartId = $this->chart(1, '4220', 'income', null);
        $categoryId = $this->category(1, 'Salary and Job Income', $incomeChartId);

        foreach (['Cash' => 'CASH_IN_HAND', 'Bank' => 'BANK_ACCOUNTS', 'ATM' => 'BANK_ACCOUNTS', 'Wallet' => 'BANK_ACCOUNTS'] as $type => $debitCode) {
            $accountId = $this->account(1, $type);
            $income = $this->income($categoryId, $accountId, '100.0000');
            $this->integration()->postIncome($income);

            $entry = $this->entry($income);
            $this->assertSame('income', $entry->source_type);
            $this->assertSame('created', $entry->source_event);
            $this->assertSame('income:' . $income->id . ':created', $entry->source_key);
            $this->assertSame($income->income_no, $entry->reference_number);
            $this->assertSame($income->income_date->format('Y-m-d'), $entry->entry_date->format('Y-m-d'));
            $this->assertLine($entry, $debitCode, '100.0000', '0.0000', $accountId);
            $this->assertLineById($entry, $incomeChartId, '0.0000', '100.0000');
            $this->assertBalanced($entry);
        }
    }

    public function test_category_specific_explicit_chart_accounts_with_null_system_codes_are_used_without_fallback(): void
    {
        $rentalChartId = $this->chart(1, '4221', 'income', null);
        $commissionChartId = $this->chart(1, '4222', 'income', null);
        $cashId = $this->account(1, 'Cash');
        $rental = $this->income($this->category(1, 'Rental', $rentalChartId), $cashId, '150.0000');
        $commission = $this->income($this->category(1, 'Commission', $commissionChartId), $cashId, '250.0000');

        $this->integration()->postIncome($rental);
        $this->integration()->postIncome($commission);

        $this->assertLineById($this->entry($rental), $rentalChartId, '0.0000', '150.0000');
        $this->assertLineById($this->entry($commission), $commissionChartId, '0.0000', '250.0000');
        $this->assertSame(0, $this->entry($rental)->lines()->where('chart_account_id', $commissionChartId)->count());
        $this->assertBalanced($this->entry($rental));
        $this->assertBalanced($this->entry($commission));
    }

    public function test_invalid_category_mapping_and_unsupported_operational_account_are_rejected_without_accounting_entries(): void
    {
        $incomeChartId = $this->chart(1, '4220', 'income', null);
        $categoryId = $this->category(1, 'Salary', $incomeChartId);
        $otherAccountId = $this->account(1, 'Other');
        $income = $this->income($categoryId, $otherAccountId, '100.0000');

        try {
            $this->integration()->postIncome($income);
            $this->fail('Other operational account types must be rejected.');
        } catch (RuntimeException $exception) {
            $this->assertSame('The income operational account is invalid.', $exception->getMessage());
        }

        DB::table('income_categories')->where('id', $categoryId)->update(['chart_account_id' => $this->chart(2, '4220', 'income', null)]);
        $income->refresh()->update(['account_id' => $this->account(1, 'Cash')]);
        AccountTransaction::where('reference_id', $income->id)->update(['account_id' => $income->account_id]);

        try {
            $this->integration()->postIncome($income->fresh());
            $this->fail('Foreign-company category chart accounts must be rejected.');
        } catch (RuntimeException $exception) {
            $this->assertSame('The income category chart account is invalid for this company.', $exception->getMessage());
        }

        $this->assertSame(0, AccountingEntry::count());
        $this->assertSame(0, DB::table('accounting_entry_lines')->count());
    }

    public function test_duplicate_create_uses_canonical_source_identity_and_legacy_alias_reversal_lookup(): void
    {
        $chartId = $this->chart(1, '4220', 'income', null);
        $income = $this->income($this->category(1, 'Salary', $chartId), $this->account(1, 'Cash'), '100.0000');
        $this->integration()->postIncome($income);

        try {
            $this->integration()->postIncome($income);
            $this->fail('Duplicate Income postings must be rejected.');
        } catch (RuntimeException $exception) {
            $this->assertSame('An accounting entry has already been posted for this source key.', $exception->getMessage());
        }

        $entry = $this->entry($income);
        $this->assertSame(1, AccountingEntry::where('source_type', 'income')->where('source_id', $income->id)->where('source_event', 'created')->count());

        $income->update(['status' => Income::STATUS_CANCELLED]);
        $this->integration()->reverseIncome($income, '2026-06-20', 1);
        $reversal = AccountingEntry::where('reversal_of_id', $entry->id)->firstOrFail();
        $this->assertSame('cancelled', $reversal->source_event);
        $this->assertSame($chartId, $reversal->lines()->orderBy('line_number')->skip(1)->firstOrFail()->chart_account_id);
        $this->assertBalanced($reversal);
    }

    public function test_edits_create_immutable_reversal_and_replacement_history_then_cancel_latest_posting(): void
    {
        $firstChartId = $this->chart(1, '4220', 'income', null);
        $secondChartId = $this->chart(1, '4221', 'income', null);
        $cashId = $this->account(1, 'Cash');
        $bankId = $this->account(1, 'Bank');
        $income = $this->income($this->category(1, 'Salary', $firstChartId), $cashId, '300.0000');
        $this->integration()->postIncome($income);
        $original = $this->entry($income);

        $this->synchronizeBusinessEdit($income, $this->category(1, 'Commission', $secondChartId), $bankId, '450.0000', '2026-06-15');
        $income->refresh();
        $this->integration()->syncIncomeEdit($income, 1);

        $replacement = $this->currentEntry($income);
        $this->assertSame('reversed', $original->fresh()->status);
        $this->assertSame('edited_1', $replacement->source_event);
        $this->assertLine($replacement, 'BANK_ACCOUNTS', '450.0000', '0.0000', $bankId);
        $this->assertLineById($replacement, $secondChartId, '0.0000', '450.0000');

        try {
            $this->integration()->syncIncomeEdit($income, 1);
            $this->fail('Unchanged Income synchronization must be rejected.');
        } catch (RuntimeException $exception) {
            $this->assertSame('The current income accounting entry already represents the persisted income.', $exception->getMessage());
        }

        $income->update(['status' => Income::STATUS_CANCELLED]);
        $this->integration()->reverseIncome($income, '2026-06-20', 1);
        $cancellation = AccountingEntry::where('source_event', 'cancelled')->firstOrFail();
        $this->assertSame($replacement->id, $cancellation->reversal_of_id);
        $this->assertSame('reversed', $replacement->fresh()->status);
        $this->assertBalanced($original);
        $this->assertBalanced($replacement);
        $this->assertBalanced($cancellation);
    }

    public function test_missing_system_chart_account_and_invalid_financial_year_reject_posting_without_entries(): void
    {
        $chartId = $this->chart(1, '4220', 'income', null);
        $income = $this->income($this->category(1, 'Salary', $chartId), $this->account(1, 'Cash'), '100.0000');
        DB::table('chart_accounts')->where('company_id', 1)->where('system_code', 'CASH_IN_HAND')->delete();

        try {
            $this->integration()->postIncome($income);
            $this->fail('A missing Cash system account must reject Income accounting.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('CASH_IN_HAND', $exception->getMessage());
        }

        $this->assertSame(0, AccountingEntry::count());
        $this->chart(1, '1110', 'asset', 'CASH_IN_HAND');
        $income->update(['income_date' => '2027-01-01']);
        AccountTransaction::where('reference_id', $income->id)->update(['transaction_date' => '2027-01-01']);

        try {
            $this->integration()->postIncome($income->fresh());
            $this->fail('Income outside its active financial year must be rejected.');
        } catch (RuntimeException $exception) {
            $this->assertSame('The income date does not belong to its active company financial year.', $exception->getMessage());
        }
    }

    public function test_inactive_deleted_wrong_class_and_cross_company_category_chart_accounts_are_rejected_without_posting(): void
    {
        $cashId = $this->account(1, 'Cash');

        foreach ([
            ['company' => 2, 'class' => 'income', 'status' => 'active', 'deleted' => false],
            ['company' => 1, 'class' => 'income', 'status' => 'inactive', 'deleted' => false],
            ['company' => 1, 'class' => 'expense', 'status' => 'active', 'deleted' => false],
            ['company' => 1, 'class' => 'income', 'status' => 'active', 'deleted' => true],
        ] as $index => $case) {
            $chartId = $this->chart($case['company'], '423' . $index, $case['class'], null, $case['status']);

            if ($case['deleted']) {
                DB::table('chart_accounts')->where('id', $chartId)->update(['deleted_at' => now()]);
            }

            $income = $this->income($this->category(1, 'Invalid Mapping ' . $index, $chartId), $cashId, '100.0000');

            try {
                $this->integration()->postIncome($income);
                $this->fail('Invalid category chart accounts must be rejected.');
            } catch (RuntimeException $exception) {
                $this->assertSame('The income category chart account is invalid for this company.', $exception->getMessage());
            }
        }

        $this->assertSame(0, AccountingEntry::count());
        $this->assertSame(0, DB::table('accounting_entry_lines')->count());
    }

    public function test_inactive_or_cross_company_operational_accounts_and_inactive_categories_are_rejected_without_posting(): void
    {
        $chartId = $this->chart(1, '4220', 'income', null);
        $categoryId = $this->category(1, 'Salary', $chartId);
        $inactiveAccountId = $this->account(1, 'Cash', 0);
        $foreignAccountId = $this->account(2, 'Cash');

        foreach ([$inactiveAccountId, $foreignAccountId] as $accountId) {
            $income = $this->income($categoryId, $accountId, '100.0000');

            try {
                $this->integration()->postIncome($income);
                $this->fail('Invalid operational accounts must be rejected.');
            } catch (RuntimeException $exception) {
                $this->assertSame('The income operational account is invalid.', $exception->getMessage());
            }
        }

        DB::table('income_categories')->where('id', $categoryId)->update(['status' => 0]);
        $income = $this->income($categoryId, $this->account(1, 'Cash'), '100.0000');

        try {
            $this->integration()->postIncome($income);
            $this->fail('Inactive income categories must be rejected.');
        } catch (RuntimeException $exception) {
            $this->assertSame('The income category must be mapped to an income chart account.', $exception->getMessage());
        }

        $this->assertSame(0, AccountingEntry::count());
        $this->assertSame(0, DB::table('accounting_entry_lines')->count());
    }

    public function test_second_edit_preserves_exact_reversal_history_and_the_latest_cancellation_leaves_no_active_posting(): void
    {
        $firstChartId = $this->chart(1, '4220', 'income', null);
        $secondChartId = $this->chart(1, '4221', 'income', null);
        $thirdChartId = $this->chart(1, '4222', 'income', null);
        $income = $this->income($this->category(1, 'Initial', $firstChartId), $this->account(1, 'Cash'), '100.0000');
        $this->integration()->postIncome($income);
        $original = $this->entry($income);

        $this->synchronizeBusinessEdit($income, $this->category(1, 'First Edit', $secondChartId), $this->account(1, 'Bank'), '200.0000', '2026-06-11');
        $this->integration()->syncIncomeEdit($income->fresh(), 1);
        $firstReplacement = $this->currentEntry($income);

        $this->synchronizeBusinessEdit($income, $this->category(1, 'Second Edit', $thirdChartId), $this->account(1, 'ATM'), '300.0000', '2026-06-12');
        $this->integration()->syncIncomeEdit($income->fresh(), 1);

        $secondReversal = AccountingEntry::where('source_event', 'edited_reversed_2')->firstOrFail();
        $secondReplacement = $this->currentEntry($income);
        $this->assertSame('edited_2', $secondReplacement->source_event);
        $this->assertSame($firstReplacement->id, $secondReversal->reversal_of_id);
        $this->assertExactReversal($firstReplacement, $secondReversal);
        $this->assertSame('reversed', $original->fresh()->status);
        $this->assertSame('reversed', $firstReplacement->fresh()->status);

        $income->update(['status' => Income::STATUS_CANCELLED]);
        $this->integration()->reverseIncome($income->fresh(), '2026-06-20', 1);
        $cancellation = AccountingEntry::where('source_event', 'cancelled')->firstOrFail();
        $this->assertSame($secondReplacement->id, $cancellation->reversal_of_id);
        $this->assertExactReversal($secondReplacement, $cancellation);
        $this->assertSame(0, AccountingEntry::where('source_type', 'income')->where('status', 'posted')->whereNull('reversal_of_id')->count());
    }

    private function integration(): IncomeAccountingIntegrationService
    {
        return app(IncomeAccountingIntegrationService::class);
    }

    private function chart(int $companyId, string $code, string $class, ?string $systemCode, string $status = 'active'): int
    {
        return DB::table('chart_accounts')->insertGetId([
            'company_id' => $companyId, 'code' => $code, 'name' => $code, 'account_class' => $class,
            'system_code' => $systemCode, 'status' => $status, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function category(int $companyId, string $name, int $chartAccountId): int
    {
        return DB::table('income_categories')->insertGetId([
            'company_id' => $companyId, 'chart_account_id' => $chartAccountId, 'name' => $name,
            'status' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function account(int $companyId, string $type, int $status = 1): int
    {
        return DB::table('accounts')->insertGetId([
            'company_id' => $companyId, 'account_type' => $type, 'current_balance' => '1000.0000',
            'status' => $status, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function income(int $categoryId, int $accountId, string $amount, string $date = '2026-06-10'): Income
    {
        $income = Income::create([
            'company_id' => 1, 'financial_year_id' => 1, 'income_category_id' => $categoryId,
            'income_no' => 'INC-' . (Income::count() + 1), 'title' => 'Income', 'account_id' => $accountId,
            'amount' => $amount, 'income_date' => $date, 'status' => Income::STATUS_ACTIVE,
        ]);

        AccountTransaction::create([
            'company_id' => 1, 'financial_year_id' => 1, 'account_id' => $accountId, 'transaction_date' => $date,
            'voucher_no' => $income->income_no, 'reference_type' => 'Income', 'reference_id' => $income->id,
            'debit' => $amount, 'credit' => '0.0000', 'balance' => '0.0000', 'status' => 1,
        ]);

        return $income;
    }

    private function synchronizeBusinessEdit(Income $income, int $categoryId, int $accountId, string $amount, string $date): void
    {
        $income->update(['income_category_id' => $categoryId, 'account_id' => $accountId, 'amount' => $amount, 'income_date' => $date]);
        AccountTransaction::where('reference_type', 'Income')->where('reference_id', $income->id)->update([
            'account_id' => $accountId, 'debit' => $amount, 'credit' => '0.0000', 'transaction_date' => $date,
        ]);
    }

    private function entry(Income $income): AccountingEntry
    {
        return AccountingEntry::where('source_type', 'income')->where('source_id', $income->id)->where('source_event', 'created')->firstOrFail();
    }

    private function currentEntry(Income $income): AccountingEntry
    {
        return AccountingEntry::where('source_type', 'income')->where('source_id', $income->id)->where('status', 'posted')->whereNull('reversal_of_id')->firstOrFail();
    }

    private function assertLine(AccountingEntry $entry, string $systemCode, string $debit, string $credit, ?int $operationalAccountId = null): void
    {
        $line = $entry->lines()->whereHas('chartAccount', fn ($query) => $query->where('system_code', $systemCode))->firstOrFail();
        $this->assertSame($debit, $line->debit);
        $this->assertSame($credit, $line->credit);
        $this->assertSame($operationalAccountId, $line->operational_account_id);
    }

    private function assertLineById(AccountingEntry $entry, int $chartAccountId, string $debit, string $credit): void
    {
        $line = $entry->lines()->where('chart_account_id', $chartAccountId)->firstOrFail();
        $this->assertSame($debit, $line->debit);
        $this->assertSame($credit, $line->credit);
    }

    private function assertBalanced(AccountingEntry $entry): void
    {
        $this->assertSame($this->decimal($entry->lines()->sum('debit')), $this->decimal($entry->lines()->sum('credit')));
    }

    private function assertExactReversal(AccountingEntry $original, AccountingEntry $reversal): void
    {
        $originalLines = $original->lines()->orderBy('line_number')->get();
        $reversalLines = $reversal->lines()->orderBy('line_number')->get();

        $this->assertSame($originalLines->count(), $reversalLines->count());

        foreach ($originalLines as $index => $line) {
            $reversalLine = $reversalLines->get($index);
            $this->assertSame($line->chart_account_id, $reversalLine->chart_account_id);
            $this->assertSame($line->operational_account_id, $reversalLine->operational_account_id);
            $this->assertSame($line->debit, $reversalLine->credit);
            $this->assertSame($line->credit, $reversalLine->debit);
        }

        $this->assertBalanced($original);
        $this->assertBalanced($reversal);
    }

    private function decimal(mixed $amount): string
    {
        return number_format((float) $amount, 4, '.', '');
    }
}
