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
            DB::statement('CREATE TABLE `delivery_notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned DEFAULT NULL,
  `delivery_no` varchar(50) NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `employee_id` bigint(20) unsigned NOT NULL,
  `sales_invoice_id` bigint(20) unsigned NOT NULL,
  `delivery_date` date NOT NULL,
  `status` enum(\'draft\',\'ready\',\'delivered\',\'partial\',\'rejected\',\'cancelled\') NOT NULL DEFAULT \'draft\',
  `remarks` text DEFAULT NULL,
  `cancel_reason` text DEFAULT NULL,
  `pdf_path` varchar(255) DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `cancelled_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `completed_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `delivery_notes_company_no_unique` (`company_id`,`delivery_no`),
  KEY `delivery_notes_company_date_index` (`company_id`,`delivery_date`),
  KEY `delivery_notes_company_status_index` (`company_id`,`status`),
  KEY `delivery_notes_company_customer_index` (`company_id`,`customer_id`),
  KEY `delivery_notes_company_invoice_index` (`company_id`,`sales_invoice_id`),
  KEY `delivery_notes_company_fy_index` (`company_id`,`financial_year_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('delivery_notes', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('financial_year_id')->nullable();
            $table->string('delivery_no', 50);
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('sales_invoice_id');
            $table->date('delivery_date');
            $table->string('status')->default('draft');
            $table->text('remarks')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->string('pdf_path', 255)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->index(array (
  0 => 'company_id',
  1 => 'customer_id',
), 'delivery_notes_company_customer_index');
            $table->index(array (
  0 => 'company_id',
  1 => 'delivery_date',
), 'delivery_notes_company_date_index');
            $table->index(array (
  0 => 'company_id',
  1 => 'financial_year_id',
), 'delivery_notes_company_fy_index');
            $table->index(array (
  0 => 'company_id',
  1 => 'sales_invoice_id',
), 'delivery_notes_company_invoice_index');
            $table->unique(array (
  0 => 'company_id',
  1 => 'delivery_no',
), 'delivery_notes_company_no_unique');
            $table->index(array (
  0 => 'company_id',
  1 => 'status',
), 'delivery_notes_company_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_notes');
    }
};
