<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('delivery_note_id');
            $table->enum('document_type', ['photo', 'additional_photo', 'attachment', 'pdf'])->default('photo');
            $table->string('file_path');
            $table->string('original_name')->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['delivery_note_id', 'document_type'], 'delivery_attachments_note_type_index');
            $table->index(['company_id', 'delivery_note_id'], 'delivery_attachments_company_note_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_attachments');
    }
};
