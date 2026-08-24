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
            DB::statement('CREATE TABLE `subscription_plans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `staff_limit` int(11) NOT NULL,
  `hidden_modules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`hidden_modules`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `cancelled_by` bigint(20) unsigned DEFAULT NULL,
  `cancel_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_subscription_plans_code` (`code`),
  KEY `subscription_plans_created_by_foreign` (`created_by`),
  KEY `subscription_plans_updated_by_foreign` (`updated_by`),
  KEY `subscription_plans_cancelled_by_foreign` (`cancelled_by`),
  CONSTRAINT `subscription_plans_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `subscription_plans_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `subscription_plans_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('subscription_plans', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('code', 50);
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->integer('staff_limit');
            $table->longText('hidden_modules')->nullable();
            $table->boolean('is_active')->default('1');
            $table->unsignedInteger('sort_order')->default('0');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(array (
  0 => 'cancelled_by',
), 'subscription_plans_cancelled_by_foreign');
            $table->index(array (
  0 => 'created_by',
), 'subscription_plans_created_by_foreign');
            $table->index(array (
  0 => 'updated_by',
), 'subscription_plans_updated_by_foreign');
            $table->unique(array (
  0 => 'code',
), 'uq_subscription_plans_code');
            $table->foreign(array (
  0 => 'cancelled_by',
), 'subscription_plans_cancelled_by_foreign')->references(array (
  0 => 'id',
))->on('users')->onDelete('set null')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'created_by',
), 'subscription_plans_created_by_foreign')->references(array (
  0 => 'id',
))->on('users')->onDelete('set null')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'updated_by',
), 'subscription_plans_updated_by_foreign')->references(array (
  0 => 'id',
))->on('users')->onDelete('set null')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
