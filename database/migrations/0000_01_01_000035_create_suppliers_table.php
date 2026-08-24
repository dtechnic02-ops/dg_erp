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
            DB::statement('CREATE TABLE `suppliers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `authority_name` varchar(255) DEFAULT NULL,
  `mobile` varchar(255) DEFAULT NULL,
  `telephone` varchar(255) DEFAULT NULL,
  `fax_no` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `tax_no` varchar(255) DEFAULT NULL,
  `opening_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `credit_days` int(10) unsigned NOT NULL DEFAULT 0,
  `current_balance` decimal(18,2) NOT NULL DEFAULT 0.00,
  `bank_name` varchar(255) DEFAULT NULL,
  `bank_account_no` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `status` enum(\'active\',\'inactive\') NOT NULL DEFAULT \'active\',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('suppliers', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('name', 255);
            $table->string('authority_name', 255)->nullable();
            $table->string('mobile', 255)->nullable();
            $table->string('telephone', 255)->nullable();
            $table->string('fax_no', 255)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('website', 255)->nullable();
            $table->text('address')->nullable();
            $table->string('tax_no', 255)->nullable();
            $table->decimal('opening_balance', 15, 2)->default('0.00');
            $table->unsignedInteger('credit_days')->default('0');
            $table->decimal('current_balance', 18, 2)->default('0.00');
            $table->string('bank_name', 255)->nullable();
            $table->string('bank_account_no', 255)->nullable();
            $table->text('note')->nullable();
            $table->string('image_path', 255)->nullable();
            $table->string('status')->default('active');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
