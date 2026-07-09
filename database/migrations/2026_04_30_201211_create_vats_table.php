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
        Schema::create('vats', function (Blueprint $table) {
    $table->id();

    $table->unsignedBigInteger('company_id'); // 🔥 important

    $table->string('name'); // VAT 13%, GST 5%
    $table->decimal('rate', 5, 2); // 13.00, 5.00

    $table->boolean('is_default')->default(0);

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vats');
    }
};
