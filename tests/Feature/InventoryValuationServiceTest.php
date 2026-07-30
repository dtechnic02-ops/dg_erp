<?php

namespace Tests\Feature;

use App\Models\InventoryValuation;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\InventoryValuationService;
use App\Services\StockService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class InventoryValuationServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['inventory_valuations', 'stock_movements', 'purchase_invoices', 'products'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->decimal('cost_price', 20, 8)->default(0);
            $table->decimal('current_stock', 20, 6)->default(0);
            $table->string('status')->default('active');
            $table->timestamps();
        });
        Schema::create('purchase_invoices', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('financial_year_id');
            $table->string('invoice_no');
            $table->integer('status')->default(1);
            $table->timestamps();
        });
        Schema::create('stock_movements', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('financial_year_id');
            $table->date('transaction_date');
            $table->unsignedBigInteger('product_id');
            $table->string('type');
            $table->decimal('quantity', 20, 6);
            $table->decimal('before_stock', 20, 6);
            $table->decimal('after_stock', 20, 6);
            $table->decimal('unit_price', 20, 8)->nullable();
            $table->string('reference_no')->nullable();
            $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
        Schema::create('inventory_valuations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('stock_movement_id')->unique();
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
            $table->unsignedBigInteger('reversal_of_id')->nullable();
            $table->timestamp('valued_at');
            $table->timestamps();
            $table->unique(['company_id', 'product_id', 'valuation_sequence']);
        });
    }

    public function test_opening_stock_and_purchase_create_precise_moving_average_snapshots(): void
    {
        $product = $this->product(1, 'A');
        StockService::increase($product, '3.000000', 'opening_stock', 'OPENING', 1, '2026-01-01', '10.25000000');
        $this->purchaseSource(1, 1, 'PU-1');
        StockService::increase($product->fresh(), '2.000000', 'purchase', 'PU-1', 1, '2026-01-02', '20.50000000');

        $valuations = InventoryValuation::where('company_id', 1)->where('product_id', $product->id)->orderBy('valuation_sequence')->get();
        $this->assertCount(2, $valuations);
        $this->assertSame(1, $valuations[0]->valuation_sequence);
        $this->assertSame('30.7500', $valuations[0]->inventory_value_after);
        $this->assertSame('10.25000000', $valuations[0]->average_cost_after);
        $this->assertSame(2, $valuations[1]->valuation_sequence);
        $this->assertSame('71.7500', $valuations[1]->inventory_value_after);
        $this->assertSame('14.35000000', $valuations[1]->average_cost_after);
        $this->assertSame('purchase', $valuations[1]->source_module);
        $this->assertSame(1, $valuations[1]->source_id);
    }

    public function test_each_company_and_product_has_an_independent_immutable_sequence(): void
    {
        $one = $this->product(1, 'One');
        $two = $this->product(2, 'Two');
        StockService::increase($one, 1, 'opening_stock', 'OPENING', 1, '2026-01-01', '5.00000000');
        StockService::increase($two, 1, 'opening_stock', 'OPENING', 1, '2026-01-01', '7.00000000');

        $this->assertSame(1, InventoryValuation::where('company_id', 1)->where('product_id', $one->id)->value('valuation_sequence'));
        $this->assertSame(1, InventoryValuation::where('company_id', 2)->where('product_id', $two->id)->value('valuation_sequence'));
        $this->assertSame(2, InventoryValuation::count());
    }

    public function test_duplicate_valuation_for_a_stock_movement_is_rejected(): void
    {
        $product = $this->product(1, 'Duplicate');
        StockService::increase($product, 1, 'opening_stock', 'OPENING', 1, '2026-01-01', '5.00000000');
        $movement = StockMovement::firstOrFail();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('already exists for this stock movement');
        app(InventoryValuationService::class)->recordIncomingMovement($movement);
    }

    public function test_missing_purchase_source_rolls_back_stock_movement_and_product_quantity_in_outer_transaction(): void
    {
        $product = $this->product(1, 'Rollback');

        try {
            DB::transaction(function () use ($product): void {
                StockService::increase($product, 2, 'purchase', 'MISSING', 1, '2026-01-01', '9.00000000');
            });
            $this->fail('An unresolved Purchase source must reject valuation.');
        } catch (RuntimeException $exception) {
            $this->assertSame('The purchase source could not be resolved for this stock movement.', $exception->getMessage());
        }

        $this->assertSame(0, StockMovement::count());
        $this->assertSame(0, InventoryValuation::count());
        $this->assertSame('0.000000', number_format((float) $product->fresh()->current_stock, 6, '.', ''));
    }

    private function product(int $companyId, string $name): Product
    {
        return Product::create(['company_id' => $companyId, 'name' => $name, 'cost_price' => '0.00000000', 'current_stock' => '0.000000', 'status' => 'active']);
    }

    private function purchaseSource(int $companyId, int $id, string $invoiceNo): void
    {
        DB::table('purchase_invoices')->insert(['id' => $id, 'company_id' => $companyId, 'financial_year_id' => 1, 'invoice_no' => $invoiceNo, 'status' => 1, 'created_at' => now(), 'updated_at' => now()]);
    }
}
