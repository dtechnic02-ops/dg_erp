<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_status_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('delivery_note_id');
            $table->string('previous_status', 20)->nullable();
            $table->string('current_status', 20);
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamp('changed_at')->useCurrent();

            $table->index(['delivery_note_id'], 'delivery_status_histories_note_index');
            $table->index(['company_id', 'delivery_note_id'], 'delivery_status_histories_company_note_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_status_histories');
    }
};
