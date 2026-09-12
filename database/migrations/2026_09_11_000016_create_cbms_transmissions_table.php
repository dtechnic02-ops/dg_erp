<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cbms_transmissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->string('transmittable_type');
            $table->unsignedBigInteger('transmittable_id');
            $table->string('endpoint_type', 30);
            $table->string('status', 50)->default('pending');
            $table->unsignedInteger('attempt_count')->default(0);
            $table->timestamp('last_attempted_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->string('response_code', 20)->nullable();
            $table->string('response_category', 80)->nullable();
            $table->char('payload_hash', 64)->nullable();
            $table->json('response_body_redacted')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'transmittable_type', 'transmittable_id', 'endpoint_type'], 'cbms_transmissions_document_endpoint_unique');
            $table->index(['company_id', 'status'], 'cbms_transmissions_company_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cbms_transmissions');
    }
};
