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
            DB::statement('CREATE TABLE `journal_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `journal_id` bigint(20) unsigned NOT NULL,
  `account_id` bigint(20) unsigned DEFAULT NULL,
  `chart_account_id` bigint(20) unsigned DEFAULT NULL,
  `sub_ledger_type` varchar(20) DEFAULT NULL,
  `sub_ledger_id` bigint(20) unsigned DEFAULT NULL,
  `type` enum(\'debit\',\'credit\') NOT NULL,
  `amount` decimal(20,4) NOT NULL,
  `debit` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `credit` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `description` text DEFAULT NULL,
  `reference` varchar(255) DEFAULT NULL,
  `line_number` int(10) unsigned DEFAULT NULL,
  `note` longtext DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `journal_items_chart_account_id_index` (`chart_account_id`),
  KEY `journal_items_account_id_foreign` (`account_id`),
  KEY `journal_items_journal_line_idx` (`journal_id`,`line_number`),
  CONSTRAINT `journal_items_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`),
  CONSTRAINT `journal_items_chart_account_fk` FOREIGN KEY (`chart_account_id`) REFERENCES `chart_accounts` (`id`),
  CONSTRAINT `journal_items_journal_id_foreign` FOREIGN KEY (`journal_id`) REFERENCES `journals` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('journal_items', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('journal_id');
            $table->unsignedBigInteger('account_id')->nullable();
            $table->unsignedBigInteger('chart_account_id')->nullable();
            $table->string('sub_ledger_type', 20)->nullable();
            $table->unsignedBigInteger('sub_ledger_id')->nullable();
            $table->string('type');
            $table->decimal('amount', 20, 4);
            $table->decimal('debit', 20, 4)->default('0.0000');
            $table->decimal('credit', 20, 4)->default('0.0000');
            $table->text('description')->nullable();
            $table->string('reference', 255)->nullable();
            $table->unsignedInteger('line_number')->nullable();
            $table->longText('note')->nullable();
            $table->tinyInteger('status')->default('1');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(array (
  0 => 'account_id',
), 'journal_items_account_id_foreign');
            $table->index(array (
  0 => 'chart_account_id',
), 'journal_items_chart_account_id_index');
            $table->index(array (
  0 => 'journal_id',
  1 => 'line_number',
), 'journal_items_journal_line_idx');
            $table->foreign(array (
  0 => 'account_id',
), 'journal_items_account_id_foreign')->references(array (
  0 => 'id',
))->on('accounts')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'chart_account_id',
), 'journal_items_chart_account_fk')->references(array (
  0 => 'id',
))->on('chart_accounts')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'journal_id',
), 'journal_items_journal_id_foreign')->references(array (
  0 => 'id',
))->on('journals')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_items');
    }
};
