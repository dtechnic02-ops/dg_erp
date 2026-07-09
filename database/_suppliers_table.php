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
   Schema::create('suppliers', function (Blueprint $table) {
    $table->id();

    $table->unsignedBigInteger('company_id'); // 🔥 multi-tenant

    $table->string('name');                   // Supplier name
    $table->string('mobile')->nullable();
    $table->string('email')->nullable();

    $table->text('address')->nullable();

    $table->string('vat_no')->nullable();
    $table->string('tax_no')->nullable();

    $table->decimal('opening_balance', 10, 2)->default(0);
     $table->string('Note')->nullable();
    $table->string('image_path')->nullable(); // logo/photo

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
