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
        Schema::create('customers', function (Blueprint $table) {
    $table->id();

    $table->unsignedBigInteger('company_id');

    $table->string('name');                 // Customer name
    $table->string('authority_name')->nullable(); // contact person

    $table->string('mobile')->nullable();
    $table->string('telephone')->nullable();
    $table->string('fax_no')->nullable();

    $table->string('email')->nullable();
    $table->string('website')->nullable();

    $table->text('address')->nullable();

    $table->string('tax_no')->nullable();

    $table->decimal('opening_balance', 10, 2)->default(0);

    $table->string('bank_name')->nullable();
    $table->string('bank_account_no')->nullable();

    $table->text('note')->nullable();

    $table->string('image_path')->nullable();

    $table->string('status')->default('active');

    $table->timestamps();

    // 🔥 IMPORTANT
    $table->foreign('company_id')
        ->references('id')
        ->on('companies')
        ->onDelete('cascade');
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
