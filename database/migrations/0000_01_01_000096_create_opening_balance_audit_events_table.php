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
            DB::statement('CREATE TABLE `opening_balance_audit_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned NOT NULL,
  `opening_balance_id` bigint(20) unsigned NOT NULL,
  `event` varchar(40) NOT NULL,
  `previous_status` varchar(30) DEFAULT NULL,
  `new_status` varchar(30) DEFAULT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `reason` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `occurred_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `opening_balance_audit_events_financial_year_id_foreign` (`financial_year_id`),
  KEY `opening_balance_audit_events_opening_balance_id_foreign` (`opening_balance_id`),
  KEY `opening_balance_audit_events_user_id_foreign` (`user_id`),
  KEY `ob_audit_company_fy_idx` (`company_id`,`financial_year_id`,`occurred_at`),
  CONSTRAINT `opening_balance_audit_events_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `opening_balance_audit_events_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `financial_years` (`id`),
  CONSTRAINT `opening_balance_audit_events_opening_balance_id_foreign` FOREIGN KEY (`opening_balance_id`) REFERENCES `opening_balances` (`id`),
  CONSTRAINT `opening_balance_audit_events_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('opening_balance_audit_events', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('financial_year_id');
            $table->unsignedBigInteger('opening_balance_id');
            $table->string('event', 40);
            $table->string('previous_status', 30)->nullable();
            $table->string('new_status', 30)->nullable();
            $table->unsignedBigInteger('user_id');
            $table->text('reason')->nullable();
            $table->longText('metadata')->nullable();
            $table->timestamp('occurred_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(array (
  0 => 'company_id',
  1 => 'financial_year_id',
  2 => 'occurred_at',
), 'ob_audit_company_fy_idx');
            $table->index(array (
  0 => 'financial_year_id',
), 'opening_balance_audit_events_financial_year_id_foreign');
            $table->index(array (
  0 => 'opening_balance_id',
), 'opening_balance_audit_events_opening_balance_id_foreign');
            $table->index(array (
  0 => 'user_id',
), 'opening_balance_audit_events_user_id_foreign');
            $table->foreign(array (
  0 => 'company_id',
), 'opening_balance_audit_events_company_id_foreign')->references(array (
  0 => 'id',
))->on('companies')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'financial_year_id',
), 'opening_balance_audit_events_financial_year_id_foreign')->references(array (
  0 => 'id',
))->on('financial_years')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'opening_balance_id',
), 'opening_balance_audit_events_opening_balance_id_foreign')->references(array (
  0 => 'id',
))->on('opening_balances')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'user_id',
), 'opening_balance_audit_events_user_id_foreign')->references(array (
  0 => 'id',
))->on('users')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opening_balance_audit_events');
    }
};
