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
            DB::statement('CREATE TABLE `loan_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned NOT NULL,
  `loan_account_id` bigint(20) unsigned NOT NULL,
  `account_id` bigint(20) unsigned DEFAULT NULL,
  `payment_date` date NOT NULL,
  `next_payment_date` date DEFAULT NULL,
  `principal_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `interest_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `fine_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `saving_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `remaining_principal` decimal(18,2) NOT NULL DEFAULT 0.00,
  `reference_no` varchar(255) DEFAULT NULL,
  `request_key` char(36) DEFAULT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `created_by` bigint(20) unsigned NOT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `cancelled_by` bigint(20) unsigned DEFAULT NULL,
  `cancelled_date` date DEFAULT NULL,
  `cancel_reason` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `loan_payments_company_reference_unique` (`company_id`,`reference_no`),
  UNIQUE KEY `loan_payments_company_request_unique` (`company_id`,`request_key`),
  KEY `loan_payments_financial_year_id_index` (`financial_year_id`),
  KEY `loan_payments_updated_by_foreign` (`updated_by`),
  KEY `loan_payments_cancelled_by_foreign` (`cancelled_by`),
  KEY `loan_payments_loan_fk` (`loan_account_id`),
  KEY `loan_payments_account_fk` (`account_id`),
  CONSTRAINT `loan_payments_account_fk` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`),
  CONSTRAINT `loan_payments_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`),
  CONSTRAINT `loan_payments_company_fk` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `loan_payments_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `financial_years` (`id`),
  CONSTRAINT `loan_payments_loan_fk` FOREIGN KEY (`loan_account_id`) REFERENCES `loan_accounts` (`id`),
  CONSTRAINT `loan_payments_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('loan_payments', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('financial_year_id');
            $table->unsignedBigInteger('loan_account_id');
            $table->unsignedBigInteger('account_id')->nullable();
            $table->date('payment_date');
            $table->date('next_payment_date')->nullable();
            $table->decimal('principal_amount', 18, 2)->default('0.00');
            $table->decimal('interest_amount', 18, 2)->default('0.00');
            $table->decimal('fine_amount', 18, 2)->default('0.00');
            $table->decimal('saving_amount', 18, 2)->default('0.00');
            $table->decimal('total_amount', 18, 2)->default('0.00');
            $table->decimal('remaining_principal', 18, 2)->default('0.00');
            $table->string('reference_no', 255)->nullable();
            $table->char('request_key', 36)->nullable();
            $table->string('attachment', 255)->nullable();
            $table->text('note')->nullable();
            $table->tinyInteger('status')->default('1');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->date('cancelled_date')->nullable();
            $table->string('cancel_reason', 500)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->index(array (
  0 => 'account_id',
), 'loan_payments_account_fk');
            $table->index(array (
  0 => 'cancelled_by',
), 'loan_payments_cancelled_by_foreign');
            $table->unique(array (
  0 => 'company_id',
  1 => 'reference_no',
), 'loan_payments_company_reference_unique');
            $table->unique(array (
  0 => 'company_id',
  1 => 'request_key',
), 'loan_payments_company_request_unique');
            $table->index(array (
  0 => 'financial_year_id',
), 'loan_payments_financial_year_id_index');
            $table->index(array (
  0 => 'loan_account_id',
), 'loan_payments_loan_fk');
            $table->index(array (
  0 => 'updated_by',
), 'loan_payments_updated_by_foreign');
            $table->foreign(array (
  0 => 'account_id',
), 'loan_payments_account_fk')->references(array (
  0 => 'id',
))->on('accounts')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'cancelled_by',
), 'loan_payments_cancelled_by_foreign')->references(array (
  0 => 'id',
))->on('users')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'company_id',
), 'loan_payments_company_fk')->references(array (
  0 => 'id',
))->on('companies')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'financial_year_id',
), 'loan_payments_financial_year_id_foreign')->references(array (
  0 => 'id',
))->on('financial_years')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'loan_account_id',
), 'loan_payments_loan_fk')->references(array (
  0 => 'id',
))->on('loan_accounts')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'updated_by',
), 'loan_payments_updated_by_foreign')->references(array (
  0 => 'id',
))->on('users')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_payments');
    }
};
