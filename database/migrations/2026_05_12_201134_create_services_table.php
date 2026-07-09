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
        Schema::create('services', function (Blueprint $table) {

            $table->id();

            // Multi Tenant
            $table->unsignedBigInteger('company_id');

            // Category
            $table->unsignedBigInteger('service_category_id')
                ->nullable();

            // Service Info
            $table->string('name');

            $table->string('service_code')
                ->nullable();

            $table->string('slug')
                ->nullable();

            // Pricing
            $table->decimal('price', 15, 2)
                ->default(0);

            // VAT
            $table->unsignedBigInteger('vat_id')
                ->nullable();

            // Upload / Image
            $table->string('upload_path')
                ->nullable();

            // Description
            $table->text('description')
                ->nullable();

            // Status
            $table->enum('status', ['active', 'inactive'])
                ->default('active');

            // Audit
            $table->unsignedBigInteger('created_by')
                ->nullable();

            $table->timestamps();

            // Indexes
            $table->index('company_id');
            $table->index('service_category_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};