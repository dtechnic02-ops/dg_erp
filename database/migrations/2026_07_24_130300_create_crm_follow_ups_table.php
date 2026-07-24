<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_follow_ups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
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
            $table->timestamps();

            $table->unique(['company_id', 'activity_no'], 'crm_follow_ups_company_no_unique');
            $table->index(['company_id', 'follow_up_date'], 'crm_follow_ups_company_date_index');
            $table->index(['company_id', 'next_follow_up_date'], 'crm_follow_ups_company_next_date_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_follow_ups');
    }
};
