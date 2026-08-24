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
            DB::statement('CREATE TABLE `crm_notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `entity_type` varchar(50) NOT NULL,
  `entity_id` bigint(20) unsigned NOT NULL,
  `note` text NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT \'active\',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `archived_by` bigint(20) unsigned DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  `archive_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `crm_notes_entity_index` (`company_id`,`entity_type`,`entity_id`),
  KEY `crm_notes_created_by_foreign` (`created_by`),
  KEY `crm_notes_updated_by_foreign` (`updated_by`),
  KEY `crm_notes_archived_by_foreign` (`archived_by`),
  CONSTRAINT `crm_notes_archived_by_foreign` FOREIGN KEY (`archived_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_notes_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crm_notes_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_notes_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('crm_notes', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->string('entity_type', 50);
            $table->unsignedBigInteger('entity_id');
            $table->text('note');
            $table->string('status', 50)->default('active');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('archived_by')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->text('archive_reason')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(array (
  0 => 'archived_by',
), 'crm_notes_archived_by_foreign');
            $table->index(array (
  0 => 'created_by',
), 'crm_notes_created_by_foreign');
            $table->index(array (
  0 => 'company_id',
  1 => 'entity_type',
  2 => 'entity_id',
), 'crm_notes_entity_index');
            $table->index(array (
  0 => 'updated_by',
), 'crm_notes_updated_by_foreign');
            $table->foreign(array (
  0 => 'archived_by',
), 'crm_notes_archived_by_foreign')->references(array (
  0 => 'id',
))->on('users')->onDelete('set null')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'company_id',
), 'crm_notes_company_id_foreign')->references(array (
  0 => 'id',
))->on('companies')->onDelete('cascade')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'created_by',
), 'crm_notes_created_by_foreign')->references(array (
  0 => 'id',
))->on('users')->onDelete('set null')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'updated_by',
), 'crm_notes_updated_by_foreign')->references(array (
  0 => 'id',
))->on('users')->onDelete('set null')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_notes');
    }
};
