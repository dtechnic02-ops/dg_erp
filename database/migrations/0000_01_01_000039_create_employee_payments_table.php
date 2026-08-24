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
            DB::statement('CREATE TABLE `employee_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned NOT NULL,
  `employee_account_id` bigint(20) unsigned NOT NULL,
  `salary_sheet_id` bigint(20) unsigned NOT NULL,
  `voucher_no` varchar(255) NOT NULL,
  `payment_date` date NOT NULL,
  `salary_year` int(11) NOT NULL,
  `salary_month` tinyint(4) NOT NULL,
  `account_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `attachment` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `cancelled_by` bigint(20) unsigned DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancel_reason` text DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_payments_company_voucher_unique` (`company_id`,`voucher_no`),
  KEY `employee_payments_salary_sheet_id_index` (`salary_sheet_id`),
  CONSTRAINT `employee_payments_salary_sheet_id_foreign` FOREIGN KEY (`salary_sheet_id`) REFERENCES `salary_sheets` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('employee_payments', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('financial_year_id');
            $table->unsignedBigInteger('employee_account_id');
            $table->unsignedBigInteger('salary_sheet_id');
            $table->string('voucher_no', 255);
            $table->date('payment_date');
            $table->integer('salary_year');
            $table->tinyInteger('salary_month');
            $table->unsignedBigInteger('account_id');
            $table->decimal('amount', 18, 2)->default('0.00');
            $table->string('attachment', 255)->nullable();
            $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->tinyInteger('status')->default('1');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unique(array (
  0 => 'company_id',
  1 => 'voucher_no',
), 'employee_payments_company_voucher_unique');
            $table->index(array (
  0 => 'salary_sheet_id',
), 'employee_payments_salary_sheet_id_index');
            $table->foreign(array (
  0 => 'salary_sheet_id',
), 'employee_payments_salary_sheet_id_foreign')->references(array (
  0 => 'id',
))->on('salary_sheets')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_payments');
    }
};
