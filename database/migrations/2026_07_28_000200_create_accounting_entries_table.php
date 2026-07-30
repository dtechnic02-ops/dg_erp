<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('entry_number');
            $table->date('entry_date');
            $table->string('reference_number')->nullable();
            $table->string('source_module');
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('source_event')->nullable();
            $table->string('source_key')->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['draft', 'posted', 'reversed'])->default('draft');
            $table->foreignId('reversal_of_id')->nullable()->constrained('accounting_entries')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'entry_number']);
            $table->unique(['company_id', 'source_key']);
            $table->index('entry_date');
            $table->index('status');
            $table->index('source_module');
            $table->index(['source_type', 'source_id']);
            $table->index(['company_id', 'entry_date']);
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_entries');
    }
};
