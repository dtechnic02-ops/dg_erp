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
            DB::statement('CREATE TABLE `crm_configurations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `config_type` varchar(50) NOT NULL,
  `config_key` varchar(50) NOT NULL,
  `config_label` varchar(100) NOT NULL,
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `crm_config_company_type_key_unique` (`company_id`,`config_type`,`config_key`),
  KEY `crm_config_company_type_active_index` (`company_id`,`config_type`,`is_active`),
  KEY `crm_configurations_created_by_foreign` (`created_by`),
  KEY `crm_configurations_updated_by_foreign` (`updated_by`),
  CONSTRAINT `crm_configurations_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crm_configurations_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_configurations_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('crm_configurations', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->string('config_type', 50);
            $table->string('config_key', 50);
            $table->string('config_label', 100);
            $table->unsignedSmallInteger('sort_order')->default('0');
            $table->boolean('is_active')->default('1');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(array (
  0 => 'created_by',
), 'crm_configurations_created_by_foreign');
            $table->index(array (
  0 => 'updated_by',
), 'crm_configurations_updated_by_foreign');
            $table->index(array (
  0 => 'company_id',
  1 => 'config_type',
  2 => 'is_active',
), 'crm_config_company_type_active_index');
            $table->unique(array (
  0 => 'company_id',
  1 => 'config_type',
  2 => 'config_key',
), 'crm_config_company_type_key_unique');
            $table->foreign(array (
  0 => 'company_id',
), 'crm_configurations_company_id_foreign')->references(array (
  0 => 'id',
))->on('companies')->onDelete('cascade')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'created_by',
), 'crm_configurations_created_by_foreign')->references(array (
  0 => 'id',
))->on('users')->onDelete('set null')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'updated_by',
), 'crm_configurations_updated_by_foreign')->references(array (
  0 => 'id',
))->on('users')->onDelete('set null')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_configurations');
    }
};
