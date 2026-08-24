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
            DB::statement('CREATE TABLE `crm_opportunities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned DEFAULT NULL,
  `opportunity_no` varchar(50) NOT NULL,
  `crm_lead_id` bigint(20) unsigned NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `potential_value` decimal(15,2) NOT NULL DEFAULT 0.00,
  `expected_closing_date` date DEFAULT NULL,
  `probability` decimal(5,2) NOT NULL DEFAULT 0.00,
  `stage` varchar(50) NOT NULL DEFAULT \'discovery\',
  `assigned_employee_id` bigint(20) unsigned NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT \'open\',
  `remarks` text DEFAULT NULL,
  `closed_by` bigint(20) unsigned DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `close_reason` text DEFAULT NULL,
  `archived_by` bigint(20) unsigned DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  `archive_reason` text DEFAULT NULL,
  `cancelled_by` bigint(20) unsigned DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancel_reason` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `crm_opportunities_company_no_unique` (`company_id`,`opportunity_no`),
  KEY `crm_opportunities_company_stage_index` (`company_id`,`stage`),
  KEY `crm_opportunities_company_status_index` (`company_id`,`status`),
  KEY `crm_opportunities_financial_year_id_foreign` (`financial_year_id`),
  KEY `crm_opportunities_assigned_employee_id_foreign` (`assigned_employee_id`),
  KEY `crm_opportunities_created_by_foreign` (`created_by`),
  KEY `crm_opportunities_updated_by_foreign` (`updated_by`),
  KEY `crm_opportunities_closed_by_foreign` (`closed_by`),
  KEY `crm_opportunities_archived_by_foreign` (`archived_by`),
  KEY `crm_opportunities_cancelled_by_foreign` (`cancelled_by`),
  KEY `crm_opportunities_company_customer_index` (`company_id`,`customer_id`),
  KEY `crm_opportunities_customer_id_foreign` (`customer_id`),
  KEY `crm_opportunities_crm_lead_id_foreign` (`crm_lead_id`),
  CONSTRAINT `crm_opportunities_archived_by_foreign` FOREIGN KEY (`archived_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_opportunities_assigned_employee_id_foreign` FOREIGN KEY (`assigned_employee_id`) REFERENCES `employee_accounts` (`id`),
  CONSTRAINT `crm_opportunities_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_opportunities_closed_by_foreign` FOREIGN KEY (`closed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_opportunities_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crm_opportunities_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_opportunities_crm_lead_id_foreign` FOREIGN KEY (`crm_lead_id`) REFERENCES `crm_leads` (`id`),
  CONSTRAINT `crm_opportunities_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `crm_opportunities_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `financial_years` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_opportunities_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            return;
        }

        Schema::create('crm_opportunities', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('financial_year_id')->nullable();
            $table->string('opportunity_no', 50);
            $table->unsignedBigInteger('crm_lead_id');
            $table->unsignedBigInteger('customer_id');
            $table->string('title', 255);
            $table->decimal('potential_value', 15, 2)->default('0.00');
            $table->date('expected_closing_date')->nullable();
            $table->decimal('probability', 5, 2)->default('0.00');
            $table->string('stage', 50)->default('discovery');
            $table->unsignedBigInteger('assigned_employee_id');
            $table->string('status', 50)->default('open');
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->text('close_reason')->nullable();
            $table->unsignedBigInteger('archived_by')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->text('archive_reason')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(array (
  0 => 'archived_by',
), 'crm_opportunities_archived_by_foreign');
            $table->index(array (
  0 => 'assigned_employee_id',
), 'crm_opportunities_assigned_employee_id_foreign');
            $table->index(array (
  0 => 'cancelled_by',
), 'crm_opportunities_cancelled_by_foreign');
            $table->index(array (
  0 => 'closed_by',
), 'crm_opportunities_closed_by_foreign');
            $table->index(array (
  0 => 'company_id',
  1 => 'customer_id',
), 'crm_opportunities_company_customer_index');
            $table->unique(array (
  0 => 'company_id',
  1 => 'opportunity_no',
), 'crm_opportunities_company_no_unique');
            $table->index(array (
  0 => 'company_id',
  1 => 'stage',
), 'crm_opportunities_company_stage_index');
            $table->index(array (
  0 => 'company_id',
  1 => 'status',
), 'crm_opportunities_company_status_index');
            $table->index(array (
  0 => 'created_by',
), 'crm_opportunities_created_by_foreign');
            $table->index(array (
  0 => 'crm_lead_id',
), 'crm_opportunities_crm_lead_id_foreign');
            $table->index(array (
  0 => 'customer_id',
), 'crm_opportunities_customer_id_foreign');
            $table->index(array (
  0 => 'financial_year_id',
), 'crm_opportunities_financial_year_id_foreign');
            $table->index(array (
  0 => 'updated_by',
), 'crm_opportunities_updated_by_foreign');
            $table->foreign(array (
  0 => 'archived_by',
), 'crm_opportunities_archived_by_foreign')->references(array (
  0 => 'id',
))->on('users')->onDelete('set null')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'assigned_employee_id',
), 'crm_opportunities_assigned_employee_id_foreign')->references(array (
  0 => 'id',
))->on('employee_accounts')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'cancelled_by',
), 'crm_opportunities_cancelled_by_foreign')->references(array (
  0 => 'id',
))->on('users')->onDelete('set null')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'closed_by',
), 'crm_opportunities_closed_by_foreign')->references(array (
  0 => 'id',
))->on('users')->onDelete('set null')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'company_id',
), 'crm_opportunities_company_id_foreign')->references(array (
  0 => 'id',
))->on('companies')->onDelete('cascade')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'created_by',
), 'crm_opportunities_created_by_foreign')->references(array (
  0 => 'id',
))->on('users')->onDelete('set null')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'crm_lead_id',
), 'crm_opportunities_crm_lead_id_foreign')->references(array (
  0 => 'id',
))->on('crm_leads')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'customer_id',
), 'crm_opportunities_customer_id_foreign')->references(array (
  0 => 'id',
))->on('customers')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'financial_year_id',
), 'crm_opportunities_financial_year_id_foreign')->references(array (
  0 => 'id',
))->on('financial_years')->onDelete('set null')->onUpdate('restrict');
            $table->foreign(array (
  0 => 'updated_by',
), 'crm_opportunities_updated_by_foreign')->references(array (
  0 => 'id',
))->on('users')->onDelete('set null')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_opportunities');
    }
};
