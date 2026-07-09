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
       Schema::create('sales_returns', function (Blueprint $table) {

    $table->id();

    $table->foreignId('company_id');

    $table->foreignId('sales_invoice_id');

    $table->string('return_no');

    $table->date('return_date');

    $table->decimal('total_amount', 12, 2)
          ->default(0);

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
        Schema::dropIfExists('sales_returns');
    }
};
