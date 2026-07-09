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
        Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('company_id');
    $table->unsignedBigInteger('category_id')->nullable();
    $table->unsignedBigInteger('unit_id');

    $table->string('name');
    $table->string('barcode')->nullable();

    $table->decimal('cost_price', 10, 2)->default(0);
    $table->decimal('retail_price', 10, 2)->default(0);
    $table->decimal('wholesale_price', 10, 2)->default(0);

    $table->integer('stock_alert')->default(0);

    $table->text('description')->nullable();
    $table->string('image')->nullable();

    $table->boolean('status')->default(1);

    $table->timestamps();

    $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
    $table->foreign('unit_id')->references('id')->on('units')->onDelete('restrict');
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
