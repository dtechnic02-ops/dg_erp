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
        Schema::create('stock_movements', function (Blueprint $table) {
    $table->id();

    $table->foreignId('company_id');
    $table->foreignId('product_id');

    $table->enum('type', [
        'purchase',
        'sale',
        'adjustment',
        'damage',
        'return',
        'opening_stock'
    ]);

    $table->integer('quantity');

    $table->integer('before_stock')->default(0);
    $table->integer('after_stock')->default(0);

    $table->decimal('unit_price', 15, 2)->nullable();

    $table->string('reference_no')->nullable();

    $table->text('note')->nullable();

    $table->foreignId('created_by')->nullable();

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
