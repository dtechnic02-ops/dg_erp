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
            DB::statement('CREATE TABLE `sales_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_by` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned DEFAULT NULL,
  `sales_invoice_id` bigint(20) unsigned NOT NULL,
  `item_type` enum(\'product\',\'service\') NOT NULL DEFAULT \'product\',
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `service_id` bigint(20) unsigned DEFAULT NULL,
  `quantity` decimal(15,2) NOT NULL,
  `returned_qty` decimal(15,2) NOT NULL DEFAULT 0.00,
  `unit_price` decimal(15,2) NOT NULL,
  `vat_rate` decimal(8,2) NOT NULL DEFAULT 0.00,
  `vat_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_price` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('sales_items', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('financial_year_id')->nullable();
            $table->unsignedBigInteger('sales_invoice_id');
            $table->string('item_type')->default('product');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('service_id')->nullable();
            $table->decimal('quantity', 15, 2);
            $table->decimal('returned_qty', 15, 2)->default('0.00');
            $table->decimal('unit_price', 15, 2);
            $table->decimal('vat_rate', 8, 2)->default('0.00');
            $table->decimal('vat_amount', 15, 2)->default('0.00');
            $table->decimal('total_price', 15, 2);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_items');
    }
};
