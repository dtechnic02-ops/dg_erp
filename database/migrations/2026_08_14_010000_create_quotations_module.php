<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('financial_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->string('quotation_no', 50);
            $table->date('quotation_date');
            $table->date('valid_until')->nullable();
            $table->string('reference_no')->nullable();
            $table->text('note')->nullable();
            $table->decimal('subtotal', 20, 4);
            $table->decimal('discount', 20, 4)->default(0);
            $table->decimal('total_vat', 20, 4)->default(0);
            $table->decimal('grand_total', 20, 4);
            $table->string('status', 20)->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('converted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('converted_at')->nullable();
            $table->foreignId('sales_invoice_id')->nullable()->constrained('sales_invoices')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'financial_year_id', 'quotation_no']);
            $table->unique('sales_invoice_id');
            $table->index(['company_id', 'status', 'quotation_date']);
        });

        Schema::create('quotation_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('quotation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->string('item_type', 20);
            $table->foreignId('product_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('description')->nullable();
            $table->decimal('quantity', 20, 4);
            $table->decimal('unit_price', 20, 4);
            $table->decimal('vat_rate', 10, 4)->default(0);
            $table->decimal('vat_amount', 20, 4)->default(0);
            $table->decimal('total_price', 20, 4);
            $table->timestamps();

            $table->index(['quotation_id', 'item_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_items');
        Schema::dropIfExists('quotations');
    }
};
