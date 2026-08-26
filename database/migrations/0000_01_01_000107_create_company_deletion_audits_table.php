<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_deletion_audits', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('deleted_company_id');
            $table->string('deleted_company_name');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('requested_at');
            $table->timestamp('completed_at')->nullable();
            $table->string('result', 30);
            $table->json('deleted_counts')->nullable();
            $table->json('file_manifest')->nullable();
            $table->string('file_cleanup_state', 30)->default('pending');
            $table->text('safe_error')->nullable();
            $table->timestamps();

            $table->index(['deleted_company_id', 'completed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_deletion_audits');
    }
};
