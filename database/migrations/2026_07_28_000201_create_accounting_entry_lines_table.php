<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_entry_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accounting_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('chart_account_id')->constrained('chart_accounts')->restrictOnDelete();
            $table->foreignId('operational_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->unsignedInteger('line_number');
            $table->text('description')->nullable();
            $table->decimal('debit', 20, 4)->default(0);
            $table->decimal('credit', 20, 4)->default(0);
            $table->string('subledger_type', 50)->nullable();
            $table->unsignedBigInteger('subledger_id')->nullable();
            $table->timestamps();

            $table->unique(['accounting_entry_id', 'line_number']);
            $table->index(['subledger_type', 'subledger_id']);
            $table->index(
                ['chart_account_id', 'accounting_entry_id'],
                'ael_chart_entry_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_entry_lines');
    }
};
