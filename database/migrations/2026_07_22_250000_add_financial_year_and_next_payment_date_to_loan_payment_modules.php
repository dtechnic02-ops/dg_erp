<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loan_payments', function (Blueprint $table) {
            $table->foreignId('financial_year_id')->nullable()->after('company_id');
            $table->date('next_payment_date')->nullable()->after('payment_date');
        });

        Schema::table('loan_saving_ledgers', function (Blueprint $table) {
            $table->foreignId('financial_year_id')->nullable()->after('company_id');
        });

        Schema::table('loan_payments', function (Blueprint $table) {
            $table->index('financial_year_id');
            $table->foreign('financial_year_id')
                ->references('id')
                ->on('financial_years');
        });

        Schema::table('loan_saving_ledgers', function (Blueprint $table) {
            $table->index('financial_year_id');
            $table->foreign('financial_year_id')
                ->references('id')
                ->on('financial_years');
        });

        DB::table('loan_payments')
            ->select('id', 'company_id', 'loan_account_id', 'payment_date')
            ->orderBy('id')
            ->chunkById(100, function ($payments) {
                foreach ($payments as $payment) {
                    $financialYearId = DB::table('loan_accounts')
                        ->where('id', $payment->loan_account_id)
                        ->value('financial_year_id');

                    if (!$financialYearId) {
                        $financialYearId = DB::table('financial_years')
                            ->where('company_id', $payment->company_id)
                            ->whereDate('start_date', '<=', $payment->payment_date)
                            ->whereDate('end_date', '>=', $payment->payment_date)
                            ->value('id');
                    }

                    if ($financialYearId) {
                        DB::table('loan_payments')
                            ->where('id', $payment->id)
                            ->update(['financial_year_id' => $financialYearId]);
                    }
                }
            });

        DB::table('loan_saving_ledgers')
            ->select('id', 'company_id', 'loan_account_id', 'loan_payment_id', 'date')
            ->orderBy('id')
            ->chunkById(100, function ($ledgers) {
                foreach ($ledgers as $ledger) {
                    $financialYearId = null;

                    if ($ledger->loan_payment_id) {
                        $financialYearId = DB::table('loan_payments')
                            ->where('id', $ledger->loan_payment_id)
                            ->value('financial_year_id');
                    }

                    if (!$financialYearId) {
                        $financialYearId = DB::table('loan_accounts')
                            ->where('id', $ledger->loan_account_id)
                            ->value('financial_year_id');
                    }

                    if (!$financialYearId) {
                        $financialYearId = DB::table('financial_years')
                            ->where('company_id', $ledger->company_id)
                            ->whereDate('start_date', '<=', $ledger->date)
                            ->whereDate('end_date', '>=', $ledger->date)
                            ->value('id');
                    }

                    if ($financialYearId) {
                        DB::table('loan_saving_ledgers')
                            ->where('id', $ledger->id)
                            ->update(['financial_year_id' => $financialYearId]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('loan_payments', function (Blueprint $table) {
            $table->dropForeign(['financial_year_id']);
            $table->dropIndex(['financial_year_id']);
            $table->dropColumn(['financial_year_id', 'next_payment_date']);
        });

        Schema::table('loan_saving_ledgers', function (Blueprint $table) {
            $table->dropForeign(['financial_year_id']);
            $table->dropIndex(['financial_year_id']);
            $table->dropColumn('financial_year_id');
        });
    }
};
