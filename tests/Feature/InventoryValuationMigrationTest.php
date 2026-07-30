<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InventoryValuationMigrationTest extends TestCase
{
    public function test_inventory_valuation_migration_creates_the_required_table_and_indexes(): void
    {
        foreach (['inventory_valuations', 'stock_movements', 'products', 'companies'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('companies', function (Blueprint $table): void {
            $table->id();
        });
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained();
        });
        Schema::create('stock_movements', function (Blueprint $table): void {
            $table->id();
        });

        $migration = require base_path('database/migrations/2026_07_29_000204_create_inventory_valuations_table.php');
        $migration->up();

        $this->assertTrue(Schema::hasTable('inventory_valuations'));
        foreach (['company_id', 'product_id', 'stock_movement_id', 'valuation_sequence', 'movement_type', 'source_module', 'source_type', 'source_id', 'source_event', 'quantity_before', 'quantity_change', 'quantity_after', 'inventory_value_before', 'inventory_value_change', 'inventory_value_after', 'average_cost_before', 'movement_unit_cost', 'average_cost_after', 'reversal_of_id', 'valued_at'] as $column) {
            $this->assertTrue(Schema::hasColumn('inventory_valuations', $column));
        }

        $migration->down();
        $this->assertFalse(Schema::hasTable('inventory_valuations'));
    }
}
