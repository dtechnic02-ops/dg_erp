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
            DB::statement('CREATE TABLE `stock_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `type` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `reference` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_transactions_product_id_foreign` (`product_id`),
  KEY `stock_transactions_company_id_foreign` (`company_id`),
  CONSTRAINT `stock_transactions_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_transactions_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('stock_transactions', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('product_id');
            $table->string('type', 255);
            $table->integer('quantity');
            $table->decimal('price', 10, 2)->default('0.00');
            $table->string('reference', 255)->nullable();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(array (
  0 => 'company_id',
), 'stock_transactions_company_id_foreign');
            $table->index(array (
  0 => 'product_id',
), 'stock_transactions_product_id_foreign');
            $table->foreign(array (
  0 => 'company_id',
), 'stock_transactions_company_id_foreign')->references(array (
  0 => 'id',
))->on('companies')->onDelete('cascade')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'product_id',
), 'stock_transactions_product_id_foreign')->references(array (
  0 => 'id',
))->on('products')->onDelete('cascade')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transactions');
    }
};
