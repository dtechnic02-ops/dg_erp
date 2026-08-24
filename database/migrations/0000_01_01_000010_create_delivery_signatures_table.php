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
            DB::statement('CREATE TABLE `delivery_signatures` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `delivery_note_id` bigint(20) unsigned NOT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `receiver_name` varchar(255) DEFAULT NULL,
  `receiver_mobile` varchar(30) DEFAULT NULL,
  `signature_path` varchar(255) NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `delivery_signatures_note_unique` (`delivery_note_id`),
  KEY `delivery_signatures_company_note_index` (`company_id`,`delivery_note_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('delivery_signatures', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('delivery_note_id');
            $table->string('customer_name', 255)->nullable();
            $table->string('receiver_name', 255)->nullable();
            $table->string('receiver_mobile', 30)->nullable();
            $table->string('signature_path', 255);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(array (
  0 => 'company_id',
  1 => 'delivery_note_id',
), 'delivery_signatures_company_note_index');
            $table->unique(array (
  0 => 'delivery_note_id',
), 'delivery_signatures_note_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_signatures');
    }
};
