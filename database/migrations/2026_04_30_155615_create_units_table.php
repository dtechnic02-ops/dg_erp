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
        Schema::create('units', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('company_id');
    $table->string('name'); // unit name (pcs, kg, etc)
    $table->string('short_name')->nullable(); // optional (pc, kg)
    $table->timestamps();

    // Foreign Key (optional but recommended)
    $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
