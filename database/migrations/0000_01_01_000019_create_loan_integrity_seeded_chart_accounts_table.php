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
            DB::statement('CREATE TABLE `loan_integrity_seeded_chart_accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `chart_account_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `system_code` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `loan_integrity_seeded_chart_accounts_chart_account_id_unique` (`chart_account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('loan_integrity_seeded_chart_accounts', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('chart_account_id');
            $table->unsignedBigInteger('company_id');
            $table->string('system_code', 255);
            $table->unique(array (
  0 => 'chart_account_id',
), 'loan_integrity_seeded_chart_accounts_chart_account_id_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_integrity_seeded_chart_accounts');
    }
};
