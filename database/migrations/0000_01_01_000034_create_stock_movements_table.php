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
            DB::statement('CREATE TABLE `stock_movements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned DEFAULT NULL,
  `transaction_date` date DEFAULT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `type` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `before_stock` int(11) NOT NULL DEFAULT 0,
  `after_stock` int(11) NOT NULL DEFAULT 0,
  `unit_price` decimal(15,2) DEFAULT NULL,
  `reference_no` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('stock_movements', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('financial_year_id')->nullable();
            $table->date('transaction_date')->nullable();
            $table->unsignedBigInteger('product_id');
            $table->string('type', 255);
            $table->integer('quantity');
            $table->integer('before_stock')->default('0');
            $table->integer('after_stock')->default('0');
            $table->decimal('unit_price', 15, 2)->nullable();
            $table->string('reference_no', 255)->nullable();
            $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
