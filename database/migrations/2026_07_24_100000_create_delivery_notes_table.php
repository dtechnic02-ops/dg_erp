<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('delivery_no', 50);
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('sales_invoice_id');
            $table->date('delivery_date');
            $table->enum('status', [
                'draft',
                'ready',
                'processing',
                'delivered',
                'partial',
                'rejected',
                'cancelled',
            ])->default('draft');
            $table->text('remarks')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->timestamps();
            $table->timestamp('cancelled_at')->nullable();

            $table->unique(['company_id', 'delivery_no'], 'delivery_notes_company_no_unique');
            $table->index(['company_id', 'delivery_date'], 'delivery_notes_company_date_index');
            $table->index(['company_id', 'status'], 'delivery_notes_company_status_index');
            $table->index(['company_id', 'customer_id'], 'delivery_notes_company_customer_index');
            $table->index(['company_id', 'sales_invoice_id'], 'delivery_notes_company_invoice_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_notes');
    }
};
