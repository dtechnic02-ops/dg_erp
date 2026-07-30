<?php

namespace Tests\Feature;

use App\Http\Controllers\Company\ExpenseController;
use App\Models\AccountingEntry;
use App\Models\Expense;
use App\Models\Role;
use App\Models\User;
use App\Services\Accounting\Integrations\ExpenseAccountingIntegrationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class ExpenseAccountingTest extends TestCase
{
    private const COMPANY_ID = 1;
    private const FOREIGN_COMPANY_ID = 2;
    private const FINANCIAL_YEAR_ID = 1;
    private const CASH_ACCOUNT_ID = 100;
    private const BANK_ACCOUNT_ID = 101;
    private const ATM_ACCOUNT_ID = 102;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['accounting_entry_lines', 'accounting_entries', 'account_transactions', 'expenses', 'expense_categories', 'chart_accounts', 'accounts', 'financial_years'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('accounts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('account_type');
            $table->decimal('current_balance', 20, 4)->default(0);
            $table->integer('status')->default(1);
            $table->timestamps();
        });

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

        Schema::create('expense_categories', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });

        (require base_path('database/migrations/2026_07_28_000202_add_chart_account_id_to_expense_categories_table.php'))->up();

        Schema::create('financial_years', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(true);
        });

        Schema::create('expenses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('financial_year_id');
            $table->string('expense_no');
            $table->unsignedBigInteger('expense_category_id');
            $table->unsignedBigInteger('account_id');
            $table->date('expense_date');
            $table->decimal('amount', 20, 4);
            $table->string('reference_no')->nullable();
            $table->text('note')->nullable();
            $table->string('attachment')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->date('cancelled_date')->nullable();
            $table->string('cancel_reason', 500)->nullable();
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
            $table->unsignedBigInteger('journal_item_id')->nullable();
            $table->unsignedBigInteger('reversed_transaction_id')->nullable();
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
            ['id' => self::FINANCIAL_YEAR_ID, 'company_id' => self::COMPANY_ID, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => 1],
            ['id' => 2, 'company_id' => self::FOREIGN_COMPANY_ID, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => 1],
        ]);

        DB::table('accounts')->insert([
            ['id' => self::CASH_ACCOUNT_ID, 'company_id' => self::COMPANY_ID, 'account_type' => 'Cash', 'current_balance' => '1000.0000', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => self::BANK_ACCOUNT_ID, 'company_id' => self::COMPANY_ID, 'account_type' => 'Bank', 'current_balance' => '1000.0000', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => self::ATM_ACCOUNT_ID, 'company_id' => self::COMPANY_ID, 'account_type' => 'ATM', 'current_balance' => '1000.0000', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 103, 'company_id' => self::COMPANY_ID, 'account_type' => 'Other', 'current_balance' => '1000.0000', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 200, 'company_id' => self::FOREIGN_COMPANY_ID, 'account_type' => 'Cash', 'current_balance' => '1000.0000', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        foreach ([self::COMPANY_ID, self::FOREIGN_COMPANY_ID] as $companyId) {
            foreach (['CASH_IN_HAND', 'BANK_ACCOUNTS', 'RENT_EXPENSE', 'UTILITIES_EXPENSE', 'GENERAL_EXPENSE', 'INVENTORY', 'INPUT_TAX_RECEIVABLE', 'ACCOUNTS_PAYABLE', 'ACCOUNTS_RECEIVABLE', 'SALES_REVENUE', 'SERVICE_REVENUE', 'OUTPUT_TAX_PAYABLE', 'PURCHASE_RETURNS', 'SALES_RETURNS'] as $code) {
                DB::table('chart_accounts')->insert([
                    'company_id' => $companyId,
                    'code' => $code,
                    'name' => $code,
                    'account_class' => in_array($code, ['RENT_EXPENSE', 'UTILITIES_EXPENSE', 'GENERAL_EXPENSE'], true) ? 'expense' : 'asset',
                    'system_code' => $code,
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->actingAs($this->companyAdmin());
    }

    public function test_cash_bank_and_atm_expenses_persist_the_required_balanced_entries(): void
    {
        $rent = $this->category('Rent', 'RENT_EXPENSE');

        $cash = $this->storeExpense($rent, self::CASH_ACCOUNT_ID, '100.0000', '2026-06-10');
        $bank = $this->storeExpense($rent, self::BANK_ACCOUNT_ID, '110.0000', '2026-06-11');
        $atm = $this->storeExpense($rent, self::ATM_ACCOUNT_ID, '120.0000', '2026-06-12');

        $this->assertExpenseEntry($cash, 'RENT_EXPENSE', 'CASH_IN_HAND', self::CASH_ACCOUNT_ID, '100.0000');
        $this->assertExpenseEntry($bank, 'RENT_EXPENSE', 'BANK_ACCOUNTS', self::BANK_ACCOUNT_ID, '110.0000');
        $this->assertExpenseEntry($atm, 'RENT_EXPENSE', 'BANK_ACCOUNTS', self::ATM_ACCOUNT_ID, '120.0000');
    }

    public function test_category_mapping_is_persisted_without_general_expense_fallback(): void
    {
        $rent = $this->storeExpense($this->category('Rent', 'RENT_EXPENSE'), self::CASH_ACCOUNT_ID, '100.0000');
        $utilities = $this->storeExpense($this->category('Utilities', 'UTILITIES_EXPENSE'), self::BANK_ACCOUNT_ID, '150.0000');

        $this->assertExpenseEntry($rent, 'RENT_EXPENSE', 'CASH_IN_HAND', self::CASH_ACCOUNT_ID, '100.0000');
        $this->assertExpenseEntry($utilities, 'UTILITIES_EXPENSE', 'BANK_ACCOUNTS', self::BANK_ACCOUNT_ID, '150.0000');

        foreach ([$rent, $utilities] as $expense) {
            $this->assertSame(0, $this->entryFor($expense->id)->lines()->whereHas('chartAccount', fn ($query) => $query->where('system_code', 'GENERAL_EXPENSE'))->count());
        }
    }

    public function test_invalid_category_and_operational_account_requests_persist_nothing(): void
    {
        $foreignCategory = $this->category('Foreign Rent', 'RENT_EXPENSE', self::FOREIGN_COMPANY_ID);
        $inactiveChart = $this->chart('GENERAL_EXPENSE');
        DB::table('chart_accounts')->where('id', $inactiveChart->id)->update(['status' => 'inactive']);
        $invalidMappedCategory = $this->category('Inactive Mapping', 'GENERAL_EXPENSE');

        $this->invokeStore($foreignCategory->id, self::CASH_ACCOUNT_ID, '100.0000', '2026-06-10');
        $this->invokeStore($invalidMappedCategory->id, self::CASH_ACCOUNT_ID, '100.0000', '2026-06-10');
        $this->invokeStore($this->category('Rent', 'RENT_EXPENSE')->id, 103, '100.0000', '2026-06-10');
        $this->invokeStore($this->category('Rent', 'RENT_EXPENSE')->id, 200, '100.0000', '2026-06-10');

        $this->assertSame(0, DB::table('expenses')->count());
        $this->assertSame(0, DB::table('account_transactions')->count());
        $this->assertSame(0, DB::table('accounting_entries')->count());
        $this->assertSame(0, DB::table('accounting_entry_lines')->count());
    }

    public function test_duplicate_and_model_class_alias_posting_are_blocked_and_reversible(): void
    {
        $expense = $this->storeExpense($this->category('Rent', 'RENT_EXPENSE'), self::CASH_ACCOUNT_ID, '100.0000');

        try {
            $this->integration()->postExpense($expense);
            $this->fail('Duplicate expense accounting posting must be rejected.');
        } catch (RuntimeException $exception) {
            $this->assertSame('An accounting entry has already been posted for this source key.', $exception->getMessage());
        }

        $this->assertSame(1, AccountingEntry::where('company_id', self::COMPANY_ID)->where('source_type', 'expense')->where('source_id', $expense->id)->where('source_event', 'created')->count());
        $this->assertSame(2, DB::table('accounting_entry_lines')->count());

        $otherExpense = $this->createPersistedExpense($this->category('Utilities', 'UTILITIES_EXPENSE'), self::BANK_ACCOUNT_ID, '50.0000');
        $legacy = $this->legacyEntry($otherExpense, Expense::class);

        try {
            $this->integration()->postExpense($otherExpense);
            $this->fail('The model-class source alias must block canonical duplicate posting.');
        } catch (RuntimeException $exception) {
            $this->assertSame('An accounting entry has already been posted for this source key.', $exception->getMessage());
        }

        $this->integration()->reverseExpense($otherExpense, '2026-06-20', 1);
        $this->assertDatabaseHas('accounting_entries', ['reversal_of_id' => $legacy, 'source_type' => 'expense', 'source_event' => 'cancelled']);
    }

    public function test_cancellation_creates_one_exact_reversal_and_business_reversal(): void
    {
        $expense = $this->storeExpense($this->category('Rent', 'RENT_EXPENSE'), self::BANK_ACCOUNT_ID, '100.0000');
        $original = $this->entryFor($expense->id);

        $this->invokeCancel($expense->id, '2026-06-20');

        $reversal = AccountingEntry::where('reversal_of_id', $original->id)->firstOrFail();
        $this->assertSame('reversed', $original->fresh()->status);
        $this->assertSame('expense', $reversal->source_type);
        $this->assertSame('cancelled', $reversal->source_event);
        $this->assertSame(0, Expense::findOrFail($expense->id)->status);
        $this->assertBalanced($reversal);
        $this->assertSame(2, DB::table('account_transactions')->where('reference_id', $expense->id)->count());

        foreach ($original->lines()->orderBy('line_number')->get() as $line) {
            $reversalLine = $reversal->lines()->where('line_number', $line->line_number)->firstOrFail();
            $this->assertSame($line->chart_account_id, $reversalLine->chart_account_id);
            $this->assertSame($line->operational_account_id, $reversalLine->operational_account_id);
            $this->assertSame($line->credit, $reversalLine->debit);
            $this->assertSame($line->debit, $reversalLine->credit);
        }

        $this->invokeCancel($expense->id, '2026-06-20');
        $this->assertSame(1, AccountingEntry::whereNotNull('reversal_of_id')->count());
    }

    public function test_store_and_cancellation_accounting_failures_roll_back_business_records(): void
    {
        $category = $this->category('Rent', 'RENT_EXPENSE');
        DB::table('chart_accounts')->where('company_id', self::COMPANY_ID)->where('system_code', 'CASH_IN_HAND')->delete();
        $this->invokeStore($category->id, self::CASH_ACCOUNT_ID, '100.0000', '2026-06-10');

        $this->assertSame(0, DB::table('expenses')->count());
        $this->assertSame(0, DB::table('account_transactions')->count());
        $this->assertSame('1000.0000', $this->decimal(DB::table('accounts')->where('id', self::CASH_ACCOUNT_ID)->value('current_balance')));

        $this->restoreChart('CASH_IN_HAND');
        $expense = $this->storeExpense($category, self::CASH_ACCOUNT_ID, '100.0000');
        $original = $this->entryFor($expense->id);
        DB::table('accounting_entries')->insert([
            'company_id' => self::COMPANY_ID, 'entry_number' => 'BLOCK-CANCEL', 'entry_date' => '2026-06-20', 'source_module' => 'expense', 'source_type' => 'expense', 'source_id' => 999, 'source_event' => 'cancelled', 'source_key' => 'expense_cancel:' . $expense->id . ':cancelled', 'status' => 'posted', 'posted_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->invokeCancel($expense->id, '2026-06-20');

        $this->assertSame(1, Expense::findOrFail($expense->id)->status);
        $this->assertSame('posted', $original->fresh()->status);
        $this->assertSame(1, DB::table('account_transactions')->where('reference_id', $expense->id)->count());
        $this->assertSame(0, AccountingEntry::where('reversal_of_id', $original->id)->count());
    }

    public function test_financial_year_and_business_dates_are_rejected_without_records(): void
    {
        $category = $this->category('Rent', 'RENT_EXPENSE');
        $this->invokeStore($category->id, self::CASH_ACCOUNT_ID, '100.0000', '2027-01-01');
        $this->assertSame(0, DB::table('expenses')->count());

        $expense = $this->storeExpense($category, self::CASH_ACCOUNT_ID, '100.0000');
        $entry = $this->entryFor($expense->id);
        $this->invokeCancel($expense->id, '2027-01-01');
        $this->assertSame(1, Expense::findOrFail($expense->id)->status);
        $this->assertSame('posted', $entry->fresh()->status);
    }

    public function test_amount_category_and_payment_account_edits_reverse_and_repost_immutable_accounting(): void
    {
        $rent = $this->category('Rent', 'RENT_EXPENSE');
        $utilities = $this->category('Utilities', 'UTILITIES_EXPENSE');
        $expense = $this->storeExpense($rent, self::CASH_ACCOUNT_ID, '300.0000');
        $entry = $this->entryFor($expense->id);

        $this->invokeUpdate($expense->id, $utilities->id, self::BANK_ACCOUNT_ID, '450.00', '2026-06-15');

        $freshExpense = Expense::findOrFail($expense->id);
        $replacement = $this->currentEntryFor($expense->id);
        $reversal = AccountingEntry::where('reversal_of_id', $entry->id)->firstOrFail();

        $this->assertSame('450.0000', $this->decimal($freshExpense->amount));
        $this->assertSame($utilities->id, $freshExpense->expense_category_id);
        $this->assertSame(self::BANK_ACCOUNT_ID, $freshExpense->account_id);
        $this->assertSame('450.0000', $this->decimal(DB::table('account_transactions')->where('reference_id', $expense->id)->value('credit')));
        $this->assertSame('reversed', $entry->fresh()->status);
        $this->assertSame('edited_1', $replacement->source_event);
        $this->assertLine($replacement, 'UTILITIES_EXPENSE', '450.0000', '0.0000');
        $this->assertLine($replacement, 'BANK_ACCOUNTS', '0.0000', '450.0000', self::BANK_ACCOUNT_ID);
        $this->assertBalanced($entry);
        $this->assertBalanced($reversal);
        $this->assertBalanced($replacement);

        try {
            $this->integration()->syncExpenseEdit($freshExpense, 1);
            $this->fail('Repeated unchanged expense accounting synchronization must be rejected.');
        } catch (RuntimeException $exception) {
            $this->assertSame('The current expense accounting entry already represents the persisted expense.', $exception->getMessage());
        }

        $this->assertSame(1, AccountingEntry::where('source_id', $expense->id)->where('status', 'posted')->whereNull('reversal_of_id')->count());
        $this->invokeCancel($expense->id, '2026-06-20');
        $this->assertSame('reversed', $replacement->fresh()->status);
        $this->assertSame($replacement->id, AccountingEntry::where('source_event', 'cancelled')->value('reversal_of_id'));
    }

    public function test_failed_edit_repost_rolls_back_expense_transaction_and_original_accounting(): void
    {
        $rent = $this->category('Rent', 'RENT_EXPENSE');
        $expense = $this->storeExpense($rent, self::CASH_ACCOUNT_ID, '300.0000');
        $original = $this->entryFor($expense->id);
        $balanceBeforeEdit = $this->decimal(DB::table('accounts')->where('id', self::CASH_ACCOUNT_ID)->value('current_balance'));
        DB::table('chart_accounts')->where('company_id', self::COMPANY_ID)->where('system_code', 'BANK_ACCOUNTS')->delete();

        $this->invokeUpdate($expense->id, $rent->id, self::BANK_ACCOUNT_ID, '450.00', '2026-06-15');

        $freshExpense = Expense::findOrFail($expense->id);
        $this->assertSame('300.0000', $this->decimal($freshExpense->amount));
        $this->assertSame(self::CASH_ACCOUNT_ID, $freshExpense->account_id);
        $this->assertSame('300.0000', $this->decimal(DB::table('account_transactions')->where('reference_id', $expense->id)->value('credit')));
        $this->assertSame('posted', $original->fresh()->status);
        $this->assertSame(0, AccountingEntry::where('reversal_of_id', $original->id)->count());
        $this->assertSame($balanceBeforeEdit, $this->decimal(DB::table('accounts')->where('id', self::CASH_ACCOUNT_ID)->value('current_balance')));
    }

    private function companyAdmin(): User
    {
        $user = new User(['name' => 'Company Admin', 'email' => 'admin@example.test', 'role_id' => Role::COMPANY_ADMIN_ID, 'company_id' => self::COMPANY_ID]);
        $user->id = 1;
        $user->exists = true;

        return $user;
    }

    private function category(string $name, string $chartCode, int $companyId = self::COMPANY_ID)
    {
        $chart = $this->chart($chartCode, $companyId);
        $id = DB::table('expense_categories')->insertGetId(['company_id' => $companyId, 'chart_account_id' => $chart->id, 'name' => $name, 'status' => 1, 'created_at' => now(), 'updated_at' => now()]);

        return \App\Models\ExpenseCategory::findOrFail($id);
    }

    private function chart(string $code, int $companyId = self::COMPANY_ID): object
    {
        return DB::table('chart_accounts')->where('company_id', $companyId)->where('system_code', $code)->firstOrFail();
    }

    private function restoreChart(string $code): void
    {
        DB::table('chart_accounts')->insert(['company_id' => self::COMPANY_ID, 'code' => $code . '-RESTORED', 'name' => $code, 'account_class' => 'asset', 'system_code' => $code, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
    }

    private function storeExpense($category, int $accountId, string $amount, string $date = '2026-06-10'): Expense
    {
        $this->invokeStore($category->id, $accountId, $amount, $date);

        $expense = Expense::latest('id')->first();

        if ($expense === null) {
            throw new RuntimeException((string) session('error', 'Expense creation did not persist.'));
        }

        return $expense;
    }

    private function invokeStore(int $categoryId, int $accountId, string $amount, string $date): void
    {
        $request = Request::create('/company/expenses/store', 'POST', ['expense_category_id' => $categoryId, 'account_id' => $accountId, 'amount' => $amount, 'expense_date' => $date]);
        $this->prepareRequest($request);
        app(ExpenseController::class)->store($request);
    }

    private function invokeCancel(int $expenseId, string $date): void
    {
        $request = Request::create('/company/expenses/cancel/' . $expenseId, 'POST', ['cancel_date' => $date, 'cancel_reason' => 'QA cancellation']);
        $this->prepareRequest($request);
        app(ExpenseController::class)->cancel($request, $expenseId);
    }

    private function invokeUpdate(int $expenseId, int $categoryId, int $accountId, string $amount, string $date): void
    {
        $request = Request::create('/company/expenses/update/' . $expenseId, 'POST', [
            'expense_category_id' => $categoryId,
            'account_id' => $accountId,
            'amount' => $amount,
            'expense_date' => $date,
            'note' => 'Updated expense',
        ]);
        $this->prepareRequest($request);
        app(ExpenseController::class)->update($request, $expenseId);
    }

    private function prepareRequest(Request $request): void
    {
        $request->setLaravelSession($this->app['session.store']);
        $this->app->instance('request', $request);
    }

    private function assertExpenseEntry(Expense $expense, string $expenseCode, string $operationalCode, int $operationalAccountId, string $amount): void
    {
        $this->assertDatabaseHas('expenses', ['id' => $expense->id, 'status' => 1]);
        $this->assertSame(1, DB::table('account_transactions')->where('reference_type', 'Expense')->where('reference_id', $expense->id)->count());
        $entry = $this->entryFor($expense->id);
        $this->assertSame('expense', $entry->source_type);
        $this->assertSame($expense->id, $entry->source_id);
        $this->assertSame('created', $entry->source_event);
        $this->assertLine($entry, $expenseCode, $amount, '0.0000');
        $this->assertLine($entry, $operationalCode, '0.0000', $amount, $operationalAccountId);
        $this->assertBalanced($entry);
        $codes = $entry->lines()->with('chartAccount')->get()->pluck('chartAccount.system_code')->all();
        foreach (['INVENTORY', 'INPUT_TAX_RECEIVABLE', 'ACCOUNTS_PAYABLE', 'ACCOUNTS_RECEIVABLE', 'SALES_REVENUE', 'SERVICE_REVENUE', 'OUTPUT_TAX_PAYABLE', 'PURCHASE_RETURNS', 'SALES_RETURNS'] as $forbidden) {
            $this->assertNotContains($forbidden, $codes);
        }
    }

    private function entryFor(int $expenseId): AccountingEntry
    {
        return AccountingEntry::where('company_id', self::COMPANY_ID)->where('source_type', 'expense')->where('source_id', $expenseId)->where('source_event', 'created')->firstOrFail();
    }

    private function currentEntryFor(int $expenseId): AccountingEntry
    {
        return AccountingEntry::where('company_id', self::COMPANY_ID)
            ->where('source_type', 'expense')
            ->where('source_id', $expenseId)
            ->where('status', 'posted')
            ->whereNull('reversal_of_id')
            ->firstOrFail();
    }

    private function assertLine(AccountingEntry $entry, string $code, string $debit, string $credit, ?int $operationalAccountId = null): void
    {
        $line = $entry->lines()->whereHas('chartAccount', fn ($query) => $query->where('system_code', $code))->firstOrFail();
        $this->assertSame($debit, $line->debit);
        $this->assertSame($credit, $line->credit);
        $this->assertSame($operationalAccountId, $line->operational_account_id);
    }

    private function assertBalanced(AccountingEntry $entry): void
    {
        $this->assertSame($this->sumDebits($entry), $this->sumCredits($entry));
    }

    private function sumDebits(AccountingEntry $entry): string
    {
        return $this->decimal($entry->lines()->sum('debit'));
    }

    private function sumCredits(AccountingEntry $entry): string
    {
        return $this->decimal($entry->lines()->sum('credit'));
    }

    private function decimal(mixed $value): string
    {
        return number_format((float) $value, 4, '.', '');
    }

    private function integration(): ExpenseAccountingIntegrationService
    {
        return app(ExpenseAccountingIntegrationService::class);
    }

    private function createPersistedExpense($category, int $accountId, string $amount): Expense
    {
        $id = DB::table('expenses')->insertGetId(['company_id' => self::COMPANY_ID, 'financial_year_id' => self::FINANCIAL_YEAR_ID, 'expense_no' => 'EXP-LEGACY-' . uniqid(), 'expense_category_id' => $category->id, 'account_id' => $accountId, 'expense_date' => '2026-06-10', 'amount' => $amount, 'created_by' => 1, 'status' => 1, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('account_transactions')->insert(['company_id' => self::COMPANY_ID, 'financial_year_id' => self::FINANCIAL_YEAR_ID, 'account_id' => $accountId, 'transaction_date' => '2026-06-10', 'voucher_no' => 'EXP-LEGACY-' . $id, 'reference_type' => 'Expense', 'reference_id' => $id, 'debit' => '0.0000', 'credit' => $amount, 'balance' => '0.0000', 'status' => 1, 'created_at' => now(), 'updated_at' => now()]);

        return Expense::findOrFail($id);
    }

    private function legacyEntry(Expense $expense, ?string $sourceType = null): int
    {
        $entryId = DB::table('accounting_entries')->insertGetId(['company_id' => self::COMPANY_ID, 'entry_number' => 'LEGACY-' . $expense->id, 'entry_date' => '2026-06-10', 'source_module' => 'expense', 'source_type' => $sourceType ?? Expense::class, 'source_id' => $expense->id, 'source_event' => 'created', 'source_key' => 'expense:' . $expense->id . ':created', 'status' => 'posted', 'posted_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        $rent = $this->chart('RENT_EXPENSE');
        DB::table('accounting_entry_lines')->insert(['accounting_entry_id' => $entryId, 'chart_account_id' => $rent->id, 'line_number' => 1, 'debit' => $expense->amount, 'credit' => '0.0000', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('accounting_entry_lines')->insert(['accounting_entry_id' => $entryId, 'chart_account_id' => $this->chart('CASH_IN_HAND')->id, 'operational_account_id' => $expense->account_id, 'line_number' => 2, 'debit' => '0.0000', 'credit' => $expense->amount, 'created_at' => now(), 'updated_at' => now()]);

        return $entryId;
    }
}
