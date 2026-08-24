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
            DB::statement('CREATE TABLE `journals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned NOT NULL,
  `journal_no` varchar(255) NOT NULL,
  `journal_date` date NOT NULL,
  `journal_type` varchar(30) DEFAULT NULL,
  `reference_no` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `source_module` varchar(255) DEFAULT NULL,
  `source_type` varchar(255) DEFAULT NULL,
  `source_id` bigint(20) unsigned DEFAULT NULL,
  `source_key` varchar(255) DEFAULT NULL,
  `request_key` char(36) DEFAULT NULL,
  `total_amount` decimal(20,4) NOT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `note` longtext DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `submitted_by` bigint(20) unsigned DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejected_by` bigint(20) unsigned DEFAULT NULL,
  `rejected_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `posted_by` bigint(20) unsigned DEFAULT NULL,
  `posted_at` timestamp NULL DEFAULT NULL,
  `cancelled_by` bigint(20) unsigned DEFAULT NULL,
  `reversed_by` bigint(20) unsigned DEFAULT NULL,
  `reversed_at` timestamp NULL DEFAULT NULL,
  `reversal_reason` text DEFAULT NULL,
  `reversal_of_journal_id` bigint(20) unsigned DEFAULT NULL,
  `is_locked` tinyint(1) NOT NULL DEFAULT 0,
  `locked_by` bigint(20) unsigned DEFAULT NULL,
  `locked_at` timestamp NULL DEFAULT NULL,
  `lock_reason` text DEFAULT NULL,
  `unlocked_by` bigint(20) unsigned DEFAULT NULL,
  `unlocked_at` timestamp NULL DEFAULT NULL,
  `unlock_reason` text DEFAULT NULL,
  `legacy_classification` varchar(20) DEFAULT NULL,
  `legacy_classification_reason` text DEFAULT NULL,
  `cancelled_date` date DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancel_reason` text DEFAULT NULL,
  `cancellation_reason` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT \'draft\',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `journals_company_fy_number_unique` (`company_id`,`financial_year_id`,`journal_no`),
  UNIQUE KEY `journals_company_source_unique` (`company_id`,`source_key`),
  UNIQUE KEY `journals_company_request_unique` (`company_id`,`request_key`),
  UNIQUE KEY `journals_one_reversal_unique` (`reversal_of_journal_id`),
  KEY `journals_reversal_of_journal_id_index` (`reversal_of_journal_id`),
  KEY `journals_financial_year_id_foreign` (`financial_year_id`),
  KEY `journals_submitted_by_foreign` (`submitted_by`),
  KEY `journals_approved_by_foreign` (`approved_by`),
  KEY `journals_rejected_by_foreign` (`rejected_by`),
  KEY `journals_locked_by_foreign` (`locked_by`),
  KEY `journals_unlocked_by_foreign` (`unlocked_by`),
  KEY `journals_company_fy_date_status_idx` (`company_id`,`financial_year_id`,`journal_date`,`status`),
  CONSTRAINT `journals_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`),
  CONSTRAINT `journals_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `journals_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `financial_years` (`id`),
  CONSTRAINT `journals_locked_by_foreign` FOREIGN KEY (`locked_by`) REFERENCES `users` (`id`),
  CONSTRAINT `journals_rejected_by_foreign` FOREIGN KEY (`rejected_by`) REFERENCES `users` (`id`),
  CONSTRAINT `journals_submitted_by_foreign` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`),
  CONSTRAINT `journals_unlocked_by_foreign` FOREIGN KEY (`unlocked_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('journals', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('financial_year_id');
            $table->string('journal_no', 255);
            $table->date('journal_date');
            $table->string('journal_type', 30)->nullable();
            $table->string('reference_no', 255)->nullable();
            $table->text('description')->nullable();
            $table->text('remarks')->nullable();
            $table->string('source_module', 255)->nullable();
            $table->string('source_type', 255)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('source_key', 255)->nullable();
            $table->char('request_key', 36)->nullable();
            $table->decimal('total_amount', 20, 4);
            $table->string('attachment', 255)->nullable();
            $table->longText('note')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('rejected_by')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->unsignedBigInteger('reversed_by')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->text('reversal_reason')->nullable();
            $table->unsignedBigInteger('reversal_of_journal_id')->nullable();
            $table->boolean('is_locked')->default('0');
            $table->unsignedBigInteger('locked_by')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->text('lock_reason')->nullable();
            $table->unsignedBigInteger('unlocked_by')->nullable();
            $table->timestamp('unlocked_at')->nullable();
            $table->text('unlock_reason')->nullable();
            $table->string('legacy_classification', 20)->nullable();
            $table->text('legacy_classification_reason')->nullable();
            $table->date('cancelled_date')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(array (
  0 => 'approved_by',
), 'journals_approved_by_foreign');
            $table->index(array (
  0 => 'company_id',
  1 => 'financial_year_id',
  2 => 'journal_date',
  3 => 'status',
), 'journals_company_fy_date_status_idx');
            $table->unique(array (
  0 => 'company_id',
  1 => 'financial_year_id',
  2 => 'journal_no',
), 'journals_company_fy_number_unique');
            $table->unique(array (
  0 => 'company_id',
  1 => 'request_key',
), 'journals_company_request_unique');
            $table->unique(array (
  0 => 'company_id',
  1 => 'source_key',
), 'journals_company_source_unique');
            $table->index(array (
  0 => 'financial_year_id',
), 'journals_financial_year_id_foreign');
            $table->index(array (
  0 => 'locked_by',
), 'journals_locked_by_foreign');
            $table->unique(array (
  0 => 'reversal_of_journal_id',
), 'journals_one_reversal_unique');
            $table->index(array (
  0 => 'rejected_by',
), 'journals_rejected_by_foreign');
            $table->index(array (
  0 => 'reversal_of_journal_id',
), 'journals_reversal_of_journal_id_index');
            $table->index(array (
  0 => 'submitted_by',
), 'journals_submitted_by_foreign');
            $table->index(array (
  0 => 'unlocked_by',
), 'journals_unlocked_by_foreign');
            $table->foreign(array (
  0 => 'approved_by',
), 'journals_approved_by_foreign')->references(array (
  0 => 'id',
))->on('users')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'company_id',
), 'journals_company_id_foreign')->references(array (
  0 => 'id',
))->on('companies')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'financial_year_id',
), 'journals_financial_year_id_foreign')->references(array (
  0 => 'id',
))->on('financial_years')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'locked_by',
), 'journals_locked_by_foreign')->references(array (
  0 => 'id',
))->on('users')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'rejected_by',
), 'journals_rejected_by_foreign')->references(array (
  0 => 'id',
))->on('users')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'submitted_by',
), 'journals_submitted_by_foreign')->references(array (
  0 => 'id',
))->on('users')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'unlocked_by',
), 'journals_unlocked_by_foreign')->references(array (
  0 => 'id',
))->on('users')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journals');
    }
};
