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
            DB::statement('CREATE TABLE `subscription_histories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `company_subscription_id` bigint(20) unsigned DEFAULT NULL,
  `subscription_payment_id` bigint(20) unsigned DEFAULT NULL,
  `event_type` enum(\'register_trial_started\',\'free_trial_assigned\',\'plan_assigned\',\'renewed\',\'upgraded\',\'downgraded\',\'activated\',\'expired\',\'cancelled\',\'payment_submitted\',\'payment_approved\',\'payment_rejected\') NOT NULL,
  `subscription_type_before` enum(\'register_trial\',\'free_trial\',\'paid\') DEFAULT NULL,
  `subscription_type_after` enum(\'register_trial\',\'free_trial\',\'paid\') DEFAULT NULL,
  `subscription_plan_id_before` bigint(20) unsigned DEFAULT NULL,
  `subscription_plan_id_after` bigint(20) unsigned DEFAULT NULL,
  `billing_cycle_id_before` bigint(20) unsigned DEFAULT NULL,
  `billing_cycle_id_after` bigint(20) unsigned DEFAULT NULL,
  `status_before` enum(\'active\',\'expired\',\'cancelled\') DEFAULT NULL,
  `status_after` enum(\'active\',\'expired\',\'cancelled\') DEFAULT NULL,
  `start_date_before` date DEFAULT NULL,
  `start_date_after` date DEFAULT NULL,
  `expiry_date_before` date DEFAULT NULL,
  `expiry_date_after` date DEFAULT NULL,
  `staff_limit_before` int(10) unsigned DEFAULT NULL,
  `staff_limit_after` int(10) unsigned DEFAULT NULL,
  `hidden_modules_before` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`hidden_modules_before`)),
  `hidden_modules_after` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`hidden_modules_after`)),
  `performed_by` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `event_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `subscription_histories_performed_by_foreign` (`performed_by`),
  KEY `idx_sh_company_event_at` (`company_id`,`event_at`),
  KEY `idx_sh_subscription_event_at` (`company_subscription_id`,`event_at`),
  KEY `idx_sh_event_type_event_at` (`event_type`,`event_at`),
  KEY `idx_sh_payment_id` (`subscription_payment_id`),
  KEY `idx_sh_event_at` (`event_at`),
  CONSTRAINT `subscription_histories_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `subscription_histories_company_subscription_id_foreign` FOREIGN KEY (`company_subscription_id`) REFERENCES `company_subscriptions` (`id`),
  CONSTRAINT `subscription_histories_performed_by_foreign` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `subscription_histories_subscription_payment_id_foreign` FOREIGN KEY (`subscription_payment_id`) REFERENCES `subscription_payments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('subscription_histories', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('company_subscription_id')->nullable();
            $table->unsignedBigInteger('subscription_payment_id')->nullable();
            $table->string('event_type');
            $table->string('subscription_type_before')->nullable();
            $table->string('subscription_type_after')->nullable();
            $table->unsignedBigInteger('subscription_plan_id_before')->nullable();
            $table->unsignedBigInteger('subscription_plan_id_after')->nullable();
            $table->unsignedBigInteger('billing_cycle_id_before')->nullable();
            $table->unsignedBigInteger('billing_cycle_id_after')->nullable();
            $table->string('status_before')->nullable();
            $table->string('status_after')->nullable();
            $table->date('start_date_before')->nullable();
            $table->date('start_date_after')->nullable();
            $table->date('expiry_date_before')->nullable();
            $table->date('expiry_date_after')->nullable();
            $table->unsignedInteger('staff_limit_before')->nullable();
            $table->unsignedInteger('staff_limit_after')->nullable();
            $table->longText('hidden_modules_before')->nullable();
            $table->longText('hidden_modules_after')->nullable();
            $table->unsignedBigInteger('performed_by')->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('event_at');
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->index(array (
  0 => 'company_id',
  1 => 'event_at',
), 'idx_sh_company_event_at');
            $table->index(array (
  0 => 'event_at',
), 'idx_sh_event_at');
            $table->index(array (
  0 => 'event_type',
  1 => 'event_at',
), 'idx_sh_event_type_event_at');
            $table->index(array (
  0 => 'subscription_payment_id',
), 'idx_sh_payment_id');
            $table->index(array (
  0 => 'company_subscription_id',
  1 => 'event_at',
), 'idx_sh_subscription_event_at');
            $table->index(array (
  0 => 'performed_by',
), 'subscription_histories_performed_by_foreign');
            $table->foreign(array (
  0 => 'company_id',
), 'subscription_histories_company_id_foreign')->references(array (
  0 => 'id',
))->on('companies')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'company_subscription_id',
), 'subscription_histories_company_subscription_id_foreign')->references(array (
  0 => 'id',
))->on('company_subscriptions')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'performed_by',
), 'subscription_histories_performed_by_foreign')->references(array (
  0 => 'id',
))->on('users')->onDelete('set null')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'subscription_payment_id',
), 'subscription_histories_subscription_payment_id_foreign')->references(array (
  0 => 'id',
))->on('subscription_payments')->onDelete('set null')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_histories');
    }
};
