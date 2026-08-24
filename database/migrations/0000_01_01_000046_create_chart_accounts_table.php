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
            DB::statement('CREATE TABLE `chart_accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `account_class` enum(\'asset\',\'liability\',\'equity\',\'income\',\'expense\') NOT NULL,
  `account_category` varchar(255) DEFAULT NULL,
  `normal_balance` enum(\'debit\',\'credit\') NOT NULL,
  `system_code` varchar(255) DEFAULT NULL,
  `level` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `is_control` tinyint(1) NOT NULL DEFAULT 0,
  `allow_manual_entry` tinyint(1) NOT NULL DEFAULT 1,
  `status` enum(\'active\',\'inactive\') NOT NULL DEFAULT \'active\',
  `is_locked` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `chart_accounts_company_id_code_unique` (`company_id`,`code`),
  KEY `chart_accounts_parent_id_foreign` (`parent_id`),
  KEY `chart_accounts_created_by_foreign` (`created_by`),
  KEY `chart_accounts_updated_by_foreign` (`updated_by`),
  KEY `chart_accounts_account_class_index` (`account_class`),
  KEY `chart_accounts_system_code_index` (`system_code`),
  KEY `chart_accounts_status_index` (`status`),
  KEY `chart_accounts_company_id_account_class_index` (`company_id`,`account_class`),
  KEY `chart_accounts_company_id_parent_id_index` (`company_id`,`parent_id`),
  CONSTRAINT `chart_accounts_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chart_accounts_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chart_accounts_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `chart_accounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chart_accounts_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('chart_accounts', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('code', 255);
            $table->string('name', 255);
            $table->string('account_class');
            $table->string('account_category', 255)->nullable();
            $table->string('normal_balance');
            $table->string('system_code', 255)->nullable();
            $table->unsignedTinyInteger('level')->default('1');
            $table->unsignedInteger('sort_order')->default('0');
            $table->boolean('is_system')->default('0');
            $table->boolean('is_control')->default('0');
            $table->boolean('allow_manual_entry')->default('1');
            $table->string('status')->default('active');
            $table->boolean('is_locked')->default('0');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->index(array (
  0 => 'account_class',
), 'chart_accounts_account_class_index');
            $table->index(array (
  0 => 'company_id',
  1 => 'account_class',
), 'chart_accounts_company_id_account_class_index');
            $table->unique(array (
  0 => 'company_id',
  1 => 'code',
), 'chart_accounts_company_id_code_unique');
            $table->index(array (
  0 => 'company_id',
  1 => 'parent_id',
), 'chart_accounts_company_id_parent_id_index');
            $table->index(array (
  0 => 'created_by',
), 'chart_accounts_created_by_foreign');
            $table->index(array (
  0 => 'parent_id',
), 'chart_accounts_parent_id_foreign');
            $table->index(array (
  0 => 'status',
), 'chart_accounts_status_index');
            $table->index(array (
  0 => 'system_code',
), 'chart_accounts_system_code_index');
            $table->index(array (
  0 => 'updated_by',
), 'chart_accounts_updated_by_foreign');
            $table->foreign(array (
  0 => 'company_id',
), 'chart_accounts_company_id_foreign')->references(array (
  0 => 'id',
))->on('companies')->onDelete('cascade')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'created_by',
), 'chart_accounts_created_by_foreign')->references(array (
  0 => 'id',
))->on('users')->onDelete('set null')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'parent_id',
), 'chart_accounts_parent_id_foreign')->references(array (
  0 => 'id',
))->on('chart_accounts')->onDelete('set null')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'updated_by',
), 'chart_accounts_updated_by_foreign')->references(array (
  0 => 'id',
))->on('users')->onDelete('set null')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chart_accounts');
    }
};
