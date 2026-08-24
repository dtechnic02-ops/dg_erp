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
            DB::statement('CREATE TABLE `accounting_entry_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `accounting_entry_id` bigint(20) unsigned NOT NULL,
  `chart_account_id` bigint(20) unsigned NOT NULL,
  `operational_account_id` bigint(20) unsigned DEFAULT NULL,
  `line_number` int(10) unsigned NOT NULL,
  `description` text DEFAULT NULL,
  `debit` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `credit` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `subledger_type` varchar(50) DEFAULT NULL,
  `subledger_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `accounting_entry_lines_accounting_entry_id_line_number_unique` (`accounting_entry_id`,`line_number`),
  KEY `accounting_entry_lines_operational_account_id_foreign` (`operational_account_id`),
  KEY `accounting_entry_lines_subledger_type_subledger_id_index` (`subledger_type`,`subledger_id`),
  KEY `ael_chart_entry_idx` (`chart_account_id`,`accounting_entry_id`),
  CONSTRAINT `accounting_entry_lines_accounting_entry_id_foreign` FOREIGN KEY (`accounting_entry_id`) REFERENCES `accounting_entries` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_entry_lines_chart_account_id_foreign` FOREIGN KEY (`chart_account_id`) REFERENCES `chart_accounts` (`id`),
  CONSTRAINT `accounting_entry_lines_operational_account_id_foreign` FOREIGN KEY (`operational_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('accounting_entry_lines', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('accounting_entry_id');
            $table->unsignedBigInteger('chart_account_id');
            $table->unsignedBigInteger('operational_account_id')->nullable();
            $table->unsignedInteger('line_number');
            $table->text('description')->nullable();
            $table->decimal('debit', 20, 4)->default('0.0000');
            $table->decimal('credit', 20, 4)->default('0.0000');
            $table->string('subledger_type', 50)->nullable();
            $table->unsignedBigInteger('subledger_id')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unique(array (
  0 => 'accounting_entry_id',
  1 => 'line_number',
), 'accounting_entry_lines_accounting_entry_id_line_number_unique');
            $table->index(array (
  0 => 'operational_account_id',
), 'accounting_entry_lines_operational_account_id_foreign');
            $table->index(array (
  0 => 'subledger_type',
  1 => 'subledger_id',
), 'accounting_entry_lines_subledger_type_subledger_id_index');
            $table->index(array (
  0 => 'chart_account_id',
  1 => 'accounting_entry_id',
), 'ael_chart_entry_idx');
            $table->foreign(array (
  0 => 'accounting_entry_id',
), 'accounting_entry_lines_accounting_entry_id_foreign')->references(array (
  0 => 'id',
))->on('accounting_entries')->onDelete('cascade')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'chart_account_id',
), 'accounting_entry_lines_chart_account_id_foreign')->references(array (
  0 => 'id',
))->on('chart_accounts')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'operational_account_id',
), 'accounting_entry_lines_operational_account_id_foreign')->references(array (
  0 => 'id',
))->on('accounts')->onDelete('set null')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_entry_lines');
    }
};
