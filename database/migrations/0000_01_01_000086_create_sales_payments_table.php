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
            DB::statement('CREATE TABLE `sales_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned DEFAULT NULL,
  `sales_invoice_id` bigint(20) unsigned NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `account_id` bigint(20) unsigned NOT NULL,
  `payment_no` varchar(255) NOT NULL,
  `payment_date` date NOT NULL,
  `paid_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `payment_method` varchar(255) DEFAULT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `receipt_file` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `deleted_by` bigint(20) unsigned DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sales_payments_company_fy_payment_no_unique` (`company_id`,`financial_year_id`,`payment_no`),
  KEY `sales_payments_sales_invoice_id_foreign` (`sales_invoice_id`),
  KEY `sales_payments_customer_id_foreign` (`customer_id`),
  KEY `sales_payments_account_id_foreign` (`account_id`),
  KEY `sales_payments_financial_year_id_foreign` (`financial_year_id`),
  CONSTRAINT `sales_payments_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sales_payments_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sales_payments_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `financial_years` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sales_payments_sales_invoice_id_foreign` FOREIGN KEY (`sales_invoice_id`) REFERENCES `sales_invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('sales_payments', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('financial_year_id')->nullable();
            $table->unsignedBigInteger('sales_invoice_id');
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('account_id');
            $table->string('payment_no', 255);
            $table->date('payment_date');
            $table->decimal('paid_amount', 15, 2)->default('0.00');
            $table->string('payment_method', 255)->nullable();
            $table->string('reference_no', 100)->nullable();
            $table->string('receipt_file', 255)->nullable();
            $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->tinyInteger('status')->default('1');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(array (
  0 => 'account_id',
), 'sales_payments_account_id_foreign');
            $table->unique(array (
  0 => 'company_id',
  1 => 'financial_year_id',
  2 => 'payment_no',
), 'sales_payments_company_fy_payment_no_unique');
            $table->index(array (
  0 => 'customer_id',
), 'sales_payments_customer_id_foreign');
            $table->index(array (
  0 => 'financial_year_id',
), 'sales_payments_financial_year_id_foreign');
            $table->index(array (
  0 => 'sales_invoice_id',
), 'sales_payments_sales_invoice_id_foreign');
            $table->foreign(array (
  0 => 'account_id',
), 'sales_payments_account_id_foreign')->references(array (
  0 => 'id',
))->on('accounts')->onDelete('cascade')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'customer_id',
), 'sales_payments_customer_id_foreign')->references(array (
  0 => 'id',
))->on('customers')->onDelete('cascade')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'financial_year_id',
), 'sales_payments_financial_year_id_foreign')->references(array (
  0 => 'id',
))->on('financial_years')->onDelete('cascade')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'sales_invoice_id',
), 'sales_payments_sales_invoice_id_foreign')->references(array (
  0 => 'id',
))->on('sales_invoices')->onDelete('cascade')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_payments');
    }
};
