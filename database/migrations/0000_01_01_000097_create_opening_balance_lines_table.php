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
            DB::statement('CREATE TABLE `opening_balance_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `opening_balance_id` bigint(20) unsigned NOT NULL,
  `chart_account_id` bigint(20) unsigned NOT NULL,
  `operational_account_id` bigint(20) unsigned DEFAULT NULL,
  `line_number` int(10) unsigned NOT NULL,
  `debit` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `credit` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `subledger_type` varchar(30) DEFAULT NULL,
  `subledger_id` bigint(20) unsigned DEFAULT NULL,
  `description` text DEFAULT NULL,
  `line_reference` varchar(255) DEFAULT NULL,
  `currency` varchar(3) DEFAULT NULL,
  `exchange_rate` decimal(20,8) DEFAULT NULL,
  `base_debit` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `base_credit` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ob_line_number_unique` (`opening_balance_id`,`line_number`),
  KEY `opening_balance_lines_chart_account_id_foreign` (`chart_account_id`),
  KEY `opening_balance_lines_operational_account_id_foreign` (`operational_account_id`),
  KEY `ob_line_subledger_idx` (`subledger_type`,`subledger_id`),
  CONSTRAINT `opening_balance_lines_chart_account_id_foreign` FOREIGN KEY (`chart_account_id`) REFERENCES `chart_accounts` (`id`),
  CONSTRAINT `opening_balance_lines_opening_balance_id_foreign` FOREIGN KEY (`opening_balance_id`) REFERENCES `opening_balances` (`id`) ON DELETE CASCADE,
  CONSTRAINT `opening_balance_lines_operational_account_id_foreign` FOREIGN KEY (`operational_account_id`) REFERENCES `accounts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('opening_balance_lines', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('opening_balance_id');
            $table->unsignedBigInteger('chart_account_id');
            $table->unsignedBigInteger('operational_account_id')->nullable();
            $table->unsignedInteger('line_number');
            $table->decimal('debit', 20, 4)->default('0.0000');
            $table->decimal('credit', 20, 4)->default('0.0000');
            $table->string('subledger_type', 30)->nullable();
            $table->unsignedBigInteger('subledger_id')->nullable();
            $table->text('description')->nullable();
            $table->string('line_reference', 255)->nullable();
            $table->string('currency', 3)->nullable();
            $table->decimal('exchange_rate', 20, 8)->nullable();
            $table->decimal('base_debit', 20, 4)->default('0.0000');
            $table->decimal('base_credit', 20, 4)->default('0.0000');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unique(array (
  0 => 'opening_balance_id',
  1 => 'line_number',
), 'ob_line_number_unique');
            $table->index(array (
  0 => 'subledger_type',
  1 => 'subledger_id',
), 'ob_line_subledger_idx');
            $table->index(array (
  0 => 'chart_account_id',
), 'opening_balance_lines_chart_account_id_foreign');
            $table->index(array (
  0 => 'operational_account_id',
), 'opening_balance_lines_operational_account_id_foreign');
            $table->foreign(array (
  0 => 'chart_account_id',
), 'opening_balance_lines_chart_account_id_foreign')->references(array (
  0 => 'id',
))->on('chart_accounts')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'opening_balance_id',
), 'opening_balance_lines_opening_balance_id_foreign')->references(array (
  0 => 'id',
))->on('opening_balances')->onDelete('cascade')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'operational_account_id',
), 'opening_balance_lines_operational_account_id_foreign')->references(array (
  0 => 'id',
))->on('accounts')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opening_balance_lines');
    }
};
