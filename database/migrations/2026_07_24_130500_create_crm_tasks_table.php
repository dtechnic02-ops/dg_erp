<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('activity_no', 50);
            $table->unsignedBigInteger('crm_lead_id')->nullable();
            $table->unsignedBigInteger('crm_opportunity_id')->nullable();
            $table->string('task_type', 50)->default('call');
            $table->string('task_status', 50)->default('pending');
            $table->string('priority', 50)->default('normal');
            $table->date('due_date');
            $table->unsignedBigInteger('assigned_employee_id');
            $table->text('remarks')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'activity_no'], 'crm_tasks_company_no_unique');
            $table->index(['company_id', 'due_date'], 'crm_tasks_company_due_index');
            $table->index(['company_id', 'task_status'], 'crm_tasks_company_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_tasks');
    }
};
