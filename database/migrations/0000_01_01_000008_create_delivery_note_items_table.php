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
            DB::statement('CREATE TABLE `delivery_note_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `delivery_note_id` bigint(20) unsigned NOT NULL,
  `sales_item_id` bigint(20) unsigned NOT NULL,
  `item_type` enum(\'product\',\'service\') NOT NULL,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `service_id` bigint(20) unsigned DEFAULT NULL,
  `invoice_qty` decimal(15,2) NOT NULL DEFAULT 0.00,
  `planned_qty` decimal(15,2) NOT NULL DEFAULT 0.00,
  `delivered_qty` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` varchar(20) NOT NULL DEFAULT \'active\',
  `remarks` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `delivery_note_items_note_index` (`delivery_note_id`),
  KEY `delivery_note_items_company_item_index` (`company_id`,`sales_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('delivery_note_items', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('delivery_note_id');
            $table->unsignedBigInteger('sales_item_id');
            $table->string('item_type');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('service_id')->nullable();
            $table->decimal('invoice_qty', 15, 2)->default('0.00');
            $table->decimal('planned_qty', 15, 2)->default('0.00');
            $table->decimal('delivered_qty', 15, 2)->default('0.00');
            $table->string('status', 20)->default('active');
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(array (
  0 => 'company_id',
  1 => 'sales_item_id',
), 'delivery_note_items_company_item_index');
            $table->index(array (
  0 => 'delivery_note_id',
), 'delivery_note_items_note_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_note_items');
    }
};
