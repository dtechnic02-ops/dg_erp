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
            DB::statement('CREATE TABLE `quotations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `quotation_no` varchar(50) NOT NULL,
  `quotation_date` date NOT NULL,
  `valid_until` date DEFAULT NULL,
  `reference_no` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `subtotal` decimal(20,4) NOT NULL,
  `discount` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `total_vat` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `grand_total` decimal(20,4) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT \'draft\',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `converted_by` bigint(20) unsigned DEFAULT NULL,
  `converted_at` timestamp NULL DEFAULT NULL,
  `sales_invoice_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `quotations_company_id_financial_year_id_quotation_no_unique` (`company_id`,`financial_year_id`,`quotation_no`),
  UNIQUE KEY `quotations_sales_invoice_id_unique` (`sales_invoice_id`),
  KEY `quotations_financial_year_id_foreign` (`financial_year_id`),
  KEY `quotations_customer_id_foreign` (`customer_id`),
  KEY `quotations_created_by_foreign` (`created_by`),
  KEY `quotations_approved_by_foreign` (`approved_by`),
  KEY `quotations_converted_by_foreign` (`converted_by`),
  KEY `quotations_company_id_status_quotation_date_index` (`company_id`,`status`,`quotation_date`),
  CONSTRAINT `quotations_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quotations_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `quotations_converted_by_foreign` FOREIGN KEY (`converted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quotations_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quotations_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `quotations_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `financial_years` (`id`),
  CONSTRAINT `quotations_sales_invoice_id_foreign` FOREIGN KEY (`sales_invoice_id`) REFERENCES `sales_invoices` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('quotations', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('financial_year_id');
            $table->unsignedBigInteger('customer_id');
            $table->string('quotation_no', 50);
            $table->date('quotation_date');
            $table->date('valid_until')->nullable();
            $table->string('reference_no', 255)->nullable();
            $table->text('note')->nullable();
            $table->decimal('subtotal', 20, 4);
            $table->decimal('discount', 20, 4)->default('0.0000');
            $table->decimal('total_vat', 20, 4)->default('0.0000');
            $table->decimal('grand_total', 20, 4);
            $table->string('status', 20)->default('draft');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('converted_by')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->unsignedBigInteger('sales_invoice_id')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(array (
  0 => 'approved_by',
), 'quotations_approved_by_foreign');
            $table->unique(array (
  0 => 'company_id',
  1 => 'financial_year_id',
  2 => 'quotation_no',
), 'quotations_company_id_financial_year_id_quotation_no_unique');
            $table->index(array (
  0 => 'company_id',
  1 => 'status',
  2 => 'quotation_date',
), 'quotations_company_id_status_quotation_date_index');
            $table->index(array (
  0 => 'converted_by',
), 'quotations_converted_by_foreign');
            $table->index(array (
  0 => 'created_by',
), 'quotations_created_by_foreign');
            $table->index(array (
  0 => 'customer_id',
), 'quotations_customer_id_foreign');
            $table->index(array (
  0 => 'financial_year_id',
), 'quotations_financial_year_id_foreign');
            $table->unique(array (
  0 => 'sales_invoice_id',
), 'quotations_sales_invoice_id_unique');
            $table->foreign(array (
  0 => 'approved_by',
), 'quotations_approved_by_foreign')->references(array (
  0 => 'id',
))->on('users')->onDelete('set null')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'company_id',
), 'quotations_company_id_foreign')->references(array (
  0 => 'id',
))->on('companies')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'converted_by',
), 'quotations_converted_by_foreign')->references(array (
  0 => 'id',
))->on('users')->onDelete('set null')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'created_by',
), 'quotations_created_by_foreign')->references(array (
  0 => 'id',
))->on('users')->onDelete('set null')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'customer_id',
), 'quotations_customer_id_foreign')->references(array (
  0 => 'id',
))->on('customers')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'financial_year_id',
), 'quotations_financial_year_id_foreign')->references(array (
  0 => 'id',
))->on('financial_years')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'sales_invoice_id',
), 'quotations_sales_invoice_id_foreign')->references(array (
  0 => 'id',
))->on('sales_invoices')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotations');
    }
};
