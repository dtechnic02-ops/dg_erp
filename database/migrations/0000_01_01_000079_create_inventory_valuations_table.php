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
            DB::statement('CREATE TABLE `inventory_valuations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `stock_movement_id` bigint(20) unsigned NOT NULL,
  `valuation_sequence` bigint(20) unsigned NOT NULL,
  `movement_type` varchar(255) NOT NULL,
  `source_module` varchar(255) NOT NULL,
  `source_type` varchar(255) NOT NULL,
  `source_id` bigint(20) unsigned NOT NULL,
  `source_event` varchar(255) NOT NULL,
  `quantity_before` decimal(20,6) NOT NULL,
  `quantity_change` decimal(20,6) NOT NULL,
  `quantity_after` decimal(20,6) NOT NULL,
  `inventory_value_before` decimal(20,4) NOT NULL,
  `inventory_value_change` decimal(20,4) NOT NULL,
  `inventory_value_after` decimal(20,4) NOT NULL,
  `average_cost_before` decimal(20,8) NOT NULL,
  `movement_unit_cost` decimal(20,8) NOT NULL,
  `average_cost_after` decimal(20,8) NOT NULL,
  `reversal_of_id` bigint(20) unsigned DEFAULT NULL,
  `valued_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `inventory_valuations_stock_movement_id_unique` (`stock_movement_id`),
  UNIQUE KEY `inv_val_company_product_seq_uq` (`company_id`,`product_id`,`valuation_sequence`),
  KEY `inventory_valuations_product_id_foreign` (`product_id`),
  KEY `inventory_valuations_reversal_of_id_foreign` (`reversal_of_id`),
  KEY `inventory_valuations_company_id_product_id_index` (`company_id`,`product_id`),
  KEY `inventory_valuations_company_id_product_id_valued_at_index` (`company_id`,`product_id`,`valued_at`),
  KEY `inv_val_source_lookup_idx` (`company_id`,`source_type`,`source_id`,`source_event`),
  CONSTRAINT `inventory_valuations_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `inventory_valuations_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `inventory_valuations_reversal_of_id_foreign` FOREIGN KEY (`reversal_of_id`) REFERENCES `inventory_valuations` (`id`),
  CONSTRAINT `inventory_valuations_stock_movement_id_foreign` FOREIGN KEY (`stock_movement_id`) REFERENCES `stock_movements` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('inventory_valuations', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('stock_movement_id');
            $table->unsignedBigInteger('valuation_sequence');
            $table->string('movement_type', 255);
            $table->string('source_module', 255);
            $table->string('source_type', 255);
            $table->unsignedBigInteger('source_id');
            $table->string('source_event', 255);
            $table->decimal('quantity_before', 20, 6);
            $table->decimal('quantity_change', 20, 6);
            $table->decimal('quantity_after', 20, 6);
            $table->decimal('inventory_value_before', 20, 4);
            $table->decimal('inventory_value_change', 20, 4);
            $table->decimal('inventory_value_after', 20, 4);
            $table->decimal('average_cost_before', 20, 8);
            $table->decimal('movement_unit_cost', 20, 8);
            $table->decimal('average_cost_after', 20, 8);
            $table->unsignedBigInteger('reversal_of_id')->nullable();
            $table->timestamp('valued_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(array (
  0 => 'company_id',
  1 => 'product_id',
), 'inventory_valuations_company_id_product_id_index');
            $table->index(array (
  0 => 'company_id',
  1 => 'product_id',
  2 => 'valued_at',
), 'inventory_valuations_company_id_product_id_valued_at_index');
            $table->index(array (
  0 => 'product_id',
), 'inventory_valuations_product_id_foreign');
            $table->index(array (
  0 => 'reversal_of_id',
), 'inventory_valuations_reversal_of_id_foreign');
            $table->unique(array (
  0 => 'stock_movement_id',
), 'inventory_valuations_stock_movement_id_unique');
            $table->unique(array (
  0 => 'company_id',
  1 => 'product_id',
  2 => 'valuation_sequence',
), 'inv_val_company_product_seq_uq');
            $table->index(array (
  0 => 'company_id',
  1 => 'source_type',
  2 => 'source_id',
  3 => 'source_event',
), 'inv_val_source_lookup_idx');
            $table->foreign(array (
  0 => 'company_id',
), 'inventory_valuations_company_id_foreign')->references(array (
  0 => 'id',
))->on('companies')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'product_id',
), 'inventory_valuations_product_id_foreign')->references(array (
  0 => 'id',
))->on('products')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'reversal_of_id',
), 'inventory_valuations_reversal_of_id_foreign')->references(array (
  0 => 'id',
))->on('inventory_valuations')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'stock_movement_id',
), 'inventory_valuations_stock_movement_id_foreign')->references(array (
  0 => 'id',
))->on('stock_movements')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_valuations');
    }
};
