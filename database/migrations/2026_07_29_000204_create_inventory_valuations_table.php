<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('inventory_valuations')) {
            if (! Schema::hasIndex('inventory_valuations', 'inv_val_source_lookup_idx')) {
                Schema::table('inventory_valuations', function (Blueprint $table): void {
                    $table->index(
                        ['company_id', 'source_type', 'source_id', 'source_event'],
                        'inv_val_source_lookup_idx'
                    );
                });
            }

            return;
        }

        Schema::create('inventory_valuations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('stock_movement_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('valuation_sequence');
            $table->string('movement_type');
            $table->string('source_module');
            $table->string('source_type');
            $table->unsignedBigInteger('source_id');
            $table->string('source_event');
            $table->decimal('quantity_before', 20, 6);
            $table->decimal('quantity_change', 20, 6);
            $table->decimal('quantity_after', 20, 6);
            $table->decimal('inventory_value_before', 20, 4);
            $table->decimal('inventory_value_change', 20, 4);
            $table->decimal('inventory_value_after', 20, 4);
            $table->decimal('average_cost_before', 20, 8);
            $table->decimal('movement_unit_cost', 20, 8);
            $table->decimal('average_cost_after', 20, 8);
            $table->foreignId('reversal_of_id')->nullable()->constrained('inventory_valuations')->restrictOnDelete();
            $table->timestamp('valued_at');
            $table->timestamps();

            $table->unique(
                'stock_movement_id',
                'inv_val_stock_movement_uq'
            );
            $table->unique(
                ['company_id', 'product_id', 'valuation_sequence'],
                'inv_val_company_product_seq_uq'
            );
            $table->index(
                ['company_id', 'product_id'],
                'inv_val_company_product_idx'
            );
            $table->index(
                ['company_id', 'product_id', 'valued_at'],
                'inv_val_product_valued_at_idx'
            );
            $table->index(
                ['company_id', 'source_type', 'source_id', 'source_event'],
                'inv_val_source_lookup_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_valuations');
    }
};
