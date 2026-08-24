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
            DB::statement('CREATE TABLE `company_permission` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `permission_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_permission_company_id_permission_id_unique` (`company_id`,`permission_id`),
  KEY `company_permission_permission_id_foreign` (`permission_id`),
  CONSTRAINT `company_permission_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `company_permission_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('company_permission', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('permission_id');
            $table->unique(array (
  0 => 'company_id',
  1 => 'permission_id',
), 'company_permission_company_id_permission_id_unique');
            $table->index(array (
  0 => 'permission_id',
), 'company_permission_permission_id_foreign');
            $table->foreign(array (
  0 => 'company_id',
), 'company_permission_company_id_foreign')->references(array (
  0 => 'id',
))->on('companies')->onDelete('cascade')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'permission_id',
), 'company_permission_permission_id_foreign')->references(array (
  0 => 'id',
))->on('permissions')->onDelete('cascade')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_permission');
    }
};
