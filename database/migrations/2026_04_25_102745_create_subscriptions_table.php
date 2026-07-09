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
      Schema::create('subscriptions', function (Blueprint $table) {
    $table->id();

    $table->foreignId('company_id')->constrained()->onDelete('cascade');
    $table->foreignId('plan_id')->constrained()->onDelete('cascade');

    $table->date('start_date');
    $table->date('expiry_date');

    $table->string('status')->default('active'); // active, expired

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
