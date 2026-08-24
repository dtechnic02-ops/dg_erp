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
            DB::statement('CREATE TABLE `accounting_entries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned NOT NULL,
  `entry_number` varchar(255) NOT NULL,
  `entry_date` date NOT NULL,
  `reference_number` varchar(255) DEFAULT NULL,
  `source_module` varchar(255) NOT NULL,
  `source_type` varchar(255) DEFAULT NULL,
  `source_id` bigint(20) unsigned DEFAULT NULL,
  `source_event` varchar(255) DEFAULT NULL,
  `source_key` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum(\'draft\',\'posted\',\'reversed\') NOT NULL DEFAULT \'draft\',
  `reversal_of_id` bigint(20) unsigned DEFAULT NULL,
  `posted_at` timestamp NULL DEFAULT NULL,
  `posted_by` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `accounting_entries_company_id_entry_number_unique` (`company_id`,`entry_number`),
  UNIQUE KEY `accounting_entries_company_id_source_key_unique` (`company_id`,`source_key`),
  KEY `accounting_entries_reversal_of_id_foreign` (`reversal_of_id`),
  KEY `accounting_entries_posted_by_foreign` (`posted_by`),
  KEY `accounting_entries_created_by_foreign` (`created_by`),
  KEY `accounting_entries_updated_by_foreign` (`updated_by`),
  KEY `accounting_entries_entry_date_index` (`entry_date`),
  KEY `accounting_entries_status_index` (`status`),
  KEY `accounting_entries_source_module_index` (`source_module`),
  KEY `accounting_entries_source_type_source_id_index` (`source_type`,`source_id`),
  KEY `accounting_entries_company_id_entry_date_index` (`company_id`,`entry_date`),
  KEY `accounting_entries_company_id_status_index` (`company_id`,`status`),
  KEY `ae_company_fy_date_idx` (`company_id`,`financial_year_id`,`entry_date`),
  KEY `accounting_entries_financial_year_fk` (`financial_year_id`),
  CONSTRAINT `accounting_entries_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_entries_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_entries_financial_year_fk` FOREIGN KEY (`financial_year_id`) REFERENCES `financial_years` (`id`),
  CONSTRAINT `accounting_entries_posted_by_foreign` FOREIGN KEY (`posted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_entries_reversal_of_id_foreign` FOREIGN KEY (`reversal_of_id`) REFERENCES `accounting_entries` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_entries_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('accounting_entries', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('financial_year_id');
            $table->string('entry_number', 255);
            $table->date('entry_date');
            $table->string('reference_number', 255)->nullable();
            $table->string('source_module', 255);
            $table->string('source_type', 255)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('source_event', 255)->nullable();
            $table->string('source_key', 255)->nullable();
            $table->text('description')->nullable();
            $table->string('status')->default('draft');
            $table->unsignedBigInteger('reversal_of_id')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(array (
  0 => 'company_id',
  1 => 'entry_date',
), 'accounting_entries_company_id_entry_date_index');
            $table->unique(array (
  0 => 'company_id',
  1 => 'entry_number',
), 'accounting_entries_company_id_entry_number_unique');
            $table->unique(array (
  0 => 'company_id',
  1 => 'source_key',
), 'accounting_entries_company_id_source_key_unique');
            $table->index(array (
  0 => 'company_id',
  1 => 'status',
), 'accounting_entries_company_id_status_index');
            $table->index(array (
  0 => 'created_by',
), 'accounting_entries_created_by_foreign');
            $table->index(array (
  0 => 'entry_date',
), 'accounting_entries_entry_date_index');
            $table->index(array (
  0 => 'financial_year_id',
), 'accounting_entries_financial_year_fk');
            $table->index(array (
  0 => 'posted_by',
), 'accounting_entries_posted_by_foreign');
            $table->index(array (
  0 => 'reversal_of_id',
), 'accounting_entries_reversal_of_id_foreign');
            $table->index(array (
  0 => 'source_module',
), 'accounting_entries_source_module_index');
            $table->index(array (
  0 => 'source_type',
  1 => 'source_id',
), 'accounting_entries_source_type_source_id_index');
            $table->index(array (
  0 => 'status',
), 'accounting_entries_status_index');
            $table->index(array (
  0 => 'updated_by',
), 'accounting_entries_updated_by_foreign');
            $table->index(array (
  0 => 'company_id',
  1 => 'financial_year_id',
  2 => 'entry_date',
), 'ae_company_fy_date_idx');
            $table->foreign(array (
  0 => 'company_id',
), 'accounting_entries_company_id_foreign')->references(array (
  0 => 'id',
))->on('companies')->onDelete('cascade')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'created_by',
), 'accounting_entries_created_by_foreign')->references(array (
  0 => 'id',
))->on('users')->onDelete('set null')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'financial_year_id',
), 'accounting_entries_financial_year_fk')->references(array (
  0 => 'id',
))->on('financial_years')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'posted_by',
), 'accounting_entries_posted_by_foreign')->references(array (
  0 => 'id',
))->on('users')->onDelete('set null')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'reversal_of_id',
), 'accounting_entries_reversal_of_id_foreign')->references(array (
  0 => 'id',
))->on('accounting_entries')->onDelete('set null')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'updated_by',
), 'accounting_entries_updated_by_foreign')->references(array (
  0 => 'id',
))->on('users')->onDelete('set null')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_entries');
    }
};
