<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('CREATE TABLE `salary_sheets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned NOT NULL,
  `employee_id` bigint(20) unsigned NOT NULL,
  `salary_month` varchar(20) NOT NULL,
  `basic_salary` decimal(15,2) NOT NULL DEFAULT 0.00,
  `working_days` int(11) NOT NULL DEFAULT 30,
  `present_days` int(11) NOT NULL DEFAULT 30,
  `absent_days` int(11) NOT NULL DEFAULT 0,
  `allowance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `bonus` decimal(15,2) NOT NULL DEFAULT 0.00,
  `overtime_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `deduction` decimal(15,2) NOT NULL DEFAULT 0.00,
  `net_salary` decimal(15,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `due_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` enum(\'unpaid\',\'partial\',\'paid\',\'cancelled\') NOT NULL DEFAULT \'unpaid\',
  `note` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `cancelled_by` bigint(20) unsigned DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancel_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('salary_sheets', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('financial_year_id');
            $table->unsignedBigInteger('employee_id');
            $table->string('salary_month', 20);
            $table->decimal('basic_salary', 15, 2)->default('0.00');
            $table->integer('working_days')->default('30');
            $table->integer('present_days')->default('30');
            $table->integer('absent_days')->default('0');
            $table->decimal('allowance', 15, 2)->default('0.00');
            $table->decimal('bonus', 15, 2)->default('0.00');
            $table->decimal('overtime_amount', 15, 2)->default('0.00');
            $table->decimal('deduction', 15, 2)->default('0.00');
            $table->decimal('net_salary', 15, 2)->default('0.00');
            $table->decimal('paid_amount', 15, 2)->default('0.00');
            $table->decimal('due_amount', 15, 2)->default('0.00');
            $table->string('status')->default('unpaid');
            $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_sheets');
    }
};
