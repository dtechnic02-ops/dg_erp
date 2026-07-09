<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('purchase_items', function (Blueprint $table) {
   
    $table->id();
    $table->unsignedBigInteger('created_by');
     $table->unsignedBigInteger('company_id');
     
    $table->unsignedBigInteger('purchase_invoice_id');
    $table->unsignedBigInteger('product_id');

    $table->integer('quantity');
    $table->decimal('price', 10, 2);
    $table->decimal('total', 12, 2);
    

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
    }
};
