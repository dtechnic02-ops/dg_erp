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
            DB::statement('CREATE TABLE `accounting_period_locks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned NOT NULL,
  `date_from` date NOT NULL,
  `date_to` date NOT NULL,
  `is_locked` tinyint(1) NOT NULL DEFAULT 1,
  `reason` text NOT NULL,
  `locked_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `accounting_period_locks_financial_year_id_foreign` (`financial_year_id`),
  KEY `accounting_period_locks_locked_by_foreign` (`locked_by`),
  KEY `period_lock_company_fy_idx` (`company_id`,`financial_year_id`,`is_locked`),
  KEY `period_lock_company_dates_idx` (`company_id`,`date_from`,`date_to`),
  CONSTRAINT `accounting_period_locks_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `accounting_period_locks_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `financial_years` (`id`),
  CONSTRAINT `accounting_period_locks_locked_by_foreign` FOREIGN KEY (`locked_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('accounting_period_locks', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('financial_year_id');
            $table->date('date_from');
            $table->date('date_to');
            $table->boolean('is_locked')->default('1');
            $table->text('reason');
            $table->unsignedBigInteger('locked_by');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(array (
  0 => 'financial_year_id',
), 'accounting_period_locks_financial_year_id_foreign');
            $table->index(array (
  0 => 'locked_by',
), 'accounting_period_locks_locked_by_foreign');
            $table->index(array (
  0 => 'company_id',
  1 => 'date_from',
  2 => 'date_to',
), 'period_lock_company_dates_idx');
            $table->index(array (
  0 => 'company_id',
  1 => 'financial_year_id',
  2 => 'is_locked',
), 'period_lock_company_fy_idx');
            $table->foreign(array (
  0 => 'company_id',
), 'accounting_period_locks_company_id_foreign')->references(array (
  0 => 'id',
))->on('companies')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'financial_year_id',
), 'accounting_period_locks_financial_year_id_foreign')->references(array (
  0 => 'id',
))->on('financial_years')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'locked_by',
), 'accounting_period_locks_locked_by_foreign')->references(array (
  0 => 'id',
))->on('users')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_period_locks');
    }
};
