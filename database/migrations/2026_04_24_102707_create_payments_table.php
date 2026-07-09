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
      Schema::create('payments', function (Blueprint $table) {
    $table->id();

    $table->foreignId('company_id')->constrained()->onDelete('cascade');
    $table->foreignId('plan_id')->constrained()->onDelete('cascade');

    $table->integer('amount');
    $table->string('method'); // bank, esewa, manual
    $table->string('screenshot')->nullable();

    $table->string('status')->default('pending'); // pending, approved, rejected
    $table->text('note')->nullable();

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
