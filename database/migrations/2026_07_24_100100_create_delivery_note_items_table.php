<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_note_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('delivery_note_id');
            $table->unsignedBigInteger('sales_item_id');
            $table->enum('item_type', ['product', 'service']);
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('service_id')->nullable();
            $table->decimal('invoice_qty', 15, 2)->default(0);
            $table->decimal('delivered_qty', 15, 2)->default(0);
            $table->string('status', 20)->default('active');
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index(['delivery_note_id'], 'delivery_note_items_note_index');
            $table->index(['company_id', 'sales_item_id'], 'delivery_note_items_company_item_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_note_items');
    }
};
