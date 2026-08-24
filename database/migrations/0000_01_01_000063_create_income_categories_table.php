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
            DB::statement('CREATE TABLE `income_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `chart_account_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) DEFAULT NULL,
  `note` longtext DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `created_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `income_categories_chart_account_id_foreign` (`chart_account_id`),
  CONSTRAINT `income_categories_chart_account_id_foreign` FOREIGN KEY (`chart_account_id`) REFERENCES `chart_accounts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('income_categories', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('chart_account_id');
            $table->string('name', 255);
            $table->string('code', 255)->nullable();
            $table->longText('note')->nullable();
            $table->tinyInteger('status')->default('1');
            $table->unsignedBigInteger('created_by');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(array (
  0 => 'chart_account_id',
), 'income_categories_chart_account_id_foreign');
            $table->foreign(array (
  0 => 'chart_account_id',
), 'income_categories_chart_account_id_foreign')->references(array (
  0 => 'id',
))->on('chart_accounts')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('income_categories');
    }
};
