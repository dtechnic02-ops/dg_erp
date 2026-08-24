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
            DB::statement('CREATE TABLE `account_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned NOT NULL,
  `account_id` bigint(20) unsigned NOT NULL,
  `transaction_date` date NOT NULL,
  `voucher_no` varchar(255) DEFAULT NULL,
  `reference_type` varchar(255) DEFAULT NULL,
  `reference_id` bigint(20) unsigned DEFAULT NULL,
  `journal_item_id` bigint(20) unsigned DEFAULT NULL,
  `reversed_transaction_id` bigint(20) unsigned DEFAULT NULL,
  `description` text DEFAULT NULL,
  `debit` decimal(18,2) NOT NULL DEFAULT 0.00,
  `credit` decimal(18,2) NOT NULL DEFAULT 0.00,
  `balance` decimal(18,2) NOT NULL DEFAULT 0.00,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `at_company_journal_unique` (`company_id`,`journal_item_id`),
  UNIQUE KEY `at_reversal_unique` (`reversed_transaction_id`),
  KEY `account_transactions_journal_item_id_index` (`journal_item_id`),
  KEY `account_transactions_reversed_transaction_id_index` (`reversed_transaction_id`),
  CONSTRAINT `at_journal_item_fk` FOREIGN KEY (`journal_item_id`) REFERENCES `journal_items` (`id`),
  CONSTRAINT `at_reversal_fk` FOREIGN KEY (`reversed_transaction_id`) REFERENCES `account_transactions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('account_transactions', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('financial_year_id');
            $table->unsignedBigInteger('account_id');
            $table->date('transaction_date');
            $table->string('voucher_no', 255)->nullable();
            $table->string('reference_type', 255)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->unsignedBigInteger('journal_item_id')->nullable();
            $table->unsignedBigInteger('reversed_transaction_id')->nullable();
            $table->text('description')->nullable();
            $table->decimal('debit', 18, 2)->default('0.00');
            $table->decimal('credit', 18, 2)->default('0.00');
            $table->decimal('balance', 18, 2)->default('0.00');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->tinyInteger('status')->default('1');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(array (
  0 => 'journal_item_id',
), 'account_transactions_journal_item_id_index');
            $table->index(array (
  0 => 'reversed_transaction_id',
), 'account_transactions_reversed_transaction_id_index');
            $table->unique(array (
  0 => 'company_id',
  1 => 'journal_item_id',
), 'at_company_journal_unique');
            $table->unique(array (
  0 => 'reversed_transaction_id',
), 'at_reversal_unique');
            $table->foreign(array (
  0 => 'journal_item_id',
), 'at_journal_item_fk')->references(array (
  0 => 'id',
))->on('journal_items')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'reversed_transaction_id',
), 'at_reversal_fk')->references(array (
  0 => 'id',
))->on('account_transactions')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_transactions');
    }
};
