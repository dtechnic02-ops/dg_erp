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
            DB::statement('CREATE TABLE `journal_audit_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned NOT NULL,
  `journal_id` bigint(20) unsigned NOT NULL,
  `event` varchar(40) NOT NULL,
  `previous_status` varchar(20) DEFAULT NULL,
  `new_status` varchar(20) DEFAULT NULL,
  `actor_id` bigint(20) unsigned NOT NULL,
  `event_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `reason` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  PRIMARY KEY (`id`),
  KEY `journal_audit_events_financial_year_id_foreign` (`financial_year_id`),
  KEY `journal_audit_events_journal_id_foreign` (`journal_id`),
  KEY `journal_audit_events_actor_id_foreign` (`actor_id`),
  KEY `journal_audit_company_fy_date_idx` (`company_id`,`financial_year_id`,`event_at`),
  CONSTRAINT `journal_audit_events_actor_id_foreign` FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`),
  CONSTRAINT `journal_audit_events_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `journal_audit_events_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `financial_years` (`id`),
  CONSTRAINT `journal_audit_events_journal_id_foreign` FOREIGN KEY (`journal_id`) REFERENCES `journals` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('journal_audit_events', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('financial_year_id');
            $table->unsignedBigInteger('journal_id');
            $table->string('event', 40);
            $table->string('previous_status', 20)->nullable();
            $table->string('new_status', 20)->nullable();
            $table->unsignedBigInteger('actor_id');
            $table->timestamp('event_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->text('reason')->nullable();
            $table->longText('metadata')->nullable();
            $table->index(array (
  0 => 'company_id',
  1 => 'financial_year_id',
  2 => 'event_at',
), 'journal_audit_company_fy_date_idx');
            $table->index(array (
  0 => 'actor_id',
), 'journal_audit_events_actor_id_foreign');
            $table->index(array (
  0 => 'financial_year_id',
), 'journal_audit_events_financial_year_id_foreign');
            $table->index(array (
  0 => 'journal_id',
), 'journal_audit_events_journal_id_foreign');
            $table->foreign(array (
  0 => 'actor_id',
), 'journal_audit_events_actor_id_foreign')->references(array (
  0 => 'id',
))->on('users')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'company_id',
), 'journal_audit_events_company_id_foreign')->references(array (
  0 => 'id',
))->on('companies')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'financial_year_id',
), 'journal_audit_events_financial_year_id_foreign')->references(array (
  0 => 'id',
))->on('financial_years')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'journal_id',
), 'journal_audit_events_journal_id_foreign')->references(array (
  0 => 'id',
))->on('journals')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_audit_events');
    }
};
