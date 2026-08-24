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
            DB::statement('CREATE TABLE `crm_follow_ups` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned DEFAULT NULL,
  `activity_no` varchar(50) NOT NULL,
  `crm_lead_id` bigint(20) unsigned DEFAULT NULL,
  `crm_opportunity_id` bigint(20) unsigned DEFAULT NULL,
  `follow_up_date` date NOT NULL,
  `next_follow_up_date` date DEFAULT NULL,
  `assigned_employee_id` bigint(20) unsigned NOT NULL,
  `priority` varchar(50) NOT NULL DEFAULT \'normal\',
  `status` varchar(50) NOT NULL DEFAULT \'pending\',
  `remarks` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `archived_by` bigint(20) unsigned DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  `archive_reason` text DEFAULT NULL,
  `cancelled_by` bigint(20) unsigned DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancel_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `crm_follow_ups_company_no_unique` (`company_id`,`activity_no`),
  KEY `crm_follow_ups_company_date_index` (`company_id`,`follow_up_date`),
  KEY `crm_follow_ups_company_next_date_index` (`company_id`,`next_follow_up_date`),
  KEY `crm_follow_ups_company_fy_index` (`company_id`,`financial_year_id`),
  KEY `crm_follow_ups_financial_year_id_foreign` (`financial_year_id`),
  KEY `crm_follow_ups_crm_lead_id_foreign` (`crm_lead_id`),
  KEY `crm_follow_ups_crm_opportunity_id_foreign` (`crm_opportunity_id`),
  KEY `crm_follow_ups_assigned_employee_id_foreign` (`assigned_employee_id`),
  KEY `crm_follow_ups_created_by_foreign` (`created_by`),
  KEY `crm_follow_ups_updated_by_foreign` (`updated_by`),
  KEY `crm_follow_ups_archived_by_foreign` (`archived_by`),
  KEY `crm_follow_ups_cancelled_by_foreign` (`cancelled_by`),
  CONSTRAINT `crm_follow_ups_archived_by_foreign` FOREIGN KEY (`archived_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_follow_ups_assigned_employee_id_foreign` FOREIGN KEY (`assigned_employee_id`) REFERENCES `employee_accounts` (`id`),
  CONSTRAINT `crm_follow_ups_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_follow_ups_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crm_follow_ups_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_follow_ups_crm_lead_id_foreign` FOREIGN KEY (`crm_lead_id`) REFERENCES `crm_leads` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_follow_ups_crm_opportunity_id_foreign` FOREIGN KEY (`crm_opportunity_id`) REFERENCES `crm_opportunities` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_follow_ups_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `financial_years` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_follow_ups_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('crm_follow_ups', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('financial_year_id')->nullable();
            $table->string('activity_no', 50);
            $table->unsignedBigInteger('crm_lead_id')->nullable();
            $table->unsignedBigInteger('crm_opportunity_id')->nullable();
            $table->date('follow_up_date');
            $table->date('next_follow_up_date')->nullable();
            $table->unsignedBigInteger('assigned_employee_id');
            $table->string('priority', 50)->default('normal');
            $table->string('status', 50)->default('pending');
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('archived_by')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->text('archive_reason')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(array (
  0 => 'archived_by',
), 'crm_follow_ups_archived_by_foreign');
            $table->index(array (
  0 => 'assigned_employee_id',
), 'crm_follow_ups_assigned_employee_id_foreign');
            $table->index(array (
  0 => 'cancelled_by',
), 'crm_follow_ups_cancelled_by_foreign');
            $table->index(array (
  0 => 'company_id',
  1 => 'follow_up_date',
), 'crm_follow_ups_company_date_index');
            $table->index(array (
  0 => 'company_id',
  1 => 'financial_year_id',
), 'crm_follow_ups_company_fy_index');
            $table->index(array (
  0 => 'company_id',
  1 => 'next_follow_up_date',
), 'crm_follow_ups_company_next_date_index');
            $table->unique(array (
  0 => 'company_id',
  1 => 'activity_no',
), 'crm_follow_ups_company_no_unique');
            $table->index(array (
  0 => 'created_by',
), 'crm_follow_ups_created_by_foreign');
            $table->index(array (
  0 => 'crm_lead_id',
), 'crm_follow_ups_crm_lead_id_foreign');
            $table->index(array (
  0 => 'crm_opportunity_id',
), 'crm_follow_ups_crm_opportunity_id_foreign');
            $table->index(array (
  0 => 'financial_year_id',
), 'crm_follow_ups_financial_year_id_foreign');
            $table->index(array (
  0 => 'updated_by',
), 'crm_follow_ups_updated_by_foreign');
            $table->foreign(array (
  0 => 'archived_by',
), 'crm_follow_ups_archived_by_foreign')->references(array (
  0 => 'id',
))->on('users')->onDelete('set null')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'assigned_employee_id',
), 'crm_follow_ups_assigned_employee_id_foreign')->references(array (
  0 => 'id',
))->on('employee_accounts')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'cancelled_by',
), 'crm_follow_ups_cancelled_by_foreign')->references(array (
  0 => 'id',
))->on('users')->onDelete('set null')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'company_id',
), 'crm_follow_ups_company_id_foreign')->references(array (
  0 => 'id',
))->on('companies')->onDelete('cascade')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'created_by',
), 'crm_follow_ups_created_by_foreign')->references(array (
  0 => 'id',
))->on('users')->onDelete('set null')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'crm_lead_id',
), 'crm_follow_ups_crm_lead_id_foreign')->references(array (
  0 => 'id',
))->on('crm_leads')->onDelete('set null')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'crm_opportunity_id',
), 'crm_follow_ups_crm_opportunity_id_foreign')->references(array (
  0 => 'id',
))->on('crm_opportunities')->onDelete('set null')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'financial_year_id',
), 'crm_follow_ups_financial_year_id_foreign')->references(array (
  0 => 'id',
))->on('financial_years')->onDelete('set null')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'updated_by',
), 'crm_follow_ups_updated_by_foreign')->references(array (
  0 => 'id',
))->on('users')->onDelete('set null')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_follow_ups');
    }
};
