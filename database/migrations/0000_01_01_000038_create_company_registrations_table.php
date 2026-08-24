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
            DB::statement('CREATE TABLE `company_registrations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_name` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `mobile_no` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `country_id` bigint(20) unsigned DEFAULT NULL,
  `selected_user_limit` int(11) NOT NULL DEFAULT 5,
  `status` varchar(255) NOT NULL DEFAULT \'pending\',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_registrations_email_unique` (`email`),
  UNIQUE KEY `company_registrations_username_unique` (`username`),
  KEY `company_registrations_country_id_foreign` (`country_id`),
  CONSTRAINT `company_registrations_country_id_foreign` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('company_registrations', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('company_name', 255);
            $table->string('full_name', 255);
            $table->string('email', 255);
            $table->string('username', 255);
            $table->string('password', 255);
            $table->string('mobile_no', 255)->nullable();
            $table->string('country', 255)->nullable();
            $table->unsignedBigInteger('country_id')->nullable();
            $table->integer('selected_user_limit')->default('5');
            $table->string('status', 255)->default('pending');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(array (
  0 => 'country_id',
), 'company_registrations_country_id_foreign');
            $table->unique(array (
  0 => 'email',
), 'company_registrations_email_unique');
            $table->unique(array (
  0 => 'username',
), 'company_registrations_username_unique');
            $table->foreign(array (
  0 => 'country_id',
), 'company_registrations_country_id_foreign')->references(array (
  0 => 'id',
))->on('countries')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_registrations');
    }
};
