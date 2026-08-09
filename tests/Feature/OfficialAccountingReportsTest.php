<?php

namespace Tests\Feature;

use App\Models\AccountingEntry;
use App\Models\ChartAccount;
use App\Models\Company;
use App\Models\FinancialYear;
use App\Services\Accounting\OfficialAccountingReportService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OfficialAccountingReportsTest extends TestCase
{
    private OfficialAccountingReportService $reports;
    private Company $company;
    private FinancialYear $fy;
    private array $accounts;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createOfficialAccountingSchema();
        $this->reports = app(OfficialAccountingReportService::class);
        $this->company = Company::create(['company_name' => 'Report Co', 'mobile' => '9800000001', 'email' => 'reports@example.test']);
        $this->fy = FinancialYear::create(['company_id' => $this->company->id, 'name' => 'FY 2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true]);
        $this->accounts = [
            'cash' => $this->account('1000', 'Cash', 'asset', 'debit'),
            'equity' => $this->account('3000', 'Equity', 'equity', 'credit'),
            'income' => $this->account('4000', 'Income', 'income', 'credit'),
            'expense' => $this->account('5000', 'Expense', 'expense', 'debit'),
            'zero' => $this->account('1010', 'Zero Asset', 'asset', 'debit'),
        ];
        $this->postEntry('OB-1', '2026-01-01', [['cash', 1000, 0], ['equity', 0, 1000]], 'opening_balance', 'opening_balance', 'posted');
        $sale = $this->postEntry('SALE-1', '2026-01-10', [['cash', 200, 0], ['income', 0, 200]], 'sales', 'sales_invoice', 'posted');
        $this->postEntry('EXP-1', '2026-01-12', [['expense', 50, 0], ['cash', 0, 50]], 'expense', 'expense', 'posted');
        $sale->update(['status' => 'reversed']);
        $this->postEntry('REV-SALE-1', '2026-01-20', [['income', 200, 0], ['cash', 0, 200]], 'sales', 'sales_invoice', 'reversed', $sale->id);
        $this->accounts['income']->update(['status' => 'inactive']);
    }

    public function test_general_ledger_preserves_opening_reversal_history_scope_and_order(): void
    {
        $before = $this->reports->generalLedger($this->company->id, $this->fy->id, $this->accounts['cash']->id, '2026-01-01', '2026-01-15');
        $this->assertSame('1000.0000', $before['opening_balance']);
        $this->assertSame('200.0000', $before['period_debit']);
        $this->assertSame('50.0000', $before['period_credit']);
        $this->assertSame('1150.0000', $before['closing_balance']);
        $this->assertSame(['SALE-1', 'EXP-1'], $before['rows']->pluck('entry_number')->all());

        $after = $this->reports->generalLedger($this->company->id, $this->fy->id, $this->accounts['cash']->id, '2026-01-01', '2026-01-31');
        $this->assertSame('950.0000', $after['closing_balance']);
        $this->assertContains('SALE-1', $after['rows']->pluck('entry_number'));
        $this->assertContains('REV-SALE-1', $after['rows']->pluck('entry_number'));

        $other = Company::create(['company_name' => 'Other', 'mobile' => '9800000002', 'email' => 'other@example.test']);
        $otherFy = FinancialYear::create(['company_id' => $other->id, 'name' => 'FY 2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true]);
        $nextFy = FinancialYear::create(['company_id' => $this->company->id, 'name' => 'FY 2027', 'start_date' => '2027-01-01', 'end_date' => '2027-12-31', 'is_active' => false]);
        $this->postEntry('NEXT-FY', '2027-01-01', [['cash', 500, 0], ['equity', 0, 500]], 'opening_balance', 'opening_balance', 'posted', null, $nextFy->id);
        $this->assertSame('950.0000', $this->reports->generalLedger($this->company->id, $this->fy->id, $this->accounts['cash']->id, '2026-01-01', '2026-12-31')['closing_balance']);
        $this->assertNotSame($this->fy->id, $otherFy->id);
    }

    public function test_trial_balance_is_balanced_hides_zero_and_keeps_inactive_history(): void
    {
        $report = $this->reports->trialBalance($this->company->id, $this->fy->id, '2026-01-01', '2026-01-31');
        $this->assertTrue($report['integrity']);
        $this->assertSame($report['totals']['opening_debit'], $report['totals']['opening_credit']);
        $this->assertSame($report['totals']['period_debit'], $report['totals']['period_credit']);
        $this->assertSame($report['totals']['closing_debit'], $report['totals']['closing_credit']);
        $this->assertTrue($report['rows']->contains(fn ($row) => $row['account']->id === $this->accounts['income']->id));
        $this->assertFalse($report['rows']->contains(fn ($row) => $row['account']->id === $this->accounts['zero']->id));
        $shown = $this->reports->trialBalance($this->company->id, $this->fy->id, '2026-01-01', '2026-01-31', true);
        $this->assertTrue($shown['rows']->contains(fn ($row) => $row['account']->id === $this->accounts['zero']->id));
    }

    public function test_profit_and_loss_classification_profit_and_loss(): void
    {
        $profit = $this->reports->profitAndLoss($this->company->id, $this->fy->id, '2026-01-01', '2026-01-15');
        $this->assertSame('200.0000', $profit['income']);
        $this->assertSame('50.0000', $profit['expense']);
        $this->assertSame('Net Profit', $profit['result']);
        $this->assertSame('150.0000', $profit['net']);
        $this->assertFalse($profit['rows']->contains(fn ($row) => in_array($row['account']->account_class, ['asset', 'liability', 'equity'], true)));

        $loss = $this->reports->profitAndLoss($this->company->id, $this->fy->id, '2026-01-01', '2026-01-31');
        $this->assertSame('Net Loss', $loss['result']);
        $this->assertSame('50.0000', $loss['net']);
    }

    public function test_balance_sheet_derived_result_and_integrity_failures(): void
    {
        $report = $this->reports->balanceSheet($this->company->id, $this->fy->id, '2026-01-01', '2026-01-15');
        $this->assertTrue($report['integrity']);
        $this->assertSame('1150.0000', $report['assets']);
        $this->assertSame('150.0000', $report['current_result']);
        $this->assertFalse($report['rows']->contains(fn ($row) => in_array($row['account']->account_class, ['income', 'expense'], true)));

        $this->postEntry('CLOSE-1', '2026-01-31', [['equity', 50, 0], ['expense', 0, 50]], 'year_end_closing', 'year_end_closing', 'posted');
        $closedPnl = $this->reports->profitAndLoss($this->company->id, $this->fy->id, '2026-01-01', '2026-01-31', true);
        $this->assertSame('0.0000', $closedPnl['net']);
        $closed = $this->reports->balanceSheet($this->company->id, $this->fy->id, '2026-01-01', '2026-01-31');
        $this->assertTrue($closed['integrity']);
        $this->assertSame('0.0000', $closed['current_result']);
        $this->assertSame('950.0000', $closed['equity_with_result']);

        $this->postEntry('BAD-1', '2026-02-01', [['cash', 10, 0]], 'test', 'integrity_probe', 'posted');
        $this->assertFalse($this->reports->trialBalance($this->company->id, $this->fy->id, '2026-01-01', '2026-02-28')['integrity']);
        $this->assertFalse($this->reports->balanceSheet($this->company->id, $this->fy->id, '2026-01-01', '2026-02-28')['integrity']);
    }

    private function account(string $code, string $name, string $class, string $normal): ChartAccount
    {
        return ChartAccount::create(['company_id' => $this->company->id, 'code' => $code, 'name' => $name, 'account_class' => $class, 'normal_balance' => $normal, 'level' => 3, 'sort_order' => (int) $code, 'status' => 'active']);
    }

    private function createOfficialAccountingSchema(): void
    {
        foreach (['accounting_entry_lines', 'accounting_entries', 'chart_accounts', 'financial_years', 'companies'] as $table) Schema::dropIfExists($table);
        Schema::create('companies', function (Blueprint $table): void {
            $table->id(); $table->string('company_name'); $table->string('mobile')->unique(); $table->string('email')->unique(); $table->string('status')->default('active'); $table->timestamps();
        });
        Schema::create('financial_years', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('company_id'); $table->string('name'); $table->date('start_date'); $table->date('end_date'); $table->boolean('is_active')->default(false); $table->timestamps();
        });
        Schema::create('chart_accounts', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('company_id'); $table->unsignedBigInteger('parent_id')->nullable(); $table->string('code'); $table->string('name'); $table->string('account_class'); $table->string('account_category')->nullable(); $table->string('normal_balance'); $table->string('system_code')->nullable(); $table->unsignedTinyInteger('level')->default(1); $table->unsignedInteger('sort_order')->default(0); $table->boolean('is_system')->default(false); $table->boolean('is_control')->default(false); $table->boolean('allow_manual_entry')->default(true); $table->string('status')->default('active'); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('accounting_entries', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('company_id'); $table->unsignedBigInteger('financial_year_id'); $table->string('entry_number'); $table->date('entry_date'); $table->string('reference_number')->nullable(); $table->string('source_module'); $table->string('source_type')->nullable(); $table->unsignedBigInteger('source_id')->nullable(); $table->string('source_event')->nullable(); $table->string('source_key')->nullable(); $table->text('description')->nullable(); $table->string('status'); $table->unsignedBigInteger('reversal_of_id')->nullable(); $table->timestamp('posted_at')->nullable(); $table->unsignedBigInteger('posted_by')->nullable(); $table->unsignedBigInteger('created_by')->nullable(); $table->unsignedBigInteger('updated_by')->nullable(); $table->timestamps();
        });
        Schema::create('accounting_entry_lines', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('accounting_entry_id'); $table->unsignedBigInteger('chart_account_id'); $table->unsignedBigInteger('operational_account_id')->nullable(); $table->unsignedInteger('line_number'); $table->text('description')->nullable(); $table->decimal('debit', 20, 4)->default(0); $table->decimal('credit', 20, 4)->default(0); $table->string('subledger_type')->nullable(); $table->unsignedBigInteger('subledger_id')->nullable(); $table->timestamps();
        });
    }

    private function postEntry(string $number, string $date, array $lines, string $module, string $type, string $event, ?int $reversalOf = null, ?int $financialYearId = null): AccountingEntry
    {
        $entry = AccountingEntry::create(['company_id' => $this->company->id, 'financial_year_id' => $financialYearId ?? $this->fy->id, 'entry_number' => $number, 'entry_date' => $date, 'source_module' => $module, 'source_type' => $type, 'source_event' => $event, 'source_key' => $number, 'status' => 'posted', 'reversal_of_id' => $reversalOf]);
        foreach ($lines as $index => [$account, $debit, $credit]) {
            $entry->lines()->create(['chart_account_id' => $this->accounts[$account]->id, 'line_number' => $index + 1, 'debit' => number_format($debit, 4, '.', ''), 'credit' => number_format($credit, 4, '.', '')]);
        }
        return $entry;
    }
}
