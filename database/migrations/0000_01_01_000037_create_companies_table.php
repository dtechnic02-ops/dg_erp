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
            DB::statement('CREATE TABLE `companies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_name` varchar(255) NOT NULL,
  `mobile` varchar(255) NOT NULL,
  `telephone` varchar(255) DEFAULT NULL,
  `fax_no` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `address` text DEFAULT NULL,
  `address_line_2` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `country_id` bigint(20) unsigned DEFAULT NULL,
  `financial_year_type` varchar(255) NOT NULL DEFAULT \'calendar\',
  `language` varchar(255) NOT NULL DEFAULT \'English\',
  `pan_number` varchar(255) DEFAULT NULL,
  `vat_number` varchar(255) DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `signature_path` varchar(255) DEFAULT NULL,
  `selected_user_limit` int(10) unsigned NOT NULL DEFAULT 1,
  `selected_customer_limit` int(11) NOT NULL DEFAULT 0,
  `status` enum(\'active\',\'blocked\',\'expired\') NOT NULL DEFAULT \'active\',
  `expiry_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `companies_mobile_unique` (`mobile`),
  UNIQUE KEY `companies_email_unique` (`email`),
  KEY `companies_country_id_foreign` (`country_id`),
  CONSTRAINT `companies_country_id_foreign` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('companies', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('company_name', 255);
            $table->string('mobile', 255);
            $table->string('telephone', 255)->nullable();
            $table->string('fax_no', 255)->nullable();
            $table->string('website', 255)->nullable();
            $table->string('email', 255);
            $table->text('address')->nullable();
            $table->string('address_line_2', 255)->nullable();
            $table->string('country', 255)->nullable();
            $table->unsignedBigInteger('country_id')->nullable();
            $table->string('financial_year_type', 255)->default('calendar');
            $table->string('language', 255)->default('English');
            $table->string('pan_number', 255)->nullable();
            $table->string('vat_number', 255)->nullable();
            $table->string('logo_path', 255)->nullable();
            $table->string('signature_path', 255)->nullable();
            $table->unsignedInteger('selected_user_limit')->default('1');
            $table->integer('selected_customer_limit')->default('0');
            $table->string('status')->default('active');
            $table->date('expiry_date')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(array (
  0 => 'country_id',
), 'companies_country_id_foreign');
            $table->unique(array (
  0 => 'email',
), 'companies_email_unique');
            $table->unique(array (
  0 => 'mobile',
), 'companies_mobile_unique');
            $table->foreign(array (
  0 => 'country_id',
), 'companies_country_id_foreign')->references(array (
  0 => 'id',
))->on('countries')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
