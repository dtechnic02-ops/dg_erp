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
            DB::statement('CREATE TABLE `crm_status_histories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `entity_type` varchar(50) NOT NULL,
  `entity_id` bigint(20) unsigned NOT NULL,
  `event` varchar(100) NOT NULL,
  `previous_value` varchar(255) DEFAULT NULL,
  `current_value` varchar(255) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `changed_by` bigint(20) unsigned DEFAULT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `crm_status_histories_entity_index` (`company_id`,`entity_type`,`entity_id`),
  KEY `crm_status_histories_changed_by_foreign` (`changed_by`),
  CONSTRAINT `crm_status_histories_changed_by_foreign` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_status_histories_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('crm_status_histories', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->string('entity_type', 50);
            $table->unsignedBigInteger('entity_id');
            $table->string('event', 100);
            $table->string('previous_value', 255)->nullable();
            $table->string('current_value', 255)->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->timestamp('changed_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->index(array (
  0 => 'changed_by',
), 'crm_status_histories_changed_by_foreign');
            $table->index(array (
  0 => 'company_id',
  1 => 'entity_type',
  2 => 'entity_id',
), 'crm_status_histories_entity_index');
            $table->foreign(array (
  0 => 'changed_by',
), 'crm_status_histories_changed_by_foreign')->references(array (
  0 => 'id',
))->on('users')->onDelete('set null')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'company_id',
), 'crm_status_histories_company_id_foreign')->references(array (
  0 => 'id',
))->on('companies')->onDelete('cascade')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_status_histories');
    }
};
