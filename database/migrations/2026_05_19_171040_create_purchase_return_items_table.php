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
        Schema::create('purchase_return_items', function (Blueprint $table) {

    $table->id();

    $table->unsignedBigInteger('company_id');

    $table->unsignedBigInteger('purchase_return_id');

    $table->unsignedBigInteger('product_id');

    $table->decimal('quantity', 15, 2);

    $table->decimal('unit_price', 15, 2);

    $table->decimal('vat_rate', 15, 2)
        ->default(0);

    $table->decimal('vat_amount', 15, 2)
        ->default(0);

    $table->decimal('total_price', 15, 2);

    $table->unsignedBigInteger('created_by');

    $table->timestamps();

});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_return_items');
    }
};
