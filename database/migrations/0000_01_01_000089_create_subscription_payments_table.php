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
            DB::statement('CREATE TABLE `subscription_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `subscription_plan_id` bigint(20) unsigned NOT NULL,
  `billing_cycle_id` bigint(20) unsigned DEFAULT NULL,
  `action_type` enum(\'assign\',\'renew\',\'upgrade\',\'downgrade\') NOT NULL DEFAULT \'assign\',
  `amount` int(11) NOT NULL,
  `currency_code` char(3) NOT NULL DEFAULT \'NPR\',
  `payment_method` varchar(255) NOT NULL,
  `payment_date` date DEFAULT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `proof_path` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT \'pending\',
  `company_subscription_id` bigint(20) unsigned DEFAULT NULL,
  `target_subscription_id` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `verified_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `rejected_by` bigint(20) unsigned DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `cancelled_by` bigint(20) unsigned DEFAULT NULL,
  `cancel_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payments_company_id_foreign` (`company_id`),
  KEY `subscription_payments_subscription_plan_id_foreign` (`subscription_plan_id`),
  KEY `subscription_payments_billing_cycle_id_foreign` (`billing_cycle_id`),
  KEY `subscription_payments_company_subscription_id_foreign` (`company_subscription_id`),
  KEY `subscription_payments_target_subscription_id_foreign` (`target_subscription_id`),
  KEY `subscription_payments_verified_by_foreign` (`verified_by`),
  KEY `subscription_payments_approved_by_foreign` (`approved_by`),
  KEY `subscription_payments_rejected_by_foreign` (`rejected_by`),
  KEY `subscription_payments_cancelled_by_foreign` (`cancelled_by`),
  KEY `subscription_payments_created_by_foreign` (`created_by`),
  KEY `subscription_payments_updated_by_foreign` (`updated_by`),
  CONSTRAINT `payments_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `subscription_payments_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `subscription_payments_billing_cycle_id_foreign` FOREIGN KEY (`billing_cycle_id`) REFERENCES `billing_cycles` (`id`),
  CONSTRAINT `subscription_payments_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `subscription_payments_company_subscription_id_foreign` FOREIGN KEY (`company_subscription_id`) REFERENCES `company_subscriptions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `subscription_payments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `subscription_payments_rejected_by_foreign` FOREIGN KEY (`rejected_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `subscription_payments_subscription_plan_id_foreign` FOREIGN KEY (`subscription_plan_id`) REFERENCES `subscription_plans` (`id`),
  CONSTRAINT `subscription_payments_target_subscription_id_foreign` FOREIGN KEY (`target_subscription_id`) REFERENCES `company_subscriptions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `subscription_payments_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `subscription_payments_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('subscription_payments', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('subscription_plan_id');
            $table->unsignedBigInteger('billing_cycle_id')->nullable();
            $table->string('action_type')->default('assign');
            $table->integer('amount');
            $table->char('currency_code', 3)->default('NPR');
            $table->string('payment_method', 255);
            $table->date('payment_date')->nullable();
            $table->string('reference_no', 100)->nullable();
            $table->string('proof_path', 255)->nullable();
            $table->string('status', 255)->default('pending');
            $table->unsignedBigInteger('company_subscription_id')->nullable();
            $table->unsignedBigInteger('target_subscription_id')->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('verified_at')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->dateTime('rejected_at')->nullable();
            $table->unsignedBigInteger('rejected_by')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->index(array (
  0 => 'company_id',
), 'payments_company_id_foreign');
            $table->index(array (
  0 => 'approved_by',
), 'subscription_payments_approved_by_foreign');
            $table->index(array (
  0 => 'billing_cycle_id',
), 'subscription_payments_billing_cycle_id_foreign');
            $table->index(array (
  0 => 'cancelled_by',
), 'subscription_payments_cancelled_by_foreign');
            $table->index(array (
  0 => 'company_subscription_id',
), 'subscription_payments_company_subscription_id_foreign');
            $table->index(array (
  0 => 'created_by',
), 'subscription_payments_created_by_foreign');
            $table->index(array (
  0 => 'rejected_by',
), 'subscription_payments_rejected_by_foreign');
            $table->index(array (
  0 => 'subscription_plan_id',
), 'subscription_payments_subscription_plan_id_foreign');
            $table->index(array (
  0 => 'target_subscription_id',
), 'subscription_payments_target_subscription_id_foreign');
            $table->index(array (
  0 => 'updated_by',
), 'subscription_payments_updated_by_foreign');
            $table->index(array (
  0 => 'verified_by',
), 'subscription_payments_verified_by_foreign');
            $table->foreign(array (
  0 => 'company_id',
), 'payments_company_id_foreign')->references(array (
  0 => 'id',
))->on('companies')->onDelete('cascade')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'approved_by',
), 'subscription_payments_approved_by_foreign')->references(array (
  0 => 'id',
))->on('users')->onDelete('set null')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'billing_cycle_id',
), 'subscription_payments_billing_cycle_id_foreign')->references(array (
  0 => 'id',
))->on('billing_cycles')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'cancelled_by',
), 'subscription_payments_cancelled_by_foreign')->references(array (
  0 => 'id',
))->on('users')->onDelete('set null')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'company_subscription_id',
), 'subscription_payments_company_subscription_id_foreign')->references(array (
  0 => 'id',
))->on('company_subscriptions')->onDelete('set null')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'created_by',
), 'subscription_payments_created_by_foreign')->references(array (
  0 => 'id',
))->on('users')->onDelete('set null')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'rejected_by',
), 'subscription_payments_rejected_by_foreign')->references(array (
  0 => 'id',
))->on('users')->onDelete('set null')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'subscription_plan_id',
), 'subscription_payments_subscription_plan_id_foreign')->references(array (
  0 => 'id',
))->on('subscription_plans')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'target_subscription_id',
), 'subscription_payments_target_subscription_id_foreign')->references(array (
  0 => 'id',
))->on('company_subscriptions')->onDelete('set null')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'updated_by',
), 'subscription_payments_updated_by_foreign')->references(array (
  0 => 'id',
))->on('users')->onDelete('set null')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'verified_by',
), 'subscription_payments_verified_by_foreign')->references(array (
  0 => 'id',
))->on('users')->onDelete('set null')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
    }
};
