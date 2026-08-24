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
            DB::statement('CREATE TABLE `sales_invoices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_by` bigint(20) unsigned NOT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned DEFAULT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `invoice_no` varchar(255) NOT NULL,
  `sale_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_vat` decimal(15,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `due_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `payment_status` enum(\'paid\',\'partial\',\'unpaid\') NOT NULL DEFAULT \'unpaid\',
  `note` text DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sales_invoices_company_fy_invoice_no_unique` (`company_id`,`financial_year_id`,`invoice_no`),
  KEY `sales_invoices_financial_year_id_foreign` (`financial_year_id`),
  CONSTRAINT `sales_invoices_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `financial_years` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('sales_invoices', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('financial_year_id')->nullable();
            $table->unsignedBigInteger('customer_id');
            $table->string('invoice_no', 255);
            $table->date('sale_date');
            $table->date('due_date')->nullable();
            $table->decimal('subtotal', 15, 2)->default('0.00');
            $table->decimal('discount', 15, 2)->default('0.00');
            $table->decimal('total_vat', 15, 2)->default('0.00');
            $table->decimal('grand_total', 15, 2)->default('0.00');
            $table->decimal('paid_amount', 15, 2)->default('0.00');
            $table->decimal('due_amount', 15, 2)->default('0.00');
            $table->string('payment_status')->default('unpaid');
            $table->text('note')->nullable();
            $table->tinyInteger('status')->default('1');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unique(array (
  0 => 'company_id',
  1 => 'financial_year_id',
  2 => 'invoice_no',
), 'sales_invoices_company_fy_invoice_no_unique');
            $table->index(array (
  0 => 'financial_year_id',
), 'sales_invoices_financial_year_id_foreign');
            $table->foreign(array (
  0 => 'financial_year_id',
), 'sales_invoices_financial_year_id_foreign')->references(array (
  0 => 'id',
))->on('financial_years')->onDelete('set null')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_invoices');
    }
};
