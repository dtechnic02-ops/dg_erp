<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_contacts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('financial_year_id')->nullable();
            $table->string('contact_no', 50);
            $table->unsignedBigInteger('crm_lead_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('name');
            $table->string('designation')->nullable();
            $table->string('mobile', 30)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->unsignedBigInteger('assigned_employee_id');
            $table->string('status', 50)->default('active');
            $table->string('priority', 50)->default('normal');
            $table->date('contact_date');
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
            $table->timestamps();

            $table->unique(['company_id', 'contact_no'], 'crm_contacts_company_no_unique');
            $table->index(['company_id', 'contact_date'], 'crm_contacts_company_date_index');
            $table->index(['company_id', 'status'], 'crm_contacts_company_status_index');
            $table->index(['company_id', 'assigned_employee_id'], 'crm_contacts_company_employee_index');
            $table->index(['company_id', 'financial_year_id'], 'crm_contacts_company_fy_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_contacts');
    }
};
