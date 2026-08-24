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
            DB::statement('CREATE TABLE `loan_saving_ledgers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned NOT NULL,
  `loan_account_id` bigint(20) unsigned NOT NULL,
  `loan_payment_id` bigint(20) unsigned DEFAULT NULL,
  `request_key` char(36) DEFAULT NULL,
  `account_id` bigint(20) unsigned NOT NULL,
  `type` enum(\'deposit\',\'withdraw\',\'loan_settlement\',\'reversal\') NOT NULL,
  `amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `balance_after` decimal(18,2) NOT NULL DEFAULT 0.00,
  `date` date NOT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `cancelled_by` bigint(20) unsigned DEFAULT NULL,
  `cancelled_date` date DEFAULT NULL,
  `cancel_reason` varchar(500) DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `loan_saving_company_request_unique` (`company_id`,`request_key`),
  KEY `loan_saving_ledgers_financial_year_id_index` (`financial_year_id`),
  KEY `loan_saving_ledgers_updated_by_foreign` (`updated_by`),
  KEY `loan_saving_ledgers_cancelled_by_foreign` (`cancelled_by`),
  KEY `loan_saving_loan_fk` (`loan_account_id`),
  KEY `loan_saving_payment_fk` (`loan_payment_id`),
  KEY `loan_saving_account_fk` (`account_id`),
  CONSTRAINT `loan_saving_account_fk` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`),
  CONSTRAINT `loan_saving_company_fk` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `loan_saving_ledgers_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`),
  CONSTRAINT `loan_saving_ledgers_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `financial_years` (`id`),
  CONSTRAINT `loan_saving_ledgers_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`),
  CONSTRAINT `loan_saving_loan_fk` FOREIGN KEY (`loan_account_id`) REFERENCES `loan_accounts` (`id`),
  CONSTRAINT `loan_saving_payment_fk` FOREIGN KEY (`loan_payment_id`) REFERENCES `loan_payments` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('loan_saving_ledgers', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('financial_year_id');
            $table->unsignedBigInteger('loan_account_id');
            $table->unsignedBigInteger('loan_payment_id')->nullable();
            $table->char('request_key', 36)->nullable();
            $table->unsignedBigInteger('account_id');
            $table->string('type');
            $table->decimal('amount', 18, 2)->default('0.00');
            $table->decimal('balance_after', 18, 2)->default('0.00');
            $table->date('date');
            $table->string('attachment', 255)->nullable();
            $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->date('cancelled_date')->nullable();
            $table->string('cancel_reason', 500)->nullable();
            $table->tinyInteger('status')->default('1');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(array (
  0 => 'account_id',
), 'loan_saving_account_fk');
            $table->unique(array (
  0 => 'company_id',
  1 => 'request_key',
), 'loan_saving_company_request_unique');
            $table->index(array (
  0 => 'cancelled_by',
), 'loan_saving_ledgers_cancelled_by_foreign');
            $table->index(array (
  0 => 'financial_year_id',
), 'loan_saving_ledgers_financial_year_id_index');
            $table->index(array (
  0 => 'updated_by',
), 'loan_saving_ledgers_updated_by_foreign');
            $table->index(array (
  0 => 'loan_account_id',
), 'loan_saving_loan_fk');
            $table->index(array (
  0 => 'loan_payment_id',
), 'loan_saving_payment_fk');
            $table->foreign(array (
  0 => 'account_id',
), 'loan_saving_account_fk')->references(array (
  0 => 'id',
))->on('accounts')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'company_id',
), 'loan_saving_company_fk')->references(array (
  0 => 'id',
))->on('companies')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'cancelled_by',
), 'loan_saving_ledgers_cancelled_by_foreign')->references(array (
  0 => 'id',
))->on('users')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'financial_year_id',
), 'loan_saving_ledgers_financial_year_id_foreign')->references(array (
  0 => 'id',
))->on('financial_years')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'updated_by',
), 'loan_saving_ledgers_updated_by_foreign')->references(array (
  0 => 'id',
))->on('users')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'loan_account_id',
), 'loan_saving_loan_fk')->references(array (
  0 => 'id',
))->on('loan_accounts')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'loan_payment_id',
), 'loan_saving_payment_fk')->references(array (
  0 => 'id',
))->on('loan_payments')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_saving_ledgers');
    }
};
