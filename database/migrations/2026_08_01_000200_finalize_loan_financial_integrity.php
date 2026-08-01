<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->preflight();

        $this->backfill('loan_accounts', 'start_date');
        $this->backfill('loan_payments', 'payment_date');
        $this->backfill('loan_saving_ledgers', 'date');

        foreach (['loan_accounts', 'loan_payments', 'loan_saving_ledgers', 'accounting_entries'] as $table) {
            if (DB::table($table)->whereNull('financial_year_id')->exists()) {
                throw new RuntimeException("Cannot enforce financial-year integrity: unresolved legacy rows remain in {$table}.");
            }
        }

        Schema::table('loan_accounts', function (Blueprint $table) {
            $table->foreignId('financial_year_id')->nullable(false)->change();
            $table->uuid('request_key')->nullable()->after('loan_no');
            $table->unique(['company_id', 'request_key'], 'loan_accounts_company_request_unique');
        });
        Schema::table('loan_payments', function (Blueprint $table) {
            $table->foreignId('financial_year_id')->nullable(false)->change();
            $table->uuid('request_key')->nullable()->after('reference_no');
            $table->unique(['company_id', 'reference_no'], 'loan_payments_company_reference_unique');
            $table->unique(['company_id', 'request_key'], 'loan_payments_company_request_unique');
        });
        Schema::table('loan_saving_ledgers', function (Blueprint $table) {
            $table->foreignId('financial_year_id')->nullable(false)->change();
            $table->uuid('request_key')->nullable()->after('loan_payment_id');
            $table->unique(['company_id', 'request_key'], 'loan_saving_company_request_unique');
        });
        Schema::table('accounting_entries', function (Blueprint $table) {
            $table->foreignId('financial_year_id')->nullable(false)->change();
            $table->foreign('financial_year_id', 'accounting_entries_financial_year_fk')->references('id')->on('financial_years')->restrictOnDelete();
        });

        Schema::table('loan_accounts', function (Blueprint $table) {
            $table->foreign('company_id', 'loan_accounts_company_fk')->references('id')->on('companies')->restrictOnDelete();
            $table->foreign('party_account_id', 'loan_accounts_party_fk')->references('id')->on('party_accounts')->restrictOnDelete();
            $table->foreign('account_id', 'loan_accounts_account_fk')->references('id')->on('accounts')->restrictOnDelete();
        });
        Schema::table('loan_payments', function (Blueprint $table) {
            $table->foreign('company_id', 'loan_payments_company_fk')->references('id')->on('companies')->restrictOnDelete();
            $table->foreign('loan_account_id', 'loan_payments_loan_fk')->references('id')->on('loan_accounts')->restrictOnDelete();
            $table->foreign('account_id', 'loan_payments_account_fk')->references('id')->on('accounts')->restrictOnDelete();
        });
        Schema::table('loan_saving_ledgers', function (Blueprint $table) {
            $table->foreign('company_id', 'loan_saving_company_fk')->references('id')->on('companies')->restrictOnDelete();
            $table->foreign('loan_account_id', 'loan_saving_loan_fk')->references('id')->on('loan_accounts')->restrictOnDelete();
            $table->foreign('loan_payment_id', 'loan_saving_payment_fk')->references('id')->on('loan_payments')->restrictOnDelete();
            $table->foreign('account_id', 'loan_saving_account_fk')->references('id')->on('accounts')->restrictOnDelete();
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE loan_saving_ledgers MODIFY type ENUM('deposit','withdraw','loan_settlement','reversal') NOT NULL");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql' && DB::table('loan_saving_ledgers')->whereIn('type', ['loan_settlement', 'reversal'])->exists()) {
            throw new RuntimeException('Cannot rollback Loan saving type definition while settlement or reversal records exist.');
        }

        Schema::table('accounting_entries', function (Blueprint $table) {
            $table->dropForeign('accounting_entries_financial_year_fk');
        });
        Schema::table('loan_saving_ledgers', function (Blueprint $table) {
            $table->dropForeign('loan_saving_company_fk'); $table->dropForeign('loan_saving_loan_fk');
            $table->dropForeign('loan_saving_payment_fk'); $table->dropForeign('loan_saving_account_fk');
        });
        Schema::table('loan_payments', function (Blueprint $table) {
            $table->dropForeign('loan_payments_company_fk'); $table->dropForeign('loan_payments_loan_fk'); $table->dropForeign('loan_payments_account_fk');
        });
        Schema::table('loan_accounts', function (Blueprint $table) {
            $table->dropForeign('loan_accounts_company_fk'); $table->dropForeign('loan_accounts_party_fk'); $table->dropForeign('loan_accounts_account_fk');
        });
        Schema::table('loan_saving_ledgers', function (Blueprint $table) {
            $table->dropUnique('loan_saving_company_request_unique');
            $table->dropColumn('request_key');
            $table->foreignId('financial_year_id')->nullable()->change();
        });
        Schema::table('loan_payments', function (Blueprint $table) {
            $table->dropUnique('loan_payments_company_reference_unique');
            $table->dropUnique('loan_payments_company_request_unique');
            $table->dropColumn('request_key');
            $table->foreignId('financial_year_id')->nullable()->change();
        });
        Schema::table('loan_accounts', function (Blueprint $table) {
            $table->dropUnique('loan_accounts_company_request_unique');
            $table->dropColumn('request_key');
            $table->foreignId('financial_year_id')->nullable()->change();
        });
        Schema::table('accounting_entries', fn (Blueprint $table) => $table->foreignId('financial_year_id')->nullable()->change());

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE loan_saving_ledgers MODIFY type ENUM('deposit','withdraw') NOT NULL");
        }
    }

    private function preflight(): void
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
        $this->assertNoOrphans('accounting_entries', 'financial_year_id', 'financial_years', true);

        $requiredSystemCodes = [
            'LOAN_COMPULSORY_SAVING_ASSET', 'LOAN_PAYABLE', 'LOAN_INTEREST_INCOME',
            'LOAN_FINE_INCOME', 'LOAN_INTEREST_EXPENSE', 'LOAN_FINE_EXPENSE',
        ];
        foreach (DB::table('companies')->pluck('id') as $companyId) {
            foreach ($requiredSystemCodes as $systemCode) {
                if (DB::table('chart_accounts')->where('company_id', $companyId)->where('system_code', $systemCode)->count() !== 1) {
                    throw new RuntimeException("Cannot finalize Loan accounting: Chart Account [{$systemCode}] must resolve exactly once for company {$companyId}.");
                }
            }
        }
    }

    private function backfill(string $table, string $dateColumn): void
    {
        DB::table($table)->whereNull('financial_year_id')->orderBy('id')->chunkById(100, function ($rows) use ($table, $dateColumn) {
            foreach ($rows as $row) {
                $financialYearId = DB::table('financial_years')->where('company_id', $row->company_id)
                    ->whereDate('start_date', '<=', $row->{$dateColumn})->whereDate('end_date', '>=', $row->{$dateColumn})->value('id');
                if ($financialYearId) {
                    DB::table($table)->where('id', $row->id)->update(['financial_year_id' => $financialYearId]);
                }
            }
        });
    }

    private function assertNoOrphans(string $table, string $column, string $parentTable, bool $nullable = false): void
    {
        $query = DB::table($table . ' as child')->leftJoin($parentTable . ' as parent', 'parent.id', '=', 'child.' . $column)
            ->whereNull('parent.id');
        if ($nullable) {
            $query->whereNotNull('child.' . $column);
        }
        if ($query->exists()) {
            throw new RuntimeException("Cannot add restrictive foreign key: {$table}.{$column} contains orphaned values.");
        }
    }
};
