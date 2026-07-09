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
       Schema::create('purchase_invoices', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('created_by');
    $table->unsignedBigInteger('company_id');
    $table->unsignedBigInteger('supplier_id');

    $table->string('invoice_no')->nullable();
    $table->date('date');

    $table->decimal('total_amount', 12, 2)->default(0);
    $table->text('note')->nullable();
    $table->unsignedBigInteger('vat_id');
    $table->string('status')->default('completed');

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_invoices');
    }
};
