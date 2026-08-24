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
            DB::statement('CREATE TABLE `customer_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `transaction_date` date NOT NULL,
  `voucher_no` varchar(255) DEFAULT NULL,
  `reference_type` varchar(50) NOT NULL,
  `reference_id` bigint(20) unsigned DEFAULT NULL,
  `journal_item_id` bigint(20) unsigned DEFAULT NULL,
  `reversed_transaction_id` bigint(20) unsigned DEFAULT NULL,
  `reference_no` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `debit` decimal(18,2) NOT NULL DEFAULT 0.00,
  `credit` decimal(18,2) NOT NULL DEFAULT 0.00,
  `balance` decimal(18,2) NOT NULL DEFAULT 0.00,
  `remarks` varchar(255) DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ct_company_journal_unique` (`company_id`,`journal_item_id`),
  UNIQUE KEY `ct_reversal_unique` (`reversed_transaction_id`),
  KEY `customer_transactions_company_id_customer_id_index` (`company_id`,`customer_id`),
  KEY `customer_transactions_financial_year_id_index` (`financial_year_id`),
  KEY `customer_transactions_transaction_date_index` (`transaction_date`),
  KEY `customer_transactions_reference_type_reference_id_index` (`reference_type`,`reference_id`),
  KEY `customer_transactions_voucher_no_index` (`voucher_no`),
  KEY `customer_transactions_journal_item_id_index` (`journal_item_id`),
  KEY `customer_transactions_reversed_transaction_id_index` (`reversed_transaction_id`),
  CONSTRAINT `ct_journal_item_fk` FOREIGN KEY (`journal_item_id`) REFERENCES `journal_items` (`id`),
  CONSTRAINT `ct_reversal_fk` FOREIGN KEY (`reversed_transaction_id`) REFERENCES `customer_transactions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('customer_transactions', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('financial_year_id');
            $table->unsignedBigInteger('customer_id');
            $table->date('transaction_date');
            $table->string('voucher_no', 255)->nullable();
            $table->string('reference_type', 50);
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->unsignedBigInteger('journal_item_id')->nullable();
            $table->unsignedBigInteger('reversed_transaction_id')->nullable();
            $table->string('reference_no', 255)->nullable();
            $table->string('description', 255)->nullable();
            $table->decimal('debit', 18, 2)->default('0.00');
            $table->decimal('credit', 18, 2)->default('0.00');
            $table->decimal('balance', 18, 2)->default('0.00');
            $table->string('remarks', 255)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->boolean('status')->default('1');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unique(array (
  0 => 'company_id',
  1 => 'journal_item_id',
), 'ct_company_journal_unique');
            $table->unique(array (
  0 => 'reversed_transaction_id',
), 'ct_reversal_unique');
            $table->index(array (
  0 => 'company_id',
  1 => 'customer_id',
), 'customer_transactions_company_id_customer_id_index');
            $table->index(array (
  0 => 'financial_year_id',
), 'customer_transactions_financial_year_id_index');
            $table->index(array (
  0 => 'journal_item_id',
), 'customer_transactions_journal_item_id_index');
            $table->index(array (
  0 => 'reference_type',
  1 => 'reference_id',
), 'customer_transactions_reference_type_reference_id_index');
            $table->index(array (
  0 => 'reversed_transaction_id',
), 'customer_transactions_reversed_transaction_id_index');
            $table->index(array (
  0 => 'transaction_date',
), 'customer_transactions_transaction_date_index');
            $table->index(array (
  0 => 'voucher_no',
), 'customer_transactions_voucher_no_index');
            $table->foreign(array (
  0 => 'journal_item_id',
), 'ct_journal_item_fk')->references(array (
  0 => 'id',
))->on('journal_items')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'reversed_transaction_id',
), 'ct_reversal_fk')->references(array (
  0 => 'id',
))->on('customer_transactions')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_transactions');
    }
};
