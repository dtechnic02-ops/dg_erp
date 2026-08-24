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
            DB::statement('CREATE TABLE `party_accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `account_no` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `opening_balance` decimal(18,2) NOT NULL DEFAULT 0.00,
  `current_balance` decimal(18,2) NOT NULL DEFAULT 0.00,
  `type` enum(\'bank\',\'person\',\'customer\',\'supplier\',\'company\',\'other\') NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `id_card` varchar(255) DEFAULT NULL,
  `document` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `deleted_by` bigint(20) unsigned DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('party_accounts', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->string('account_no', 255);
            $table->string('name', 255);
            $table->string('phone', 255)->nullable();
            $table->text('address')->nullable();
            $table->decimal('opening_balance', 18, 2)->default('0.00');
            $table->decimal('current_balance', 18, 2)->default('0.00');
            $table->string('type');
            $table->string('photo', 255)->nullable();
            $table->string('id_card', 255)->nullable();
            $table->string('document', 255)->nullable();
            $table->text('note')->nullable();
            $table->date('due_date')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->tinyInteger('status')->default('1');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('party_accounts');
    }
};
