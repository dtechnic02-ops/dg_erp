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
            DB::statement('CREATE TABLE `products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `category_id` bigint(20) unsigned DEFAULT NULL,
  `brand_id` bigint(20) unsigned DEFAULT NULL,
  `manufacture_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `batch_no` varchar(255) DEFAULT NULL,
  `allow_online` tinyint(1) NOT NULL DEFAULT 0,
  `unit_id` bigint(20) unsigned NOT NULL,
  `vat_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `barcode` varchar(255) DEFAULT NULL,
  `cost_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `retail_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `wholesale_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `current_stock` decimal(15,2) NOT NULL DEFAULT 0.00,
  `stock_alert` int(11) NOT NULL DEFAULT 0,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT \'active\',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `products_company_id_foreign` (`company_id`),
  KEY `products_unit_id_foreign` (`unit_id`),
  KEY `products_vat_id_foreign` (`vat_id`),
  KEY `products_brand_id_foreign` (`brand_id`),
  CONSTRAINT `products_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL,
  CONSTRAINT `products_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `products_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`),
  CONSTRAINT `products_vat_id_foreign` FOREIGN KEY (`vat_id`) REFERENCES `vats` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('products', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->date('manufacture_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('batch_no', 255)->nullable();
            $table->boolean('allow_online')->default('0');
            $table->unsignedBigInteger('unit_id');
            $table->unsignedBigInteger('vat_id')->nullable();
            $table->string('name', 255);
            $table->string('barcode', 255)->nullable();
            $table->decimal('cost_price', 10, 2)->default('0.00');
            $table->decimal('retail_price', 10, 2)->default('0.00');
            $table->decimal('wholesale_price', 10, 2)->default('0.00');
            $table->decimal('current_stock', 15, 2)->default('0.00');
            $table->integer('stock_alert')->default('0');
            $table->text('description')->nullable();
            $table->string('image', 255)->nullable();
            $table->string('status', 255)->default('active');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(array (
  0 => 'brand_id',
), 'products_brand_id_foreign');
            $table->index(array (
  0 => 'company_id',
), 'products_company_id_foreign');
            $table->index(array (
  0 => 'unit_id',
), 'products_unit_id_foreign');
            $table->index(array (
  0 => 'vat_id',
), 'products_vat_id_foreign');
            $table->foreign(array (
  0 => 'brand_id',
), 'products_brand_id_foreign')->references(array (
  0 => 'id',
))->on('brands')->onDelete('set null')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'company_id',
), 'products_company_id_foreign')->references(array (
  0 => 'id',
))->on('companies')->onDelete('cascade')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'unit_id',
), 'products_unit_id_foreign')->references(array (
  0 => 'id',
))->on('units')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'vat_id',
), 'products_vat_id_foreign')->references(array (
  0 => 'id',
))->on('vats')->onDelete('set null')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
