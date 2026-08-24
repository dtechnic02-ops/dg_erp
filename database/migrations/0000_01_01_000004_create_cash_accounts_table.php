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
            DB::statement('CREATE TABLE `cash_accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `account_name` varchar(255) NOT NULL,
  `account_number` varchar(255) DEFAULT NULL,
  `opening_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `note` text DEFAULT NULL,
  `status` enum(\'active\',\'inactive\') NOT NULL DEFAULT \'active\',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('cash_accounts', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->string('account_name', 255);
            $table->string('account_number', 255)->nullable();
            $table->decimal('opening_balance', 15, 2)->default('0.00');
            $table->text('note')->nullable();
            $table->string('status')->default('active');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_accounts');
    }
};
