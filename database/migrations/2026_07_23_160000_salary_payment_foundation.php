<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salary_sheets', function (Blueprint $table) {
            if (!Schema::hasColumn('salary_sheets', 'paid_amount')) {
                $table->decimal('paid_amount', 15, 2)->default(0)->after('net_salary');
            }

            if (!Schema::hasColumn('salary_sheets', 'due_amount')) {
                $table->decimal('due_amount', 15, 2)->default(0)->after('paid_amount');
            }
        });

        Schema::table('employee_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('employee_payments', 'salary_sheet_id')) {
                $table->unsignedBigInteger('salary_sheet_id')->nullable()->after('employee_account_id');
                $table->index('salary_sheet_id', 'employee_payments_salary_sheet_id_index');
            }

            if (!Schema::hasColumn('employee_payments', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            }

            if (!Schema::hasColumn('employee_payments', 'cancelled_by')) {
                $table->unsignedBigInteger('cancelled_by')->nullable()->after('updated_by');
            }

            if (!Schema::hasColumn('employee_payments', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('cancelled_by');
            }

            if (!Schema::hasColumn('employee_payments', 'cancel_reason')) {
                $table->text('cancel_reason')->nullable()->after('cancelled_at');
            }
        });

        DB::table('employee_payments')
            ->orderBy('id')
            ->chunkById(100, function ($payments) {
                foreach ($payments as $payment) {
                    if ($payment->salary_sheet_id) {
                        continue;
                    }

                    $salarySheet = DB::table('salary_sheets')
                        ->where('company_id', $payment->company_id)
                        ->where('employee_id', $payment->employee_account_id)
                        ->where('salary_month', sprintf('%04d-%02d', $payment->salary_year, $payment->salary_month))
                        ->first();

                    if ($salarySheet) {
                        DB::table('employee_payments')
                            ->where('id', $payment->id)
                            ->update(['salary_sheet_id' => $salarySheet->id]);
                    }
                }
            });

        DB::table('salary_sheets')
            ->orderBy('id')
            ->chunkById(100, function ($sheets) {
                foreach ($sheets as $sheet) {
                    if ((string) $sheet->status === 'cancelled') {
                        continue;
                    }

                    $paidAmount = round((float) DB::table('employee_payments')
                        ->where('salary_sheet_id', $sheet->id)
                        ->where('status', 1)
                        ->sum('amount'), 2);

                    $netSalary = round((float) $sheet->net_salary, 2);
                    $dueAmount = max(0, round($netSalary - $paidAmount, 2));

                    if ($paidAmount <= 0) {
                        $status = 'unpaid';
                    } elseif ($paidAmount >= $netSalary) {
                        $status = 'paid';
                    } else {
                        $status = 'partial';
                    }

                    DB::table('salary_sheets')
                        ->where('id', $sheet->id)
                        ->update([
                            'paid_amount' => $paidAmount,
                            'due_amount' => $dueAmount,
                            'status' => $status,
                        ]);
                }
            });

        Schema::table('employee_payments', function (Blueprint $table) {
            if (Schema::hasColumn('employee_payments', 'salary_sheet_id')) {
                $table->foreign('salary_sheet_id', 'employee_payments_salary_sheet_id_foreign')
                    ->references('id')
                    ->on('salary_sheets')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('employee_payments', function (Blueprint $table) {
            if (Schema::hasColumn('employee_payments', 'salary_sheet_id')) {
                $table->dropForeign('employee_payments_salary_sheet_id_foreign');
                $table->dropIndex('employee_payments_salary_sheet_id_index');
                $table->dropColumn('salary_sheet_id');
            }

            foreach (['cancel_reason', 'cancelled_at', 'cancelled_by', 'updated_by'] as $column) {
                if (Schema::hasColumn('employee_payments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('salary_sheets', function (Blueprint $table) {
            foreach (['due_amount', 'paid_amount'] as $column) {
                if (Schema::hasColumn('salary_sheets', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
