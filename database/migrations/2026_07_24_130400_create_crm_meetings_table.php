<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_meetings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('activity_no', 50);
            $table->unsignedBigInteger('crm_lead_id')->nullable();
            $table->unsignedBigInteger('crm_opportunity_id')->nullable();
            $table->date('meeting_date');
            $table->time('meeting_time')->nullable();
            $table->unsignedBigInteger('assigned_employee_id');
            $table->string('location')->nullable();
            $table->string('status', 50)->default('scheduled');
            $table->text('remarks')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'activity_no'], 'crm_meetings_company_no_unique');
            $table->index(['company_id', 'meeting_date'], 'crm_meetings_company_date_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_meetings');
    }
};
