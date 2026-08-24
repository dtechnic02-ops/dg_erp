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
            DB::statement('CREATE TABLE `incomes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned DEFAULT NULL,
  `income_category_id` bigint(20) unsigned DEFAULT NULL,
  `income_no` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `account_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `income_date` date NOT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `note` longtext DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `cancelled_by` bigint(20) unsigned DEFAULT NULL,
  `cancelled_date` date DEFAULT NULL,
  `cancel_reason` varchar(500) DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('incomes', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('financial_year_id')->nullable();
            $table->unsignedBigInteger('income_category_id')->nullable();
            $table->string('income_no', 255);
            $table->string('title', 255);
            $table->unsignedBigInteger('account_id');
            $table->decimal('amount', 18, 2)->default('0.00');
            $table->date('income_date');
            $table->string('attachment', 255)->nullable();
            $table->longText('note')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->date('cancelled_date')->nullable();
            $table->string('cancel_reason', 500)->nullable();
            $table->tinyInteger('status')->default('1');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incomes');
    }
};
