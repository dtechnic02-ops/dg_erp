<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('employee_payments')) {
            Schema::table('employee_payments', function (Blueprint $table) {
                $indexes = collect(DB::select('SHOW INDEX FROM employee_payments'))
                    ->pluck('Key_name')
                    ->unique()
                    ->values()
                    ->all();

                if (in_array('employee_payments_voucher_no_unique', $indexes, true)) {
                    $table->dropUnique('employee_payments_voucher_no_unique');
                }
            });

            Schema::table('employee_payments', function (Blueprint $table) {
                $indexes = collect(DB::select('SHOW INDEX FROM employee_payments'))
                    ->pluck('Key_name')
                    ->unique()
                    ->values()
                    ->all();

                if (!in_array('employee_payments_company_voucher_unique', $indexes, true)) {
                    $table->unique(
                        ['company_id', 'voucher_no'],
                        'employee_payments_company_voucher_unique'
                    );
                }
            });

            $nullSalarySheetCount = (int) DB::table('employee_payments')
                ->whereNull('salary_sheet_id')
                ->count();

            if ($nullSalarySheetCount === 0 && Schema::hasColumn('employee_payments', 'salary_sheet_id')) {
                Schema::table('employee_payments', function (Blueprint $table) {
                    $foreignKeys = collect(DB::select(
                        "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
                         WHERE TABLE_SCHEMA = DATABASE()
                         AND TABLE_NAME = 'employee_payments'
                         AND CONSTRAINT_TYPE = 'FOREIGN KEY'"
                    ))->pluck('CONSTRAINT_NAME')->all();

                    if (in_array('employee_payments_salary_sheet_id_foreign', $foreignKeys, true)) {
                        $table->dropForeign('employee_payments_salary_sheet_id_foreign');
                    }
                });

                DB::statement('ALTER TABLE employee_payments MODIFY salary_sheet_id BIGINT UNSIGNED NOT NULL');

                Schema::table('employee_payments', function (Blueprint $table) {
                    $table->foreign('salary_sheet_id', 'employee_payments_salary_sheet_id_foreign')
                        ->references('id')
                        ->on('salary_sheets')
                        ->restrictOnDelete();
                });
            }
        }

        if (Schema::hasTable('salary_sheets')) {
            Schema::table('salary_sheets', function (Blueprint $table) {
                $indexes = collect(DB::select('SHOW INDEX FROM salary_sheets'))
                    ->pluck('Key_name')
                    ->unique()
                    ->values()
                    ->all();

                if (in_array('salary_unique', $indexes, true)) {
                    $table->dropUnique('salary_unique');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('salary_sheets')) {
            Schema::table('salary_sheets', function (Blueprint $table) {
                $indexes = collect(DB::select('SHOW INDEX FROM salary_sheets'))
                    ->pluck('Key_name')
                    ->unique()
                    ->values()
                    ->all();

                if (!in_array('salary_unique', $indexes, true)) {
                    $table->unique(
                        ['company_id', 'financial_year_id', 'employee_id', 'salary_month'],
                        'salary_unique'
                    );
                }
            });
        }

        if (Schema::hasTable('employee_payments')) {
            Schema::table('employee_payments', function (Blueprint $table) {
                $indexes = collect(DB::select('SHOW INDEX FROM employee_payments'))
                    ->pluck('Key_name')
                    ->unique()
                    ->values()
                    ->all();

                if (in_array('employee_payments_company_voucher_unique', $indexes, true)) {
                    $table->dropUnique('employee_payments_company_voucher_unique');
                }
            });

            Schema::table('employee_payments', function (Blueprint $table) {
                $indexes = collect(DB::select('SHOW INDEX FROM employee_payments'))
                    ->pluck('Key_name')
                    ->unique()
                    ->values()
                    ->all();

                if (!in_array('employee_payments_voucher_no_unique', $indexes, true)) {
                    $table->unique('voucher_no', 'employee_payments_voucher_no_unique');
                }
            });

            if (Schema::hasColumn('employee_payments', 'salary_sheet_id')) {
                Schema::table('employee_payments', function (Blueprint $table) {
                    $foreignKeys = collect(DB::select(
                        "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
                         WHERE TABLE_SCHEMA = DATABASE()
                         AND TABLE_NAME = 'employee_payments'
                         AND CONSTRAINT_TYPE = 'FOREIGN KEY'"
                    ))->pluck('CONSTRAINT_NAME')->all();

                    if (in_array('employee_payments_salary_sheet_id_foreign', $foreignKeys, true)) {
                        $table->dropForeign('employee_payments_salary_sheet_id_foreign');
                    }
                });

                DB::statement('ALTER TABLE employee_payments MODIFY salary_sheet_id BIGINT UNSIGNED NULL');

                Schema::table('employee_payments', function (Blueprint $table) {
                    $table->foreign('salary_sheet_id', 'employee_payments_salary_sheet_id_foreign')
                        ->references('id')
                        ->on('salary_sheets')
                        ->nullOnDelete();
                });
            }
        }
    }
};
