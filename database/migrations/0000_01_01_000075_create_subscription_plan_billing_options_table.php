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
            DB::statement('CREATE TABLE `subscription_plan_billing_options` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `subscription_plan_id` bigint(20) unsigned NOT NULL,
  `billing_cycle_id` bigint(20) unsigned NOT NULL,
  `price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `currency_code` char(3) NOT NULL DEFAULT \'NPR\',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_plan_billing_option` (`subscription_plan_id`,`billing_cycle_id`),
  KEY `subscription_plan_billing_options_created_by_foreign` (`created_by`),
  KEY `subscription_plan_billing_options_updated_by_foreign` (`updated_by`),
  KEY `idx_spbo_plan_active` (`subscription_plan_id`,`is_active`),
  KEY `idx_spbo_cycle_active` (`billing_cycle_id`,`is_active`),
  CONSTRAINT `subscription_plan_billing_options_billing_cycle_id_foreign` FOREIGN KEY (`billing_cycle_id`) REFERENCES `billing_cycles` (`id`),
  CONSTRAINT `subscription_plan_billing_options_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `subscription_plan_billing_options_subscription_plan_id_foreign` FOREIGN KEY (`subscription_plan_id`) REFERENCES `subscription_plans` (`id`),
  CONSTRAINT `subscription_plan_billing_options_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('subscription_plan_billing_options', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('subscription_plan_id');
            $table->unsignedBigInteger('billing_cycle_id');
            $table->decimal('price', 12, 2)->default('0.00');
            $table->char('currency_code', 3)->default('NPR');
            $table->boolean('is_active')->default('1');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(array (
  0 => 'billing_cycle_id',
  1 => 'is_active',
), 'idx_spbo_cycle_active');
            $table->index(array (
  0 => 'subscription_plan_id',
  1 => 'is_active',
), 'idx_spbo_plan_active');
            $table->index(array (
  0 => 'created_by',
), 'subscription_plan_billing_options_created_by_foreign');
            $table->index(array (
  0 => 'updated_by',
), 'subscription_plan_billing_options_updated_by_foreign');
            $table->unique(array (
  0 => 'subscription_plan_id',
  1 => 'billing_cycle_id',
), 'uq_plan_billing_option');
            $table->foreign(array (
  0 => 'billing_cycle_id',
), 'subscription_plan_billing_options_billing_cycle_id_foreign')->references(array (
  0 => 'id',
))->on('billing_cycles')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'created_by',
), 'subscription_plan_billing_options_created_by_foreign')->references(array (
  0 => 'id',
))->on('users')->onDelete('set null')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'subscription_plan_id',
), 'subscription_plan_billing_options_subscription_plan_id_foreign')->references(array (
  0 => 'id',
))->on('subscription_plans')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'updated_by',
), 'subscription_plan_billing_options_updated_by_foreign')->references(array (
  0 => 'id',
))->on('users')->onDelete('set null')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plan_billing_options');
    }
};
