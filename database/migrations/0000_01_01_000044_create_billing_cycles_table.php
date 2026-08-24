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
            DB::statement('CREATE TABLE `billing_cycles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `duration_days` int(10) unsigned DEFAULT NULL,
  `is_lifetime` tinyint(1) NOT NULL DEFAULT 0,
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
  UNIQUE KEY `billing_cycles_code_unique` (`code`),
  KEY `billing_cycles_created_by_foreign` (`created_by`),
  KEY `billing_cycles_updated_by_foreign` (`updated_by`),
  KEY `billing_cycles_cancelled_by_foreign` (`cancelled_by`),
  KEY `idx_billing_cycles_active_sort` (`is_active`,`sort_order`),
  CONSTRAINT `billing_cycles_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `billing_cycles_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `billing_cycles_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('billing_cycles', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('code', 50);
            $table->string('name', 100);
            $table->unsignedInteger('duration_days')->nullable();
            $table->boolean('is_lifetime')->default('0');
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
), 'billing_cycles_cancelled_by_foreign');
            $table->unique(array (
  0 => 'code',
), 'billing_cycles_code_unique');
            $table->index(array (
  0 => 'created_by',
), 'billing_cycles_created_by_foreign');
            $table->index(array (
  0 => 'updated_by',
), 'billing_cycles_updated_by_foreign');
            $table->index(array (
  0 => 'is_active',
  1 => 'sort_order',
), 'idx_billing_cycles_active_sort');
            $table->foreign(array (
  0 => 'cancelled_by',
), 'billing_cycles_cancelled_by_foreign')->references(array (
  0 => 'id',
))->on('users')->onDelete('set null')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'created_by',
), 'billing_cycles_created_by_foreign')->references(array (
  0 => 'id',
))->on('users')->onDelete('set null')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'updated_by',
), 'billing_cycles_updated_by_foreign')->references(array (
  0 => 'id',
))->on('users')->onDelete('set null')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_cycles');
    }
};
