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
            DB::statement('CREATE TABLE `opening_balance_legacy_records` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `source_table` varchar(255) NOT NULL,
  `source_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `stored_amount` decimal(20,4) NOT NULL,
  `classification` varchar(30) NOT NULL,
  `evidence` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ob_legacy_source_unique` (`source_table`,`source_id`),
  KEY `ob_legacy_company_class_idx` (`company_id`,`classification`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('opening_balance_legacy_records', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('source_table', 255);
            $table->unsignedBigInteger('source_id');
            $table->unsignedBigInteger('company_id');
            $table->decimal('stored_amount', 20, 4);
            $table->string('classification', 30);
            $table->text('evidence');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(array (
  0 => 'company_id',
  1 => 'classification',
), 'ob_legacy_company_class_idx');
            $table->unique(array (
  0 => 'source_table',
  1 => 'source_id',
), 'ob_legacy_source_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opening_balance_legacy_records');
    }
};
