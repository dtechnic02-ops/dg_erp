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
            DB::statement('CREATE TABLE `sales_cost_snapshots` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `sales_invoice_id` bigint(20) unsigned NOT NULL,
  `sales_item_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `stock_movement_id` bigint(20) unsigned NOT NULL,
  `inventory_valuation_id` bigint(20) unsigned NOT NULL,
  `average_cost_used` decimal(20,8) NOT NULL,
  `movement_unit_cost` decimal(20,8) NOT NULL,
  `movement_value` decimal(20,4) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sales_cost_snapshots_sales_item_id_unique` (`sales_item_id`),
  UNIQUE KEY `sales_cost_snapshots_stock_movement_id_unique` (`stock_movement_id`),
  KEY `sales_cost_snapshots_sales_invoice_id_foreign` (`sales_invoice_id`),
  KEY `sales_cost_snapshots_product_id_foreign` (`product_id`),
  KEY `sales_cost_snapshots_inventory_valuation_id_foreign` (`inventory_valuation_id`),
  KEY `sales_cost_snapshots_company_id_sales_invoice_id_index` (`company_id`,`sales_invoice_id`),
  KEY `sales_cost_snapshots_company_id_product_id_index` (`company_id`,`product_id`),
  CONSTRAINT `sales_cost_snapshots_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `sales_cost_snapshots_inventory_valuation_id_foreign` FOREIGN KEY (`inventory_valuation_id`) REFERENCES `inventory_valuations` (`id`),
  CONSTRAINT `sales_cost_snapshots_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `sales_cost_snapshots_sales_invoice_id_foreign` FOREIGN KEY (`sales_invoice_id`) REFERENCES `sales_invoices` (`id`),
  CONSTRAINT `sales_cost_snapshots_sales_item_id_foreign` FOREIGN KEY (`sales_item_id`) REFERENCES `sales_items` (`id`),
  CONSTRAINT `sales_cost_snapshots_stock_movement_id_foreign` FOREIGN KEY (`stock_movement_id`) REFERENCES `stock_movements` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('sales_cost_snapshots', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('sales_invoice_id');
            $table->unsignedBigInteger('sales_item_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('stock_movement_id');
            $table->unsignedBigInteger('inventory_valuation_id');
            $table->decimal('average_cost_used', 20, 8);
            $table->decimal('movement_unit_cost', 20, 8);
            $table->decimal('movement_value', 20, 4);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(array (
  0 => 'company_id',
  1 => 'product_id',
), 'sales_cost_snapshots_company_id_product_id_index');
            $table->index(array (
  0 => 'company_id',
  1 => 'sales_invoice_id',
), 'sales_cost_snapshots_company_id_sales_invoice_id_index');
            $table->index(array (
  0 => 'inventory_valuation_id',
), 'sales_cost_snapshots_inventory_valuation_id_foreign');
            $table->index(array (
  0 => 'product_id',
), 'sales_cost_snapshots_product_id_foreign');
            $table->index(array (
  0 => 'sales_invoice_id',
), 'sales_cost_snapshots_sales_invoice_id_foreign');
            $table->unique(array (
  0 => 'sales_item_id',
), 'sales_cost_snapshots_sales_item_id_unique');
            $table->unique(array (
  0 => 'stock_movement_id',
), 'sales_cost_snapshots_stock_movement_id_unique');
            $table->foreign(array (
  0 => 'company_id',
), 'sales_cost_snapshots_company_id_foreign')->references(array (
  0 => 'id',
))->on('companies')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'inventory_valuation_id',
), 'sales_cost_snapshots_inventory_valuation_id_foreign')->references(array (
  0 => 'id',
))->on('inventory_valuations')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'product_id',
), 'sales_cost_snapshots_product_id_foreign')->references(array (
  0 => 'id',
))->on('products')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'sales_invoice_id',
), 'sales_cost_snapshots_sales_invoice_id_foreign')->references(array (
  0 => 'id',
))->on('sales_invoices')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'sales_item_id',
), 'sales_cost_snapshots_sales_item_id_foreign')->references(array (
  0 => 'id',
))->on('sales_items')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'stock_movement_id',
), 'sales_cost_snapshots_stock_movement_id_foreign')->references(array (
  0 => 'id',
))->on('stock_movements')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_cost_snapshots');
    }
};
