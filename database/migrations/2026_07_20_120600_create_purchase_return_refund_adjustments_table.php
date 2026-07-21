<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
       Schema::create('purchase_return_refund_adjustments', function (Blueprint $table) {
    $table->id();

    $table->unsignedBigInteger('company_id');
    $table->unsignedBigInteger('purchase_return_refund_id');
    $table->unsignedBigInteger('purchase_invoice_id');

    $table->decimal('adjust_amount', 18, 2);
    $table->tinyInteger('status')->default(1);

    $table->unsignedBigInteger('created_by');
    $table->unsignedBigInteger('updated_by')->nullable();
    $table->unsignedBigInteger('deleted_by')->nullable();

    $table->timestamps();
    $table->softDeletes();

    // Short Index Names
    $table->index('company_id', 'prra_cmp_idx');
    $table->index('purchase_return_refund_id', 'prra_refund_idx');
    $table->index('purchase_invoice_id', 'prra_invoice_idx');

    // Short Foreign Key Names
    $table->foreign('company_id', 'prra_cmp_fk')
        ->references('id')
        ->on('companies')
        ->cascadeOnUpdate()
        ->restrictOnDelete();

    $table->foreign('purchase_return_refund_id', 'prra_refund_fk')
        ->references('id')
        ->on('purchase_return_refunds')
        ->cascadeOnUpdate()
        ->restrictOnDelete();

    $table->foreign('purchase_invoice_id', 'prra_invoice_fk')
        ->references('id')
        ->on('purchase_invoices')
        ->cascadeOnUpdate()
        ->restrictOnDelete();
});
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_return_refund_adjustments');
    }
};
