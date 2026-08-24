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
            DB::statement('CREATE TABLE `sales_return_refunds` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned DEFAULT NULL,
  `sales_return_id` bigint(20) unsigned NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `account_id` bigint(20) unsigned DEFAULT NULL,
  `idempotency_key` char(36) DEFAULT NULL,
  `refund_no` varchar(255) NOT NULL,
  `refund_date` date NOT NULL,
  `refund_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `adjust_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `cash_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `reference_no` varchar(255) DEFAULT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `deleted_by` bigint(20) unsigned DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sales_return_refunds_company_fy_refund_no_unique` (`company_id`,`financial_year_id`,`refund_no`),
  UNIQUE KEY `sales_return_refunds_company_idempotency_key_unique` (`company_id`,`idempotency_key`),
  KEY `sales_return_refunds_sales_return_id_foreign` (`sales_return_id`),
  KEY `sales_return_refunds_customer_id_foreign` (`customer_id`),
  KEY `sales_return_refunds_account_id_foreign` (`account_id`),
  KEY `sales_return_refunds_financial_year_id_foreign` (`financial_year_id`),
  CONSTRAINT `sales_return_refunds_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sales_return_refunds_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sales_return_refunds_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `financial_years` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sales_return_refunds_sales_return_id_foreign` FOREIGN KEY (`sales_return_id`) REFERENCES `sales_returns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('sales_return_refunds', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('financial_year_id')->nullable();
            $table->unsignedBigInteger('sales_return_id');
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('account_id')->nullable();
            $table->char('idempotency_key', 36)->nullable();
            $table->string('refund_no', 255);
            $table->date('refund_date');
            $table->decimal('refund_amount', 15, 2)->default('0.00');
            $table->decimal('adjust_amount', 18, 2)->default('0.00');
            $table->decimal('cash_amount', 18, 2)->default('0.00');
            $table->string('reference_no', 255)->nullable();
            $table->string('attachment', 255)->nullable();
            $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->tinyInteger('status')->default('1');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->index(array (
  0 => 'account_id',
), 'sales_return_refunds_account_id_foreign');
            $table->unique(array (
  0 => 'company_id',
  1 => 'financial_year_id',
  2 => 'refund_no',
), 'sales_return_refunds_company_fy_refund_no_unique');
            $table->unique(array (
  0 => 'company_id',
  1 => 'idempotency_key',
), 'sales_return_refunds_company_idempotency_key_unique');
            $table->index(array (
  0 => 'customer_id',
), 'sales_return_refunds_customer_id_foreign');
            $table->index(array (
  0 => 'financial_year_id',
), 'sales_return_refunds_financial_year_id_foreign');
            $table->index(array (
  0 => 'sales_return_id',
), 'sales_return_refunds_sales_return_id_foreign');
            $table->foreign(array (
  0 => 'account_id',
), 'sales_return_refunds_account_id_foreign')->references(array (
  0 => 'id',
))->on('accounts')->onDelete('cascade')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'customer_id',
), 'sales_return_refunds_customer_id_foreign')->references(array (
  0 => 'id',
))->on('customers')->onDelete('cascade')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'financial_year_id',
), 'sales_return_refunds_financial_year_id_foreign')->references(array (
  0 => 'id',
))->on('financial_years')->onDelete('set null')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'sales_return_id',
), 'sales_return_refunds_sales_return_id_foreign')->references(array (
  0 => 'id',
))->on('sales_returns')->onDelete('cascade')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_return_refunds');
    }
};
