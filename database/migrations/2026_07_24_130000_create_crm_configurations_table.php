<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_configurations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('config_type', 50);
            $table->string('config_key', 50);
            $table->string('config_label', 100);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'config_type', 'config_key'], 'crm_config_company_type_key_unique');
            $table->index(['company_id', 'config_type', 'is_active'], 'crm_config_company_type_active_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_configurations');
    }
};
