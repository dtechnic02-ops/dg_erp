<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_signatures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('delivery_note_id');
            $table->string('customer_name')->nullable();
            $table->string('receiver_name')->nullable();
            $table->string('receiver_mobile', 30)->nullable();
            $table->string('signature_path');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->unique('delivery_note_id', 'delivery_signatures_note_unique');
            $table->index(['company_id', 'delivery_note_id'], 'delivery_signatures_company_note_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_signatures');
    }
};
