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
            DB::statement('CREATE TABLE `company_subscriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `subscription_type` enum(\'register_trial\',\'free_trial\',\'paid\') NOT NULL DEFAULT \'paid\',
  `subscription_plan_id` bigint(20) unsigned NOT NULL,
  `billing_cycle_id` bigint(20) unsigned DEFAULT NULL,
  `start_date` date NOT NULL,
  `expiry_date` date NOT NULL,
  `staff_limit` int(10) unsigned NOT NULL DEFAULT 1,
  `hidden_modules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`hidden_modules`)),
  `is_all_modules_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `previous_subscription_id` bigint(20) unsigned DEFAULT NULL,
  `activated_at` datetime DEFAULT NULL,
  `expired_at` datetime DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `cancelled_by` bigint(20) unsigned DEFAULT NULL,
  `cancel_reason` text DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT \'active\',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subscriptions_company_id_foreign` (`company_id`),
  KEY `company_subscriptions_subscription_plan_id_foreign` (`subscription_plan_id`),
  KEY `company_subscriptions_billing_cycle_id_foreign` (`billing_cycle_id`),
  KEY `company_subscriptions_previous_subscription_id_foreign` (`previous_subscription_id`),
  KEY `company_subscriptions_cancelled_by_foreign` (`cancelled_by`),
  KEY `company_subscriptions_approved_by_foreign` (`approved_by`),
  KEY `company_subscriptions_created_by_foreign` (`created_by`),
  KEY `company_subscriptions_updated_by_foreign` (`updated_by`),
  CONSTRAINT `company_subscriptions_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `company_subscriptions_billing_cycle_id_foreign` FOREIGN KEY (`billing_cycle_id`) REFERENCES `billing_cycles` (`id`),
  CONSTRAINT `company_subscriptions_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `company_subscriptions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `company_subscriptions_previous_subscription_id_foreign` FOREIGN KEY (`previous_subscription_id`) REFERENCES `company_subscriptions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `company_subscriptions_subscription_plan_id_foreign` FOREIGN KEY (`subscription_plan_id`) REFERENCES `subscription_plans` (`id`),
  CONSTRAINT `company_subscriptions_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `subscriptions_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('company_subscriptions', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->string('subscription_type')->default('paid');
            $table->unsignedBigInteger('subscription_plan_id');
            $table->unsignedBigInteger('billing_cycle_id')->nullable();
            $table->date('start_date');
            $table->date('expiry_date');
            $table->unsignedInteger('staff_limit')->default('1');
            $table->longText('hidden_modules')->nullable();
            $table->boolean('is_all_modules_enabled')->default('0');
            $table->unsignedBigInteger('previous_subscription_id')->nullable();
            $table->dateTime('activated_at')->nullable();
            $table->dateTime('expired_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->string('status', 255)->default('active');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(array (
  0 => 'approved_by',
), 'company_subscriptions_approved_by_foreign');
            $table->index(array (
  0 => 'billing_cycle_id',
), 'company_subscriptions_billing_cycle_id_foreign');
            $table->index(array (
  0 => 'cancelled_by',
), 'company_subscriptions_cancelled_by_foreign');
            $table->index(array (
  0 => 'created_by',
), 'company_subscriptions_created_by_foreign');
            $table->index(array (
  0 => 'previous_subscription_id',
), 'company_subscriptions_previous_subscription_id_foreign');
            $table->index(array (
  0 => 'subscription_plan_id',
), 'company_subscriptions_subscription_plan_id_foreign');
            $table->index(array (
  0 => 'updated_by',
), 'company_subscriptions_updated_by_foreign');
            $table->index(array (
  0 => 'company_id',
), 'subscriptions_company_id_foreign');
            $table->foreign(array (
  0 => 'approved_by',
), 'company_subscriptions_approved_by_foreign')->references(array (
  0 => 'id',
))->on('users')->onDelete('set null')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'billing_cycle_id',
), 'company_subscriptions_billing_cycle_id_foreign')->references(array (
  0 => 'id',
))->on('billing_cycles')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'cancelled_by',
), 'company_subscriptions_cancelled_by_foreign')->references(array (
  0 => 'id',
))->on('users')->onDelete('set null')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'created_by',
), 'company_subscriptions_created_by_foreign')->references(array (
  0 => 'id',
))->on('users')->onDelete('set null')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'previous_subscription_id',
), 'company_subscriptions_previous_subscription_id_foreign')->references(array (
  0 => 'id',
))->on('company_subscriptions')->onDelete('set null')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'subscription_plan_id',
), 'company_subscriptions_subscription_plan_id_foreign')->references(array (
  0 => 'id',
))->on('subscription_plans')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'updated_by',
), 'company_subscriptions_updated_by_foreign')->references(array (
  0 => 'id',
))->on('users')->onDelete('set null')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'company_id',
), 'subscriptions_company_id_foreign')->references(array (
  0 => 'id',
))->on('companies')->onDelete('cascade')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_subscriptions');
    }
};
