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
            DB::statement('CREATE TABLE `purchase_returns` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned DEFAULT NULL,
  `purchase_invoice_id` bigint(20) unsigned NOT NULL,
  `supplier_id` bigint(20) unsigned NOT NULL,
  `return_no` varchar(255) NOT NULL,
  `request_key` char(36) DEFAULT NULL,
  `return_date` date NOT NULL,
  `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_vat` decimal(15,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `refund_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `adjust_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `note` text DEFAULT NULL,
  `damage_photo` varchar(255) DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `purchase_returns_company_request_unique` (`company_id`,`request_key`),
  KEY `purchase_returns_financial_year_id_foreign` (`financial_year_id`),
  CONSTRAINT `purchase_returns_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `financial_years` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('purchase_returns', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('financial_year_id')->nullable();
            $table->unsignedBigInteger('purchase_invoice_id');
            $table->unsignedBigInteger('supplier_id');
            $table->string('return_no', 255);
            $table->char('request_key', 36)->nullable();
            $table->date('return_date');
            $table->decimal('subtotal', 15, 2)->default('0.00');
            $table->decimal('total_vat', 15, 2)->default('0.00');
            $table->decimal('grand_total', 15, 2)->default('0.00');
            $table->decimal('refund_amount', 15, 2)->default('0.00');
            $table->decimal('adjust_amount', 15, 2)->default('0.00');
            $table->text('note')->nullable();
            $table->string('damage_photo', 255)->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->boolean('status')->default('1');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unique(array (
  0 => 'company_id',
  1 => 'request_key',
), 'purchase_returns_company_request_unique');
            $table->index(array (
  0 => 'financial_year_id',
), 'purchase_returns_financial_year_id_foreign');
            $table->foreign(array (
  0 => 'financial_year_id',
), 'purchase_returns_financial_year_id_foreign')->references(array (
  0 => 'id',
))->on('financial_years')->onDelete('set null')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_returns');
    }
};
