<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('service_categories', function (Blueprint $table) {

            $table->id();

            // Multi Tenant
            $table->unsignedBigInteger('company_id');

            // Category Info
            $table->string('name');
            $table->string('slug')->nullable();

            // Image / Upload
            $table->string('upload_path')->nullable();

            // Status
            $table->enum('status', ['active', 'inactive'])
                ->default('active');

            // Audit
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();

            // Index
            $table->index('company_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_categories');
    }
};