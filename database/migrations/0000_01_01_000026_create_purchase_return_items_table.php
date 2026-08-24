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
            DB::statement('CREATE TABLE `purchase_return_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned NOT NULL,
  `purchase_return_id` bigint(20) unsigned NOT NULL,
  `purchase_item_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `service_id` bigint(20) unsigned DEFAULT NULL,
  `quantity` decimal(15,2) NOT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `vat_rate` decimal(15,2) NOT NULL DEFAULT 0.00,
  `vat_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_price` decimal(15,2) NOT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_return_items_purchase_return_id_purchase_item_id_index` (`purchase_return_id`,`purchase_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('purchase_return_items', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('financial_year_id');
            $table->unsignedBigInteger('purchase_return_id');
            $table->unsignedBigInteger('purchase_item_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('service_id')->nullable();
            $table->decimal('quantity', 15, 2);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('vat_rate', 15, 2)->default('0.00');
            $table->decimal('vat_amount', 15, 2)->default('0.00');
            $table->decimal('total_price', 15, 2);
            $table->unsignedBigInteger('created_by');
            $table->tinyInteger('status')->default('1');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(array (
  0 => 'purchase_return_id',
  1 => 'purchase_item_id',
), 'purchase_return_items_purchase_return_id_purchase_item_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_return_items');
    }
};
