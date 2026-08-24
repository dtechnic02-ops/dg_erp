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
            DB::statement('CREATE TABLE `services` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `service_category_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `service_code` varchar(255) DEFAULT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `vat_id` bigint(20) unsigned DEFAULT NULL,
  `upload_path` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum(\'active\',\'inactive\') NOT NULL DEFAULT \'active\',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `services_company_id_index` (`company_id`),
  KEY `services_service_category_id_index` (`service_category_id`),
  KEY `services_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('services', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('service_category_id')->nullable();
            $table->string('name', 255);
            $table->string('service_code', 255)->nullable();
            $table->string('slug', 255)->nullable();
            $table->decimal('price', 15, 2)->default('0.00');
            $table->unsignedBigInteger('vat_id')->nullable();
            $table->string('upload_path', 255)->nullable();
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(array (
  0 => 'company_id',
), 'services_company_id_index');
            $table->index(array (
  0 => 'service_category_id',
), 'services_service_category_id_index');
            $table->index(array (
  0 => 'status',
), 'services_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
