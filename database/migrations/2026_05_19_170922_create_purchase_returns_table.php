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
       Schema::create('purchase_returns', function (Blueprint $table) {

    $table->id();

    $table->unsignedBigInteger('company_id');

    $table->unsignedBigInteger('purchase_invoice_id');

    $table->unsignedBigInteger('supplier_id');

    $table->string('return_no');

    $table->date('return_date');

    $table->decimal('subtotal', 15, 2)->default(0);

    $table->decimal('total_vat', 15, 2)->default(0);

    $table->decimal('grand_total', 15, 2)->default(0);

    $table->text('note')->nullable();

    $table->unsignedBigInteger('created_by');

    $table->boolean('status')->default(1);

    $table->timestamps();

});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_returns');
    }
};
