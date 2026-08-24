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
            DB::statement('CREATE TABLE `purchase_return_refund_adjustments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `purchase_return_refund_id` bigint(20) unsigned NOT NULL,
  `purchase_invoice_id` bigint(20) unsigned NOT NULL,
  `adjust_amount` decimal(18,2) NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `created_by` bigint(20) unsigned NOT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `deleted_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `prra_cmp_idx` (`company_id`),
  KEY `prra_refund_idx` (`purchase_return_refund_id`),
  KEY `prra_invoice_idx` (`purchase_invoice_id`),
  CONSTRAINT `prra_cmp_fk` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `prra_invoice_fk` FOREIGN KEY (`purchase_invoice_id`) REFERENCES `purchase_invoices` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `prra_refund_fk` FOREIGN KEY (`purchase_return_refund_id`) REFERENCES `purchase_return_refunds` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('purchase_return_refund_adjustments', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('purchase_return_refund_id');
            $table->unsignedBigInteger('purchase_invoice_id');
            $table->decimal('adjust_amount', 18, 2);
            $table->tinyInteger('status')->default('1');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->index(array (
  0 => 'company_id',
), 'prra_cmp_idx');
            $table->index(array (
  0 => 'purchase_invoice_id',
), 'prra_invoice_idx');
            $table->index(array (
  0 => 'purchase_return_refund_id',
), 'prra_refund_idx');
            $table->foreign(array (
  0 => 'company_id',
), 'prra_cmp_fk')->references(array (
  0 => 'id',
))->on('companies')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign(array (
  0 => 'purchase_invoice_id',
), 'prra_invoice_fk')->references(array (
  0 => 'id',
))->on('purchase_invoices')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign(array (
  0 => 'purchase_return_refund_id',
), 'prra_refund_fk')->references(array (
  0 => 'id',
))->on('purchase_return_refunds')->onDelete('restrict')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_return_refund_adjustments');
    }
};
