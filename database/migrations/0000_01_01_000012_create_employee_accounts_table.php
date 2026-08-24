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
            DB::statement('CREATE TABLE `employee_accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `employee_code` varchar(255) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `middle_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `gender` varchar(255) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `joining_date` date NOT NULL,
  `designation` varchar(255) DEFAULT NULL,
  `department` varchar(255) DEFAULT NULL,
  `post` varchar(255) DEFAULT NULL,
  `employment_type` enum(\'permanent\',\'contract\',\'temporary\',\'intern\') NOT NULL DEFAULT \'permanent\',
  `basic_salary` decimal(18,2) NOT NULL DEFAULT 0.00,
  `salary_type` enum(\'monthly\',\'daily\') NOT NULL DEFAULT \'monthly\',
  `opening_due_salary` decimal(18,2) NOT NULL DEFAULT 0.00,
  `bank_name` varchar(255) DEFAULT NULL,
  `bank_account_no` varchar(255) DEFAULT NULL,
  `account_holder_name` varchar(255) DEFAULT NULL,
  `cit_no` varchar(255) DEFAULT NULL,
  `pan_no` varchar(255) DEFAULT NULL,
  `emergency_contact` varchar(255) DEFAULT NULL,
  `emergency_phone` varchar(255) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `cv_attachment` varchar(255) DEFAULT NULL,
  `id_document` varchar(255) DEFAULT NULL,
  `contract_document` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_accounts_company_code_unique` (`company_id`,`employee_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('employee_accounts', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->string('employee_code', 255);
            $table->string('first_name', 255);
            $table->string('middle_name', 255)->nullable();
            $table->string('last_name', 255)->nullable();
            $table->string('phone', 255)->nullable();
            $table->string('email', 255)->nullable();
            $table->text('address')->nullable();
            $table->string('gender', 255)->nullable();
            $table->date('dob')->nullable();
            $table->date('joining_date');
            $table->string('designation', 255)->nullable();
            $table->string('department', 255)->nullable();
            $table->string('post', 255)->nullable();
            $table->string('employment_type')->default('permanent');
            $table->decimal('basic_salary', 18, 2)->default('0.00');
            $table->string('salary_type')->default('monthly');
            $table->decimal('opening_due_salary', 18, 2)->default('0.00');
            $table->string('bank_name', 255)->nullable();
            $table->string('bank_account_no', 255)->nullable();
            $table->string('account_holder_name', 255)->nullable();
            $table->string('cit_no', 255)->nullable();
            $table->string('pan_no', 255)->nullable();
            $table->string('emergency_contact', 255)->nullable();
            $table->string('emergency_phone', 255)->nullable();
            $table->string('photo', 255)->nullable();
            $table->string('cv_attachment', 255)->nullable();
            $table->string('id_document', 255)->nullable();
            $table->string('contract_document', 255)->nullable();
            $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->tinyInteger('status')->default('1');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unique(array (
  0 => 'company_id',
  1 => 'employee_code',
), 'employee_accounts_company_code_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_accounts');
    }
};
