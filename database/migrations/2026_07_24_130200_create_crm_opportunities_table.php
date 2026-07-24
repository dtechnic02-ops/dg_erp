<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_opportunities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('financial_year_id')->nullable();
            $table->string('opportunity_no', 50);
            $table->unsignedBigInteger('crm_lead_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('title');
            $table->decimal('potential_value', 15, 2)->default(0);
            $table->date('expected_closing_date')->nullable();
            $table->decimal('probability', 5, 2)->default(0);
            $table->string('stage', 50)->default('discovery');
            $table->unsignedBigInteger('assigned_employee_id');
            $table->string('status', 50)->default('open');
            $table->text('remarks')->nullable();
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

            $table->unique(['company_id', 'opportunity_no'], 'crm_opportunities_company_no_unique');
            $table->index(['company_id', 'stage'], 'crm_opportunities_company_stage_index');
            $table->index(['company_id', 'status'], 'crm_opportunities_company_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_opportunities');
    }
};
