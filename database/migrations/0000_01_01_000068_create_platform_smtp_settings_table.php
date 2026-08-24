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
            DB::statement('CREATE TABLE `platform_smtp_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `platform_setting_id` bigint(20) unsigned NOT NULL,
  `mailer` varchar(50) NOT NULL DEFAULT \'smtp\',
  `host` varchar(255) NOT NULL,
  `port` smallint(5) unsigned NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `password` text DEFAULT NULL,
  `encryption` varchar(20) DEFAULT NULL,
  `from_address` varchar(190) NOT NULL,
  `from_name` varchar(150) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `last_tested_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `platform_smtp_settings_platform_setting_id_unique` (`platform_setting_id`),
  CONSTRAINT `platform_smtp_settings_platform_setting_id_foreign` FOREIGN KEY (`platform_setting_id`) REFERENCES `platform_settings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('platform_smtp_settings', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('platform_setting_id');
            $table->string('mailer', 50)->default('smtp');
            $table->string('host', 255);
            $table->unsignedSmallInteger('port');
            $table->string('username', 255)->nullable();
            $table->text('password')->nullable();
            $table->string('encryption', 20)->nullable();
            $table->string('from_address', 190);
            $table->string('from_name', 150);
            $table->boolean('is_active')->default('0');
            $table->timestamp('last_tested_at')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unique(array (
  0 => 'platform_setting_id',
), 'platform_smtp_settings_platform_setting_id_unique');
            $table->foreign(array (
  0 => 'platform_setting_id',
), 'platform_smtp_settings_platform_setting_id_foreign')->references(array (
  0 => 'id',
))->on('platform_settings')->onDelete('cascade')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_smtp_settings');
    }
};
