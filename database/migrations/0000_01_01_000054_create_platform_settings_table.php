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
            DB::statement('CREATE TABLE `platform_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `platform_name` varchar(150) NOT NULL,
  `legal_company_name` varchar(200) DEFAULT NULL,
  `owner_name` varchar(150) NOT NULL,
  `primary_email` varchar(190) NOT NULL,
  `primary_mobile` varchar(30) NOT NULL,
  `alternate_mobile` varchar(30) DEFAULT NULL,
  `support_email` varchar(190) DEFAULT NULL,
  `support_mobile` varchar(30) DEFAULT NULL,
  `whatsapp_number` varchar(30) DEFAULT NULL,
  `website_url` varchar(255) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `state_province` varchar(100) DEFAULT NULL,
  `district_city` varchar(100) DEFAULT NULL,
  `municipality` varchar(100) DEFAULT NULL,
  `ward_number` varchar(30) DEFAULT NULL,
  `postal_code` varchar(30) DEFAULT NULL,
  `full_address` text DEFAULT NULL,
  `tax_number` varchar(100) DEFAULT NULL,
  `vat_number` varchar(100) DEFAULT NULL,
  `company_registration_number` varchar(100) DEFAULT NULL,
  `business_license_number` varchar(100) DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `favicon_path` varchar(255) DEFAULT NULL,
  `signature_path` varchar(255) DEFAULT NULL,
  `profile_photo_path` varchar(255) DEFAULT NULL,
  `timezone` varchar(100) NOT NULL DEFAULT \'Asia/Kathmandu\',
  `currency_code` varchar(3) NOT NULL DEFAULT \'NPR\',
  `language_code` varchar(10) NOT NULL DEFAULT \'en\',
  `date_format` varchar(30) NOT NULL DEFAULT \'Y-m-d\',
  `time_format` varchar(30) NOT NULL DEFAULT \'H:i\',
  `default_trial_days` int(10) unsigned NOT NULL DEFAULT 0,
  `default_staff_limit` int(10) unsigned DEFAULT NULL,
  `default_customer_limit` int(10) unsigned DEFAULT NULL,
  `default_product_limit` int(10) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `platform_settings_created_by_foreign` (`created_by`),
  KEY `platform_settings_updated_by_foreign` (`updated_by`),
  CONSTRAINT `platform_settings_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `platform_settings_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('platform_settings', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('platform_name', 150);
            $table->string('legal_company_name', 200)->nullable();
            $table->string('owner_name', 150);
            $table->string('primary_email', 190);
            $table->string('primary_mobile', 30);
            $table->string('alternate_mobile', 30)->nullable();
            $table->string('support_email', 190)->nullable();
            $table->string('support_mobile', 30)->nullable();
            $table->string('whatsapp_number', 30)->nullable();
            $table->string('website_url', 255)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('state_province', 100)->nullable();
            $table->string('district_city', 100)->nullable();
            $table->string('municipality', 100)->nullable();
            $table->string('ward_number', 30)->nullable();
            $table->string('postal_code', 30)->nullable();
            $table->text('full_address')->nullable();
            $table->string('tax_number', 100)->nullable();
            $table->string('vat_number', 100)->nullable();
            $table->string('company_registration_number', 100)->nullable();
            $table->string('business_license_number', 100)->nullable();
            $table->string('logo_path', 255)->nullable();
            $table->string('favicon_path', 255)->nullable();
            $table->string('signature_path', 255)->nullable();
            $table->string('profile_photo_path', 255)->nullable();
            $table->string('timezone', 100)->default('Asia/Kathmandu');
            $table->string('currency_code', 3)->default('NPR');
            $table->string('language_code', 10)->default('en');
            $table->string('date_format', 30)->default('Y-m-d');
            $table->string('time_format', 30)->default('H:i');
            $table->unsignedInteger('default_trial_days')->default('0');
            $table->unsignedInteger('default_staff_limit')->nullable();
            $table->unsignedInteger('default_customer_limit')->nullable();
            $table->unsignedInteger('default_product_limit')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(array (
  0 => 'created_by',
), 'platform_settings_created_by_foreign');
            $table->index(array (
  0 => 'updated_by',
), 'platform_settings_updated_by_foreign');
            $table->foreign(array (
  0 => 'created_by',
), 'platform_settings_created_by_foreign')->references(array (
  0 => 'id',
))->on('users')->onDelete('set null')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'updated_by',
), 'platform_settings_updated_by_foreign')->references(array (
  0 => 'id',
))->on('users')->onDelete('set null')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_settings');
    }
};
