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
            DB::statement('CREATE TABLE `purchase_return_refunds` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned DEFAULT NULL,
  `purchase_return_id` bigint(20) unsigned NOT NULL,
  `supplier_id` bigint(20) unsigned DEFAULT NULL,
  `account_id` bigint(20) unsigned DEFAULT NULL,
  `idempotency_key` char(36) DEFAULT NULL,
  `amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `adjust_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `cash_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `payment_method` varchar(255) DEFAULT NULL,
  `reference_no` varchar(255) DEFAULT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `refund_date` date NOT NULL,
  `refund_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `note` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `deleted_by` bigint(20) unsigned DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `refund_no` varchar(255) DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `purchase_return_refunds_company_idempotency_unique` (`company_id`,`idempotency_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('purchase_return_refunds', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('financial_year_id')->nullable();
            $table->unsignedBigInteger('purchase_return_id');
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->unsignedBigInteger('account_id')->nullable();
            $table->char('idempotency_key', 36)->nullable();
            $table->decimal('amount', 18, 2)->default('0.00');
            $table->decimal('adjust_amount', 18, 2)->default('0.00');
            $table->decimal('cash_amount', 18, 2)->default('0.00');
            $table->string('payment_method', 255)->nullable();
            $table->string('reference_no', 255)->nullable();
            $table->string('attachment', 255)->nullable();
            $table->date('refund_date');
            $table->decimal('refund_amount', 18, 2)->default('0.00');
            $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->tinyInteger('status')->default('1');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->string('refund_no', 255)->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->unique(array (
  0 => 'company_id',
  1 => 'idempotency_key',
), 'purchase_return_refunds_company_idempotency_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_return_refunds');
    }
};
