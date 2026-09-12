<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cbms_transmission_attempts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('cbms_transmission_id')->constrained('cbms_transmissions')->restrictOnDelete();
            $table->unsignedInteger('attempt_number');
            $table->timestamp('attempted_at');
            $table->timestamp('finished_at')->nullable();
            $table->string('transport_classification', 80);
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->string('response_code', 20)->nullable();
            $table->string('parser_classification', 80)->nullable();
            $table->text('response_excerpt_redacted')->nullable();
            $table->char('payload_hash', 64);
            $table->boolean('is_realtime');
            $table->string('result_status', 50);
            $table->timestamps();
            $table->unique(['cbms_transmission_id', 'attempt_number'], 'cbms_attempts_transmission_number_unique');
            $table->index(['company_id', 'attempted_at'], 'cbms_attempts_company_time_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cbms_transmission_attempts');
    }
};
