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
            DB::statement('CREATE TABLE `delivery_attachments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `delivery_note_id` bigint(20) unsigned NOT NULL,
  `document_type` enum(\'photo\',\'additional_photo\',\'attachment\',\'pdf\') NOT NULL DEFAULT \'photo\',
  `file_path` varchar(255) NOT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `delivery_attachments_note_type_index` (`delivery_note_id`,`document_type`),
  KEY `delivery_attachments_company_note_index` (`company_id`,`delivery_note_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('delivery_attachments', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('delivery_note_id');
            $table->string('document_type')->default('photo');
            $table->string('file_path', 255);
            $table->string('original_name', 255)->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(array (
  0 => 'company_id',
  1 => 'delivery_note_id',
), 'delivery_attachments_company_note_index');
            $table->index(array (
  0 => 'delivery_note_id',
  1 => 'document_type',
), 'delivery_attachments_note_type_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_attachments');
    }
};
