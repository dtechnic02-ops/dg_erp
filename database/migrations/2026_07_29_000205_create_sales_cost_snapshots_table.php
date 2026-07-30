<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_cost_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('sales_invoice_id')->constrained()->restrictOnDelete();
            $table->foreignId('sales_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('stock_movement_id')->constrained()->restrictOnDelete();
            $table->foreignId('inventory_valuation_id')->constrained('inventory_valuations')->restrictOnDelete();
            $table->decimal('average_cost_used', 20, 8);
            $table->decimal('movement_unit_cost', 20, 8);
            $table->decimal('movement_value', 20, 4);
            $table->timestamps();

            $table->unique('sales_item_id');
            $table->unique('stock_movement_id');
            $table->index(['company_id', 'sales_invoice_id']);
            $table->index(['company_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_cost_snapshots');
    }
};
