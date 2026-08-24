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
            DB::statement('CREATE TABLE `platform_social_links` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `platform_setting_id` bigint(20) unsigned NOT NULL,
  `provider` varchar(60) NOT NULL,
  `url` varchar(500) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `display_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `platform_social_setting_provider_unique` (`platform_setting_id`,`provider`),
  CONSTRAINT `platform_social_links_platform_setting_id_foreign` FOREIGN KEY (`platform_setting_id`) REFERENCES `platform_settings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('platform_social_links', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('platform_setting_id');
            $table->string('provider', 60);
            $table->string('url', 500);
            $table->boolean('is_active')->default('1');
            $table->unsignedSmallInteger('display_order')->default('0');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unique(array (
  0 => 'platform_setting_id',
  1 => 'provider',
), 'platform_social_setting_provider_unique');
            $table->foreign(array (
  0 => 'platform_setting_id',
), 'platform_social_links_platform_setting_id_foreign')->references(array (
  0 => 'id',
))->on('platform_settings')->onDelete('cascade')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_social_links');
    }
};
