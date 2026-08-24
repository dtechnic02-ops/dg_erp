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
            DB::statement('CREATE TABLE `purchase_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_by` bigint(20) unsigned NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned NOT NULL,
  `purchase_invoice_id` bigint(20) unsigned NOT NULL,
  `item_type` enum(\'product\',\'service\') NOT NULL DEFAULT \'product\',
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `service_id` bigint(20) unsigned DEFAULT NULL,
  `vat_id` bigint(20) unsigned DEFAULT NULL,
  `vat_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `vat_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `quantity` int(11) NOT NULL,
  `returned_qty` decimal(15,2) NOT NULL DEFAULT 0.00,
  `price` decimal(10,2) NOT NULL,
  `unit_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total` decimal(12,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_items_vat_id_foreign` (`vat_id`),
  CONSTRAINT `purchase_items_vat_id_foreign` FOREIGN KEY (`vat_id`) REFERENCES `vats` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('purchase_items', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('created_by');
            $table->tinyInteger('status')->default('1');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('financial_year_id');
            $table->unsignedBigInteger('purchase_invoice_id');
            $table->string('item_type')->default('product');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('service_id')->nullable();
            $table->unsignedBigInteger('vat_id')->nullable();
            $table->decimal('vat_rate', 5, 2)->default('0.00');
            $table->decimal('vat_amount', 15, 2)->default('0.00');
            $table->integer('quantity');
            $table->decimal('returned_qty', 15, 2)->default('0.00');
            $table->decimal('price', 10, 2);
            $table->decimal('unit_price', 15, 2)->default('0.00');
            $table->decimal('total_price', 15, 2)->default('0.00');
            $table->decimal('total', 12, 2);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(array (
  0 => 'vat_id',
), 'purchase_items_vat_id_foreign');
            $table->foreign(array (
  0 => 'vat_id',
), 'purchase_items_vat_id_foreign')->references(array (
  0 => 'id',
))->on('vats')->onDelete('set null')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
    }
};
