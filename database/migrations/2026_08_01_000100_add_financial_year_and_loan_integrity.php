<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const SEED_TRACKER = 'loan_integrity_seeded_chart_accounts';

    public function up(): void
    {
        $this->preflightLoanData();
        $this->preflightChartMappings();

        Schema::table('accounting_entries', function (Blueprint $table) {
            $table->unsignedBigInteger('financial_year_id')->nullable()->after('company_id');
            $table->index(['company_id', 'financial_year_id', 'entry_date'], 'ae_company_fy_date_idx');
        });

        DB::table('accounting_entries')->orderBy('id')->chunkById(100, function ($entries) {
            foreach ($entries as $entry) {
                $financialYearId = DB::table('financial_years')
                    ->where('company_id', $entry->company_id)
                    ->whereDate('start_date', '<=', $entry->entry_date)
                    ->whereDate('end_date', '>=', $entry->entry_date)
                    ->value('id');
                if ($financialYearId) {
                    DB::table('accounting_entries')->where('id', $entry->id)->update(['financial_year_id' => $financialYearId]);
                }
            }
        });

        Schema::create(self::SEED_TRACKER, function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chart_account_id')->unique();
            $table->unsignedBigInteger('company_id');
            $table->string('system_code');
        });

        foreach (DB::table('companies')->orderBy('id')->get() as $company) {
            foreach ($this->definitions() as [$code, $name, $class, $category, $normal, $systemCode, $parentCode]) {
                $existing = DB::table('chart_accounts')
                    ->where('company_id', $company->id)
                    ->where('system_code', $systemCode)
                    ->first();
                if ($existing) {
                    continue;
                }

                $parentId = DB::table('chart_accounts')
                    ->where('company_id', $company->id)
                    ->where('system_code', $parentCode)
                    ->value('id');
                $id = DB::table('chart_accounts')->insertGetId([
                    'company_id' => $company->id, 'parent_id' => $parentId, 'code' => $code, 'name' => $name,
                    'account_class' => $class, 'account_category' => $category, 'normal_balance' => $normal,
                    'system_code' => $systemCode, 'level' => 3, 'sort_order' => (int) $code,
                    'is_system' => false, 'is_control' => false, 'allow_manual_entry' => false,
                    'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
                ]);
                DB::table(self::SEED_TRACKER)->insert([
                    'chart_account_id' => $id,
                    'company_id' => $company->id,
                    'system_code' => $systemCode,
                ]);
            }
        }
        $this->assertChartMappingsResolved();

        Schema::table('loan_saving_ledgers', function (Blueprint $table) {
            $table->foreignId('cancelled_by')->nullable()->after('updated_by')->constrained('users')->restrictOnDelete();
            $table->date('cancelled_date')->nullable()->after('cancelled_by');
            $table->string('cancel_reason', 500)->nullable()->after('cancelled_date');
        });

        Schema::table('loan_accounts', function (Blueprint $table) {
            $table->string('cancel_reason', 500)->nullable()->after('cancelled_at');
        });
    }

    public function down(): void
    {
        $seededIds = Schema::hasTable(self::SEED_TRACKER)
            ? DB::table(self::SEED_TRACKER)->pluck('chart_account_id')
            : collect();

        if ($seededIds->isNotEmpty()) {
            if (DB::table('accounting_entry_lines')->whereIn('chart_account_id', $seededIds)->exists()) {
                throw new RuntimeException('Cannot rollback Loan Chart Accounts: financial entry lines depend on migration-seeded accounts.');
            }
            if (DB::table('chart_accounts')->whereIn('parent_id', $seededIds)->exists()) {
                throw new RuntimeException('Cannot rollback Loan Chart Accounts: child Chart Accounts depend on migration-seeded accounts.');
            }
            foreach (['income_categories', 'expense_categories'] as $table) {
                if (Schema::hasColumn($table, 'chart_account_id')
                    && DB::table($table)->whereIn('chart_account_id', $seededIds)->exists()) {
                    throw new RuntimeException("Cannot rollback Loan Chart Accounts: {$table} depends on migration-seeded accounts.");
                }
            }
        }

        Schema::table('loan_accounts', fn (Blueprint $table) => $table->dropColumn('cancel_reason'));
        Schema::table('loan_saving_ledgers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cancelled_by');
            $table->dropColumn(['cancelled_date', 'cancel_reason']);
        });
        Schema::table('accounting_entries', function (Blueprint $table) {
            $table->dropIndex('ae_company_fy_date_idx');
            $table->dropColumn('financial_year_id');
        });

        if ($seededIds->isNotEmpty()) {
            DB::table('chart_accounts')->whereIn('id', $seededIds)->delete();
        }
        Schema::dropIfExists(self::SEED_TRACKER);
    }

    private function preflightLoanData(): void
    {
        if (DB::table('loan_payments')->whereNotNull('reference_no')
            ->select('company_id', 'reference_no')->groupBy('company_id', 'reference_no')
            ->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException('Cannot enforce Loan payment idempotency: duplicate company payment references exist.');
        }

        $this->assertNoOrphans('loan_accounts', 'company_id', 'companies');
        $this->assertNoOrphans('loan_accounts', 'party_account_id', 'party_accounts');
        $this->assertNoOrphans('loan_accounts', 'account_id', 'accounts');
        $this->assertNoOrphans('loan_payments', 'company_id', 'companies');
        $this->assertNoOrphans('loan_payments', 'loan_account_id', 'loan_accounts');
        $this->assertNoOrphans('loan_payments', 'account_id', 'accounts', true);
        $this->assertNoOrphans('loan_saving_ledgers', 'company_id', 'companies');
        $this->assertNoOrphans('loan_saving_ledgers', 'loan_account_id', 'loan_accounts');
        $this->assertNoOrphans('loan_saving_ledgers', 'loan_payment_id', 'loan_payments', true);
        $this->assertNoOrphans('loan_saving_ledgers', 'account_id', 'accounts', true);
    }

    private function preflightChartMappings(): void
    {
        foreach (DB::table('companies')->orderBy('id')->get() as $company) {
            foreach ($this->definitions() as [$code, , , , , $systemCode, $parentCode]) {
                $systemMatches = DB::table('chart_accounts')->where('company_id', $company->id)->where('system_code', $systemCode)->get();
                if ($systemMatches->count() > 1) {
                    throw new RuntimeException("Required Chart Account system code [{$systemCode}] is duplicated for company {$company->id}.");
                }
                if ($systemMatches->count() === 1) {
                    if ((string) $systemMatches->first()->code !== $code) {
                        throw new RuntimeException("Required Chart Account system code [{$systemCode}] has unexpected account code for company {$company->id}; expected [{$code}].");
                    }
                    continue;
                }
                $parentCount = DB::table('chart_accounts')->where('company_id', $company->id)->where('system_code', $parentCode)->count();
                if ($parentCount !== 1) {
                    throw new RuntimeException("Required Chart Account parent [{$parentCode}] must resolve exactly once for company {$company->id}.");
                }
                if (DB::table('chart_accounts')->where('company_id', $company->id)->where('code', $code)->exists()) {
                    throw new RuntimeException("Cannot create Chart Account [{$systemCode}] for company {$company->id}: account code [{$code}] is already assigned.");
                }
            }
        }
    }

    private function assertChartMappingsResolved(): void
    {
        foreach (DB::table('companies')->orderBy('id')->get() as $company) {
            foreach ($this->definitions() as [$code, , , , , $systemCode]) {
                $matches = DB::table('chart_accounts')->where('company_id', $company->id)->where('system_code', $systemCode)->get();
                if ($matches->count() !== 1 || (string) $matches->first()->code !== $code) {
                    throw new RuntimeException("Required Chart Account mapping [{$systemCode} => {$code}] is unresolved for company {$company->id}.");
                }
            }
        }
    }

    private function definitions(): array
    {
        return [
            ['1165', 'Loan Compulsory Saving', 'asset', 'loan_deposit_asset', 'debit', 'LOAN_COMPULSORY_SAVING_ASSET', 'CURRENT_ASSETS'],
            ['2160', 'Loan Payable', 'liability', 'loan_payable', 'credit', 'LOAN_PAYABLE', 'CURRENT_LIABILITIES'],
            ['4220', 'Loan Interest Income', 'income', 'loan_interest_income', 'credit', 'LOAN_INTEREST_INCOME', 'OTHER_INCOME'],
            ['4230', 'Loan Fine Income', 'income', 'loan_fine_income', 'credit', 'LOAN_FINE_INCOME', 'OTHER_INCOME'],
            ['5320', 'Loan Interest Expense', 'expense', 'loan_interest_expense', 'debit', 'LOAN_INTEREST_EXPENSE', 'FINANCE_COSTS'],
            ['5330', 'Loan Fine Expense', 'expense', 'loan_fine_expense', 'debit', 'LOAN_FINE_EXPENSE', 'FINANCE_COSTS'],
        ];
    }

    private function assertNoOrphans(string $table, string $column, string $parentTable, bool $nullable = false): void
    {
        $query = DB::table($table . ' as child')->leftJoin($parentTable . ' as parent', 'parent.id', '=', 'child.' . $column)->whereNull('parent.id');
        if ($nullable) {
            $query->whereNotNull('child.' . $column);
        }
        if ($query->exists()) {
            throw new RuntimeException("Cannot add restrictive foreign key: {$table}.{$column} contains orphaned values.");
        }
    }
};
