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
            DB::statement('CREATE TABLE `sales_return_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned NOT NULL,
  `sales_return_id` bigint(20) unsigned NOT NULL,
  `sales_item_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `service_id` bigint(20) unsigned DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(12,2) NOT NULL,
  `total_price` decimal(12,2) NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `vat_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `vat_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `sales_return_items_sales_return_id_sales_item_id_index` (`sales_return_id`,`sales_item_id`),
  KEY `sales_return_items_financial_year_id_index` (`financial_year_id`),
  KEY `sales_return_items_service_id_foreign` (`service_id`),
  CONSTRAINT `sales_return_items_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('sales_return_items', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('financial_year_id');
            $table->unsignedBigInteger('sales_return_id');
            $table->unsignedBigInteger('sales_item_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('service_id')->nullable();
            $table->integer('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('total_price', 12, 2);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->boolean('status')->default('1');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->decimal('vat_rate', 5, 2)->default('0.00');
            $table->decimal('vat_amount', 12, 2)->default('0.00');
            $table->index(array (
  0 => 'financial_year_id',
), 'sales_return_items_financial_year_id_index');
            $table->index(array (
  0 => 'sales_return_id',
  1 => 'sales_item_id',
), 'sales_return_items_sales_return_id_sales_item_id_index');
            $table->index(array (
  0 => 'service_id',
), 'sales_return_items_service_id_foreign');
            $table->foreign(array (
  0 => 'service_id',
), 'sales_return_items_service_id_foreign')->references(array (
  0 => 'id',
))->on('services')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_return_items');
    }
};
