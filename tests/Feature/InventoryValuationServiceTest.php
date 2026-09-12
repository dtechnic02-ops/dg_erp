<?php

namespace Tests\Feature;

use App\Models\InventoryValuation;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseItem;
use App\Models\StockMovement;
use App\Services\InventoryValuationService;
use App\Services\PurchaseCancellationInventoryValuationService;
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

        foreach (['inventory_valuations', 'stock_movements', 'purchase_items', 'purchase_invoices', 'products'] as $table) {
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
        Schema::create('purchase_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('financial_year_id');
            $table->unsignedBigInteger('purchase_invoice_id');
            $table->string('item_type')->default('product');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('service_id')->nullable();
            $table->decimal('quantity', 20, 6);
            $table->decimal('unit_price', 20, 8)->default(0);
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

    public function test_purchase_cancellation_reverses_exact_value_and_links_each_duplicate_line_once(): void
    {
        $product = $this->product(1, 'Duplicate lines');
        StockService::increase($product, 5, 'opening_stock', 'OPENING', 1, '2026-01-01', '4.00000000');
        $purchase = $this->purchase(1, 'PU-DUP');
        $this->purchaseLine($purchase, $product, 2, '10.00000000');
        $this->purchaseLine($purchase, $product, 3, '20.00000000');
        $originals = InventoryValuation::where('source_type', PurchaseInvoice::class)
            ->where('source_id', $purchase->id)->orderBy('valuation_sequence')->get();

        $reversals = app(PurchaseCancellationInventoryValuationService::class)
            ->reversePurchase($purchase, '2026-01-03', 1, 'test');

        $this->assertCount(2, $reversals);
        $this->assertEqualsCanonicalizing($originals->pluck('id')->all(), $reversals->pluck('reversal_of_id')->all());
        $this->assertSame(2, $reversals->pluck('stock_movement_id')->unique()->count());
        $this->assertSame(['-60.0000', '-20.0000'], $reversals->pluck('inventory_value_change')->all());
        $this->assertSame('5.000000', number_format((float) $product->fresh()->current_stock, 6, '.', ''));
        $latest = InventoryValuation::where('product_id', $product->id)->latest('valuation_sequence')->firstOrFail();
        $this->assertSame('20.0000', $latest->inventory_value_after);
        $this->assertSame('4.00000000', $latest->average_cost_after);
    }

    public function test_purchase_cancellation_from_zero_stock_finishes_with_zero_average_and_allows_next_purchase(): void
    {
        $product = $this->product(1, 'Zero average');
        $purchase = $this->purchase(1, 'PU-ZERO');
        $this->purchaseLine($purchase, $product, 2, '7.50000000');

        app(PurchaseCancellationInventoryValuationService::class)
            ->reversePurchase($purchase, '2026-01-02', 1, 'test');

        $cancel = InventoryValuation::where('source_id', $purchase->id)->where('source_event', 'cancelled')->firstOrFail();
        $this->assertSame('0.000000', $cancel->quantity_after);
        $this->assertSame('0.0000', $cancel->inventory_value_after);
        $this->assertSame('0.00000000', $cancel->average_cost_after);

        $next = $this->purchase(1, 'PU-NEXT');
        $this->purchaseLine($next, $product->fresh(), 1, '9.00000000');
        $this->assertSame('9.0000', InventoryValuation::latest('valuation_sequence')->value('inventory_value_after'));
    }

    public function test_duplicate_cancellation_is_blocked_without_creating_more_movements(): void
    {
        $product = $this->product(1, 'Duplicate cancellation');
        $purchase = $this->purchase(1, 'PU-ONCE');
        $this->purchaseLine($purchase, $product, 1, '5.00000000');
        $service = app(PurchaseCancellationInventoryValuationService::class);
        $service->reversePurchase($purchase, '2026-01-02', 1, 'first');
        $movementCount = StockMovement::count();

        try {
            $service->reversePurchase($purchase, '2026-01-03', 1, 'second');
            $this->fail('A second cancellation valuation must be rejected.');
        } catch (RuntimeException $exception) {
            $this->assertSame('This purchase inventory valuation has already been reversed.', $exception->getMessage());
        }

        $this->assertSame($movementCount, StockMovement::count());
    }

    public function test_old_purchase_with_later_purchase_is_reversed_at_current_tail_and_is_company_scoped(): void
    {
        $product = $this->product(1, 'Old purchase');
        $old = $this->purchase(1, 'SHARED');
        $this->purchaseLine($old, $product, 2, '5.00000000');
        $later = $this->purchase(1, 'PU-LATER');
        $this->purchaseLine($later, $product->fresh(), 1, '8.00000000');
        $foreignProduct = $this->product(2, 'Foreign');
        $foreign = $this->purchase(2, 'SHARED');
        $this->purchaseLine($foreign, $foreignProduct, 4, '99.00000000');
        app(PurchaseCancellationInventoryValuationService::class)
            ->reversePurchase($old, '2026-01-03', 1, 'safe current-tail reversal');

        $this->assertSame('1.000000', number_format((float) $product->fresh()->current_stock, 6, '.', ''));
        $latest = InventoryValuation::where('company_id', 1)->where('product_id', $product->id)->latest('valuation_sequence')->firstOrFail();
        $this->assertSame('8.0000', $latest->inventory_value_after);
        $this->assertSame('8.00000000', $latest->average_cost_after);
        $this->assertSame(1, InventoryValuation::where('company_id', 1)->where('source_id', $old->id)->where('source_event', 'cancelled')->count());
        $this->assertSame(1, InventoryValuation::where('company_id', 2)->where('source_id', $foreign->id)->where('source_event', 'created')->count());
    }

    public function test_later_purchase_can_still_be_cancelled_normally(): void
    {
        $product = $this->product(1, 'Latest purchase');
        $first = $this->purchase(1, 'PU-FIRST');
        $this->purchaseLine($first, $product, 2, '5.00000000');
        $latestPurchase = $this->purchase(1, 'PU-LATEST');
        $this->purchaseLine($latestPurchase, $product->fresh(), 3, '8.00000000');

        app(PurchaseCancellationInventoryValuationService::class)
            ->reversePurchase($latestPurchase, '2026-01-03', 1, 'latest');

        $latest = InventoryValuation::where('product_id', $product->id)->latest('valuation_sequence')->firstOrFail();
        $this->assertSame('2.000000', $latest->quantity_after);
        $this->assertSame('10.0000', $latest->inventory_value_after);
        $this->assertSame('5.00000000', $latest->average_cost_after);
    }

    public function test_old_purchase_after_later_sale_succeeds_when_current_quantity_and_value_are_sufficient(): void
    {
        $product = $this->product(1, 'Sale-safe');
        $old = $this->purchase(1, 'PU-A');
        $this->purchaseLine($old, $product, 2, '10.00000000');
        $later = $this->purchase(1, 'PU-B');
        $this->purchaseLine($later, $product->fresh(), 10, '20.00000000');
        $sale = $this->sale($product->fresh(), 1, 'SI-1');
        $saleSnapshot = $sale->only(['id', 'quantity_change', 'inventory_value_change', 'movement_unit_cost', 'average_cost_after']);

        app(PurchaseCancellationInventoryValuationService::class)
            ->reversePurchase($old, '2026-01-04', 1, 'safe after sale');

        $this->assertSame($saleSnapshot, $sale->fresh()->only(array_keys($saleSnapshot)));
        $latest = InventoryValuation::where('product_id', $product->id)->latest('valuation_sequence')->firstOrFail();
        $this->assertSame('9.000000', $latest->quantity_after);
        $this->assertSame('181.6667', $latest->inventory_value_after);
        $this->assertSame('20.18518888', $latest->average_cost_after);
    }

    public function test_old_purchase_is_blocked_when_later_sale_leaves_insufficient_stock_without_partial_mutation(): void
    {
        $product = $this->product(1, 'Sale-unsafe');
        $old = $this->purchase(1, 'PU-OLD');
        $this->purchaseLine($old, $product, 5, '10.00000000');
        $this->sale($product->fresh(), 2, 'SI-LATER');
        $stockBefore = $product->fresh()->current_stock;
        $movementCount = StockMovement::count();
        $valuationCount = InventoryValuation::count();

        try {
            app(PurchaseCancellationInventoryValuationService::class)
                ->reversePurchase($old, '2026-01-04', 1, 'insufficient');
            $this->fail('Insufficient current stock must block cancellation.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Insufficient current stock to reverse this purchase.', $exception->getMessage());
        }

        $this->assertSame($stockBefore, $product->fresh()->current_stock);
        $this->assertSame($movementCount, StockMovement::count());
        $this->assertSame($valuationCount, InventoryValuation::count());
    }

    public function test_old_purchase_is_blocked_when_quantity_is_sufficient_but_current_value_cannot_absorb_reversal(): void
    {
        $product = $this->product(1, 'Value-unsafe');
        $old = $this->purchase(1, 'PU-EXPENSIVE');
        $this->purchaseLine($old, $product, 2, '100.00000000');
        $cheap = $this->purchase(1, 'PU-CHEAP');
        $this->purchaseLine($cheap, $product->fresh(), 100, '1.00000000');
        $this->sale($product->fresh(), 100, 'SI-VALUE-DEPLETION');
        $this->assertSame('2.000000', number_format((float) $product->fresh()->current_stock, 6, '.', ''));
        $movementCount = StockMovement::count();
        $valuationCount = InventoryValuation::count();

        try {
            app(PurchaseCancellationInventoryValuationService::class)
                ->reversePurchase($old, '2026-01-04', 1, 'insufficient value');
            $this->fail('Insufficient current inventory value must block cancellation.');
        } catch (RuntimeException $exception) {
            $this->assertSame('The current inventory valuation cannot safely absorb this purchase reversal.', $exception->getMessage());
        }

        $this->assertSame($movementCount, StockMovement::count());
        $this->assertSame($valuationCount, InventoryValuation::count());
        $this->assertSame(0, InventoryValuation::where('source_id', $old->id)->where('source_event', 'cancelled')->count());
    }

    public function test_multi_item_purchase_rolls_back_entirely_when_one_product_is_unsafe_and_services_are_ignored(): void
    {
        $safe = $this->product(1, 'Safe item');
        $unsafe = $this->product(1, 'Unsafe item');
        $purchase = $this->purchase(1, 'PU-MIXED');
        $this->purchaseLine($purchase, $safe, 2, '10.00000000');
        $this->purchaseLine($purchase, $unsafe, 3, '5.00000000');
        PurchaseItem::create(['company_id' => 1, 'financial_year_id' => 1, 'purchase_invoice_id' => $purchase->id, 'item_type' => 'service', 'service_id' => 99, 'quantity' => 1, 'unit_price' => 100, 'status' => 1]);
        $this->sale($unsafe->fresh(), 1, 'SI-MIXED');
        $movementCount = StockMovement::count();
        $valuationCount = InventoryValuation::count();

        try {
            app(PurchaseCancellationInventoryValuationService::class)
                ->reversePurchase($purchase, '2026-01-04', 1, 'mixed');
            $this->fail('One unsafe product must block the whole purchase cancellation.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Insufficient current stock to reverse this purchase.', $exception->getMessage());
        }

        $this->assertSame('2.000000', number_format((float) $safe->fresh()->current_stock, 6, '.', ''));
        $this->assertSame('2.000000', number_format((float) $unsafe->fresh()->current_stock, 6, '.', ''));
        $this->assertSame($movementCount, StockMovement::count());
        $this->assertSame($valuationCount, InventoryValuation::count());
        $this->assertSame(0, InventoryValuation::where('source_id', $purchase->id)->where('source_event', 'cancelled')->count());
    }

    public function test_production_pattern_later_movements_do_not_blanket_block_safe_old_purchase_cancellation(): void
    {
        $soldProduct = $this->product(1, 'Sold later');
        $purchaseOnlyProduct = $this->product(1, 'Purchased later');
        $old = $this->purchase(1, 'PU-A');
        $this->purchaseLine($old, $soldProduct, 2, '10.00000000');
        $this->purchaseLine($old, $purchaseOnlyProduct, 3, '5.00000000');

        $purchaseB = $this->purchase(1, 'PU-B');
        $this->purchaseLine($purchaseB, $soldProduct->fresh(), 4, '20.00000000');
        app(PurchaseCancellationInventoryValuationService::class)->reversePurchase($purchaseB, '2026-01-03', 1, 'cancel B');

        $purchaseC = $this->purchase(1, 'PU-C');
        $this->purchaseLine($purchaseC, $soldProduct->fresh(), 5, '30.00000000');
        $this->purchaseLine($purchaseC, $purchaseOnlyProduct->fresh(), 4, '8.00000000');
        $sale = $this->sale($soldProduct->fresh(), 1, 'SI-LATER');
        $saleValue = $sale->inventory_value_change;

        app(PurchaseCancellationInventoryValuationService::class)
            ->reversePurchase($old, '2026-01-06', 1, 'historical cancellation');

        $this->assertSame($saleValue, $sale->fresh()->inventory_value_change);
        $this->assertSame('4.000000', number_format((float) $soldProduct->fresh()->current_stock, 6, '.', ''));
        $this->assertSame('4.000000', number_format((float) $purchaseOnlyProduct->fresh()->current_stock, 6, '.', ''));
        $this->assertSame(2, InventoryValuation::where('source_id', $old->id)->where('source_event', 'cancelled')->count());
        $this->assertSame(2, StockMovement::where('type', 'purchase_cancel')->where('reference_no', $old->invoice_no)->count());
    }

    private function product(int $companyId, string $name): Product
    {
        return Product::create(['company_id' => $companyId, 'name' => $name, 'cost_price' => '0.00000000', 'current_stock' => '0.000000', 'status' => 'active']);
    }

    private function purchaseSource(int $companyId, int $id, string $invoiceNo): void
    {
        DB::table('purchase_invoices')->insert(['id' => $id, 'company_id' => $companyId, 'financial_year_id' => 1, 'invoice_no' => $invoiceNo, 'status' => 1, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function purchase(int $companyId, string $invoiceNo): PurchaseInvoice
    {
        return PurchaseInvoice::create(['company_id' => $companyId, 'financial_year_id' => 1, 'invoice_no' => $invoiceNo, 'status' => 1]);
    }

    private function purchaseLine(PurchaseInvoice $purchase, Product $product, int|float|string $quantity, string $cost): PurchaseItem
    {
        $item = PurchaseItem::create([
            'company_id' => $purchase->company_id,
            'financial_year_id' => $purchase->financial_year_id,
            'purchase_invoice_id' => $purchase->id,
            'item_type' => 'product',
            'product_id' => $product->id,
            'quantity' => $quantity,
            'unit_price' => $cost,
            'status' => 1,
        ]);
        StockService::increase($product->fresh(), $quantity, 'purchase', $purchase->invoice_no, 1, '2026-01-02', $cost);

        return $item;
    }

    private function sale(Product $product, int|float|string $quantity, string $reference): InventoryValuation
    {
        $latest = InventoryValuation::where('company_id', $product->company_id)
            ->where('product_id', $product->id)->latest('valuation_sequence')->firstOrFail();
        $movement = StockService::decrease($product, $quantity, 'sale', $reference, 1, '2026-01-03', $latest->average_cost_after);
        $quantityChange = bcsub('0', (string) $quantity, 6);
        $valueChange = bcsub('0', bcmul((string) $quantity, (string) $latest->average_cost_after, 4), 4);
        $quantityAfter = bcadd((string) $latest->quantity_after, $quantityChange, 6);
        $valueAfter = bcadd((string) $latest->inventory_value_after, $valueChange, 4);

        return InventoryValuation::create([
            'company_id' => $product->company_id,
            'product_id' => $product->id,
            'stock_movement_id' => $movement->id,
            'valuation_sequence' => $latest->valuation_sequence + 1,
            'movement_type' => 'sale',
            'source_module' => 'sales',
            'source_type' => 'test_sale',
            'source_id' => $movement->id,
            'source_event' => 'created',
            'quantity_before' => $latest->quantity_after,
            'quantity_change' => $quantityChange,
            'quantity_after' => $quantityAfter,
            'inventory_value_before' => $latest->inventory_value_after,
            'inventory_value_change' => $valueChange,
            'inventory_value_after' => $valueAfter,
            'average_cost_before' => $latest->average_cost_after,
            'movement_unit_cost' => $latest->average_cost_after,
            'average_cost_after' => bccomp($quantityAfter, '0', 6) === 0 ? '0.00000000' : $latest->average_cost_after,
            'valued_at' => now(),
        ]);
    }
}
