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
            DB::statement('CREATE TABLE `accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `account_group` varchar(20) DEFAULT NULL,
  `account_type` varchar(255) NOT NULL DEFAULT \'bank\',
  `sub_ledger_type` varchar(20) DEFAULT NULL,
  `bank_name` varchar(255) NOT NULL,
  `account_name` varchar(255) NOT NULL,
  `branch` varchar(255) DEFAULT NULL,
  `account_no` varchar(255) DEFAULT NULL,
  `iban` varchar(255) DEFAULT NULL,
  `currency` varchar(255) NOT NULL DEFAULT \'AED\',
  `swift_code` varchar(255) DEFAULT NULL,
  `opening_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `current_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `image_path` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `status` enum(\'active\',\'inactive\') NOT NULL DEFAULT \'active\',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('accounts', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->string('account_group', 20)->nullable();
            $table->string('account_type', 255)->default('bank');
            $table->string('sub_ledger_type', 20)->nullable();
            $table->string('bank_name', 255);
            $table->string('account_name', 255);
            $table->string('branch', 255)->nullable();
            $table->string('account_no', 255)->nullable();
            $table->string('iban', 255)->nullable();
            $table->string('currency', 255)->default('AED');
            $table->string('swift_code', 255)->nullable();
            $table->decimal('opening_balance', 15, 2)->default('0.00');
            $table->decimal('current_balance', 15, 2)->default('0.00');
            $table->string('image_path', 255)->nullable();
            $table->text('note')->nullable();
            $table->string('status')->default('active');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
