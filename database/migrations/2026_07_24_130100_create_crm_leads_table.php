<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_leads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('financial_year_id')->nullable();
            $table->string('lead_no', 50);
            $table->string('customer_name');
            $table->string('contact_person')->nullable();
            $table->string('mobile', 30)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('country', 100)->nullable();
            $table->string('industry', 100)->nullable();
            $table->string('lead_source', 50)->nullable();
            $table->unsignedBigInteger('assigned_employee_id');
            $table->string('status', 50)->default('new');
            $table->string('priority', 50)->default('normal');
            $table->decimal('expected_value', 15, 2)->default(0);
            $table->date('lead_date');
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('converted_customer_id')->nullable();
            $table->unsignedBigInteger('converted_by')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->text('conversion_remarks')->nullable();
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->text('close_reason')->nullable();
            $table->unsignedBigInteger('archived_by')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'lead_no'], 'crm_leads_company_no_unique');
            $table->index(['company_id', 'lead_date'], 'crm_leads_company_date_index');
            $table->index(['company_id', 'status'], 'crm_leads_company_status_index');
            $table->index(['company_id', 'assigned_employee_id'], 'crm_leads_company_employee_index');
            $table->index(['company_id', 'financial_year_id'], 'crm_leads_company_fy_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_leads');
    }
};
