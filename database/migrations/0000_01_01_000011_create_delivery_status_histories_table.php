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
            DB::statement('CREATE TABLE `delivery_status_histories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `delivery_note_id` bigint(20) unsigned NOT NULL,
  `previous_status` varchar(20) DEFAULT NULL,
  `current_status` varchar(20) NOT NULL,
  `changed_by` bigint(20) unsigned DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `delivery_status_histories_note_index` (`delivery_note_id`),
  KEY `delivery_status_histories_company_note_index` (`company_id`,`delivery_note_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('delivery_status_histories', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('delivery_note_id');
            $table->string('previous_status', 20)->nullable();
            $table->string('current_status', 20);
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamp('changed_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->index(array (
  0 => 'company_id',
  1 => 'delivery_note_id',
), 'delivery_status_histories_company_note_index');
            $table->index(array (
  0 => 'delivery_note_id',
), 'delivery_status_histories_note_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_status_histories');
    }
};
