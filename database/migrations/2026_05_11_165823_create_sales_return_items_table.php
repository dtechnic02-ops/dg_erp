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
Schema::create('sales_return_items', function (Blueprint $table) {

    $table->id();

    $table->foreignId('company_id');

    $table->foreignId('sales_return_id');

    $table->foreignId('product_id');

    $table->integer('quantity');

    $table->decimal('unit_price', 12, 2);

    $table->decimal('total_price', 12, 2);

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_return_items');
    }
};
