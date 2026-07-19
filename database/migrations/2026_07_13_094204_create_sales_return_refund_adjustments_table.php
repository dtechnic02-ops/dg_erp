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
        Schema::create('sales_return_refund_adjustments', function (Blueprint $table) {

            $table->id();

            // Company
            $table->unsignedBigInteger('company_id');

            // Parent Refund
            $table->unsignedBigInteger('sales_return_refund_id');

            // Adjusted Sales Invoice
            $table->unsignedBigInteger('sales_invoice_id');

            // Adjusted Amount
            $table->decimal('adjust_amount', 18, 2);

            // Status
            // 0 = Cancelled
            // 1 = Active
            $table->tinyInteger('status')->default(1);

            // Audit
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Performance Indexes
            $table->index('company_id');
            $table->index('sales_return_refund_id');
            $table->index('sales_invoice_id');

            // Foreign Keys
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreign('sales_return_refund_id')
                ->references('id')
                ->on('sales_return_refunds')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreign('sales_invoice_id')
                ->references('id')
                ->on('sales_invoices')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_return_refund_adjustments');
    }
};