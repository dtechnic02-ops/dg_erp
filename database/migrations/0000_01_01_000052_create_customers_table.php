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
            DB::statement('CREATE TABLE `customers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `crm_lead_id` bigint(20) unsigned DEFAULT NULL,
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
  `opening_balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `credit_days` int(10) unsigned NOT NULL DEFAULT 0,
  `current_balance` decimal(18,2) NOT NULL DEFAULT 0.00,
  `bank_name` varchar(255) DEFAULT NULL,
  `bank_account_no` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT \'active\',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customers_company_crm_lead_index` (`company_id`,`crm_lead_id`),
  CONSTRAINT `customers_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('customers', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('crm_lead_id')->nullable();
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
            $table->decimal('opening_balance', 10, 2)->default('0.00');
            $table->unsignedInteger('credit_days')->default('0');
            $table->decimal('current_balance', 18, 2)->default('0.00');
            $table->string('bank_name', 255)->nullable();
            $table->string('bank_account_no', 255)->nullable();
            $table->text('note')->nullable();
            $table->string('image_path', 255)->nullable();
            $table->string('status', 255)->default('active');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(array (
  0 => 'company_id',
  1 => 'crm_lead_id',
), 'customers_company_crm_lead_index');
            $table->foreign(array (
  0 => 'company_id',
), 'customers_company_id_foreign')->references(array (
  0 => 'id',
))->on('companies')->onDelete('cascade')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
