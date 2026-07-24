<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_status_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('entity_type', 50);
            $table->unsignedBigInteger('entity_id');
            $table->string('event', 100);
            $table->string('previous_value')->nullable();
            $table->string('current_value')->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->timestamp('changed_at');

            $table->index(['company_id', 'entity_type', 'entity_id'], 'crm_status_histories_entity_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_status_histories');
    }
};
