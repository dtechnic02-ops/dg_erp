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
            DB::statement('CREATE TABLE `opening_balances` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned NOT NULL,
  `business_date` date NOT NULL,
  `type` varchar(30) NOT NULL,
  `reference_number` varchar(255) NOT NULL,
  `remarks` text DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT \'draft\',
  `active_key` varchar(20) DEFAULT \'active\',
  `request_key` char(36) NOT NULL,
  `journal_id` bigint(20) unsigned DEFAULT NULL,
  `accounting_entry_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `submitted_by` bigint(20) unsigned DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `posted_by` bigint(20) unsigned DEFAULT NULL,
  `cancelled_by` bigint(20) unsigned DEFAULT NULL,
  `reversed_by` bigint(20) unsigned DEFAULT NULL,
  `locked_by` bigint(20) unsigned DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `posted_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `reversed_at` timestamp NULL DEFAULT NULL,
  `locked_at` timestamp NULL DEFAULT NULL,
  `cancellation_reason` text DEFAULT NULL,
  `reversal_reason` text DEFAULT NULL,
  `lock_reason` text DEFAULT NULL,
  `is_locked` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ob_company_request_unique` (`company_id`,`request_key`),
  UNIQUE KEY `ob_company_fy_reference_unique` (`company_id`,`financial_year_id`,`reference_number`),
  UNIQUE KEY `ob_company_fy_active_unique` (`company_id`,`financial_year_id`,`active_key`),
  UNIQUE KEY `ob_journal_unique` (`journal_id`),
  UNIQUE KEY `ob_accounting_entry_unique` (`accounting_entry_id`),
  KEY `opening_balances_financial_year_id_foreign` (`financial_year_id`),
  KEY `opening_balances_created_by_foreign` (`created_by`),
  KEY `opening_balances_submitted_by_foreign` (`submitted_by`),
  KEY `opening_balances_approved_by_foreign` (`approved_by`),
  KEY `opening_balances_posted_by_foreign` (`posted_by`),
  KEY `opening_balances_cancelled_by_foreign` (`cancelled_by`),
  KEY `opening_balances_reversed_by_foreign` (`reversed_by`),
  KEY `opening_balances_locked_by_foreign` (`locked_by`),
  KEY `ob_company_fy_status_idx` (`company_id`,`financial_year_id`,`status`),
  KEY `ob_company_date_idx` (`company_id`,`business_date`),
  CONSTRAINT `opening_balances_accounting_entry_id_foreign` FOREIGN KEY (`accounting_entry_id`) REFERENCES `accounting_entries` (`id`),
  CONSTRAINT `opening_balances_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`),
  CONSTRAINT `opening_balances_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`),
  CONSTRAINT `opening_balances_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `opening_balances_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `opening_balances_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `financial_years` (`id`),
  CONSTRAINT `opening_balances_journal_id_foreign` FOREIGN KEY (`journal_id`) REFERENCES `journals` (`id`),
  CONSTRAINT `opening_balances_locked_by_foreign` FOREIGN KEY (`locked_by`) REFERENCES `users` (`id`),
  CONSTRAINT `opening_balances_posted_by_foreign` FOREIGN KEY (`posted_by`) REFERENCES `users` (`id`),
  CONSTRAINT `opening_balances_reversed_by_foreign` FOREIGN KEY (`reversed_by`) REFERENCES `users` (`id`),
  CONSTRAINT `opening_balances_submitted_by_foreign` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('opening_balances', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('financial_year_id');
            $table->date('business_date');
            $table->string('type', 30);
            $table->string('reference_number', 255);
            $table->text('remarks')->nullable();
            $table->string('status', 30)->default('draft');
            $table->string('active_key', 20)->nullable()->default('active');
            $table->char('request_key', 36);
            $table->unsignedBigInteger('journal_id')->nullable();
            $table->unsignedBigInteger('accounting_entry_id')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->unsignedBigInteger('reversed_by')->nullable();
            $table->unsignedBigInteger('locked_by')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->text('reversal_reason')->nullable();
            $table->text('lock_reason')->nullable();
            $table->boolean('is_locked')->default('0');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unique(array (
  0 => 'accounting_entry_id',
), 'ob_accounting_entry_unique');
            $table->index(array (
  0 => 'company_id',
  1 => 'business_date',
), 'ob_company_date_idx');
            $table->unique(array (
  0 => 'company_id',
  1 => 'financial_year_id',
  2 => 'active_key',
), 'ob_company_fy_active_unique');
            $table->unique(array (
  0 => 'company_id',
  1 => 'financial_year_id',
  2 => 'reference_number',
), 'ob_company_fy_reference_unique');
            $table->index(array (
  0 => 'company_id',
  1 => 'financial_year_id',
  2 => 'status',
), 'ob_company_fy_status_idx');
            $table->unique(array (
  0 => 'company_id',
  1 => 'request_key',
), 'ob_company_request_unique');
            $table->unique(array (
  0 => 'journal_id',
), 'ob_journal_unique');
            $table->index(array (
  0 => 'approved_by',
), 'opening_balances_approved_by_foreign');
            $table->index(array (
  0 => 'cancelled_by',
), 'opening_balances_cancelled_by_foreign');
            $table->index(array (
  0 => 'created_by',
), 'opening_balances_created_by_foreign');
            $table->index(array (
  0 => 'financial_year_id',
), 'opening_balances_financial_year_id_foreign');
            $table->index(array (
  0 => 'locked_by',
), 'opening_balances_locked_by_foreign');
            $table->index(array (
  0 => 'posted_by',
), 'opening_balances_posted_by_foreign');
            $table->index(array (
  0 => 'reversed_by',
), 'opening_balances_reversed_by_foreign');
            $table->index(array (
  0 => 'submitted_by',
), 'opening_balances_submitted_by_foreign');
            $table->foreign(array (
  0 => 'accounting_entry_id',
), 'opening_balances_accounting_entry_id_foreign')->references(array (
  0 => 'id',
))->on('accounting_entries')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'approved_by',
), 'opening_balances_approved_by_foreign')->references(array (
  0 => 'id',
))->on('users')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'cancelled_by',
), 'opening_balances_cancelled_by_foreign')->references(array (
  0 => 'id',
))->on('users')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'company_id',
), 'opening_balances_company_id_foreign')->references(array (
  0 => 'id',
))->on('companies')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'created_by',
), 'opening_balances_created_by_foreign')->references(array (
  0 => 'id',
))->on('users')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'financial_year_id',
), 'opening_balances_financial_year_id_foreign')->references(array (
  0 => 'id',
))->on('financial_years')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'journal_id',
), 'opening_balances_journal_id_foreign')->references(array (
  0 => 'id',
))->on('journals')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'locked_by',
), 'opening_balances_locked_by_foreign')->references(array (
  0 => 'id',
))->on('users')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'posted_by',
), 'opening_balances_posted_by_foreign')->references(array (
  0 => 'id',
))->on('users')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'reversed_by',
), 'opening_balances_reversed_by_foreign')->references(array (
  0 => 'id',
))->on('users')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'submitted_by',
), 'opening_balances_submitted_by_foreign')->references(array (
  0 => 'id',
))->on('users')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opening_balances');
    }
};
