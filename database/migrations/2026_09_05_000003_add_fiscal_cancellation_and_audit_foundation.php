<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['sales_invoices', 'sales_returns'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->timestamp('cancelled_at')->nullable();
                $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('cancellation_reason')->nullable();
                $table->string('original_fiscal_reference')->nullable();
            });
        }

        Schema::create('fiscal_document_audit_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('document_type', 40);
            $table->unsignedBigInteger('document_id');
            $table->string('document_number');
            $table->string('event_type', 40);
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('event_at');
            $table->json('metadata')->nullable();
            $table->string('deduplication_key')->nullable()->unique();
            $table->timestamps();
            $table->index(['company_id', 'document_type', 'document_id'], 'fiscal_document_history_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fiscal_document_audit_events');
        foreach (['sales_returns', 'sales_invoices'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropConstrainedForeignId('cancelled_by');
                $table->dropColumn(['cancelled_at', 'cancellation_reason', 'original_fiscal_reference']);
            });
        }
    }
};
