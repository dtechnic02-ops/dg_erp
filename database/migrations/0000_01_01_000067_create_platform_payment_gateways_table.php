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
            DB::statement('CREATE TABLE `platform_payment_gateways` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `platform_setting_id` bigint(20) unsigned NOT NULL,
  `gateway` varchar(50) NOT NULL,
  `display_name` varchar(100) NOT NULL,
  `environment` varchar(20) NOT NULL DEFAULT \'sandbox\',
  `public_key` text DEFAULT NULL,
  `secret_key` text DEFAULT NULL,
  `merchant_id` varchar(255) DEFAULT NULL,
  `webhook_secret` text DEFAULT NULL,
  `additional_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`additional_config`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `platform_gateway_setting_gateway_unique` (`platform_setting_id`,`gateway`),
  CONSTRAINT `platform_payment_gateways_platform_setting_id_foreign` FOREIGN KEY (`platform_setting_id`) REFERENCES `platform_settings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('platform_payment_gateways', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('platform_setting_id');
            $table->string('gateway', 50);
            $table->string('display_name', 100);
            $table->string('environment', 20)->default('sandbox');
            $table->text('public_key')->nullable();
            $table->text('secret_key')->nullable();
            $table->string('merchant_id', 255)->nullable();
            $table->text('webhook_secret')->nullable();
            $table->longText('additional_config')->nullable();
            $table->boolean('is_active')->default('0');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unique(array (
  0 => 'platform_setting_id',
  1 => 'gateway',
), 'platform_gateway_setting_gateway_unique');
            $table->foreign(array (
  0 => 'platform_setting_id',
), 'platform_payment_gateways_platform_setting_id_foreign')->references(array (
  0 => 'id',
))->on('platform_settings')->onDelete('cascade')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_payment_gateways');
    }
};
