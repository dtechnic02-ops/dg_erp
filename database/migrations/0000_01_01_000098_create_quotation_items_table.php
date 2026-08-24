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
            DB::statement('CREATE TABLE `quotation_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `quotation_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `item_type` varchar(20) NOT NULL,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `service_id` bigint(20) unsigned DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `quantity` decimal(20,4) NOT NULL,
  `unit_price` decimal(20,4) NOT NULL,
  `vat_rate` decimal(10,4) NOT NULL DEFAULT 0.0000,
  `vat_amount` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `total_price` decimal(20,4) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `quotation_items_company_id_foreign` (`company_id`),
  KEY `quotation_items_product_id_foreign` (`product_id`),
  KEY `quotation_items_service_id_foreign` (`service_id`),
  KEY `quotation_items_quotation_id_item_type_index` (`quotation_id`,`item_type`),
  CONSTRAINT `quotation_items_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `quotation_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `quotation_items_quotation_id_foreign` FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `quotation_items_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('quotation_items', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('quotation_id');
            $table->unsignedBigInteger('company_id');
            $table->string('item_type', 20);
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('service_id')->nullable();
            $table->string('description', 255)->nullable();
            $table->decimal('quantity', 20, 4);
            $table->decimal('unit_price', 20, 4);
            $table->decimal('vat_rate', 10, 4)->default('0.0000');
            $table->decimal('vat_amount', 20, 4)->default('0.0000');
            $table->decimal('total_price', 20, 4);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(array (
  0 => 'company_id',
), 'quotation_items_company_id_foreign');
            $table->index(array (
  0 => 'product_id',
), 'quotation_items_product_id_foreign');
            $table->index(array (
  0 => 'quotation_id',
  1 => 'item_type',
), 'quotation_items_quotation_id_item_type_index');
            $table->index(array (
  0 => 'service_id',
), 'quotation_items_service_id_foreign');
            $table->foreign(array (
  0 => 'company_id',
), 'quotation_items_company_id_foreign')->references(array (
  0 => 'id',
))->on('companies')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'product_id',
), 'quotation_items_product_id_foreign')->references(array (
  0 => 'id',
))->on('products')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'quotation_id',
), 'quotation_items_quotation_id_foreign')->references(array (
  0 => 'id',
))->on('quotations')->onDelete('cascade')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'service_id',
), 'quotation_items_service_id_foreign')->references(array (
  0 => 'id',
))->on('services')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_items');
    }
};
