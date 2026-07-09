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
       Schema::create('stock_transactions', function (Blueprint $table) {
    $table->id();

    $table->unsignedBigInteger('company_id');
    $table->unsignedBigInteger('product_id');

    $table->string('type'); // in / out

    $table->integer('quantity');

    $table->decimal('price', 10, 2)->default(0);

    $table->string('reference')->nullable(); 
    // purchase_invoice / sales_invoice id

    $table->text('note')->nullable();

    $table->timestamps();

    $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
    $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_transactions');
    }
};
