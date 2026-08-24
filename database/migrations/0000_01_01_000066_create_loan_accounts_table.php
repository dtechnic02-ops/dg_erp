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
            DB::statement('CREATE TABLE `loan_accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned NOT NULL,
  `loan_no` varchar(255) NOT NULL,
  `request_key` char(36) DEFAULT NULL,
  `loan_name` varchar(255) NOT NULL,
  `loan_type` enum(\'taken\',\'given\') NOT NULL,
  `party_account_id` bigint(20) unsigned NOT NULL,
  `account_id` bigint(20) unsigned NOT NULL,
  `principal_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `interest_rate` decimal(18,2) NOT NULL DEFAULT 0.00,
  `remaining_principal` decimal(18,2) NOT NULL DEFAULT 0.00,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `next_payment_date` date DEFAULT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `cancelled_by` bigint(20) unsigned DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancel_reason` varchar(500) DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `loan_accounts_company_id_loan_no_unique` (`company_id`,`loan_no`),
  UNIQUE KEY `loan_accounts_company_request_unique` (`company_id`,`request_key`),
  KEY `loan_accounts_company_id_index` (`company_id`),
  KEY `loan_accounts_financial_year_id_index` (`financial_year_id`),
  KEY `loan_accounts_status_index` (`status`),
  KEY `loan_accounts_party_account_id_index` (`party_account_id`),
  KEY `loan_accounts_account_id_index` (`account_id`),
  KEY `loan_accounts_updated_by_foreign` (`updated_by`),
  KEY `loan_accounts_cancelled_by_foreign` (`cancelled_by`),
  CONSTRAINT `loan_accounts_account_fk` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`),
  CONSTRAINT `loan_accounts_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`),
  CONSTRAINT `loan_accounts_company_fk` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `loan_accounts_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `financial_years` (`id`),
  CONSTRAINT `loan_accounts_party_fk` FOREIGN KEY (`party_account_id`) REFERENCES `party_accounts` (`id`),
  CONSTRAINT `loan_accounts_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('loan_accounts', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('financial_year_id');
            $table->string('loan_no', 255);
            $table->char('request_key', 36)->nullable();
            $table->string('loan_name', 255);
            $table->string('loan_type');
            $table->unsignedBigInteger('party_account_id');
            $table->unsignedBigInteger('account_id');
            $table->decimal('principal_amount', 18, 2)->default('0.00');
            $table->decimal('interest_rate', 18, 2)->default('0.00');
            $table->decimal('remaining_principal', 18, 2)->default('0.00');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('next_payment_date')->nullable();
            $table->string('attachment', 255)->nullable();
            $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason', 500)->nullable();
            $table->tinyInteger('status')->default('1');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(array (
  0 => 'account_id',
), 'loan_accounts_account_id_index');
            $table->index(array (
  0 => 'cancelled_by',
), 'loan_accounts_cancelled_by_foreign');
            $table->index(array (
  0 => 'company_id',
), 'loan_accounts_company_id_index');
            $table->unique(array (
  0 => 'company_id',
  1 => 'loan_no',
), 'loan_accounts_company_id_loan_no_unique');
            $table->unique(array (
  0 => 'company_id',
  1 => 'request_key',
), 'loan_accounts_company_request_unique');
            $table->index(array (
  0 => 'financial_year_id',
), 'loan_accounts_financial_year_id_index');
            $table->index(array (
  0 => 'party_account_id',
), 'loan_accounts_party_account_id_index');
            $table->index(array (
  0 => 'status',
), 'loan_accounts_status_index');
            $table->index(array (
  0 => 'updated_by',
), 'loan_accounts_updated_by_foreign');
            $table->foreign(array (
  0 => 'account_id',
), 'loan_accounts_account_fk')->references(array (
  0 => 'id',
))->on('accounts')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'cancelled_by',
), 'loan_accounts_cancelled_by_foreign')->references(array (
  0 => 'id',
))->on('users')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'company_id',
), 'loan_accounts_company_fk')->references(array (
  0 => 'id',
))->on('companies')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'financial_year_id',
), 'loan_accounts_financial_year_id_foreign')->references(array (
  0 => 'id',
))->on('financial_years')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'party_account_id',
), 'loan_accounts_party_fk')->references(array (
  0 => 'id',
))->on('party_accounts')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'updated_by',
), 'loan_accounts_updated_by_foreign')->references(array (
  0 => 'id',
))->on('users')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_accounts');
    }
};
