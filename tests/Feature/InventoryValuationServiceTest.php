<?php

namespace Tests\Feature;

use App\Models\InventoryValuation;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\SalesInvoice;
use App\Models\SalesItem;
use App\Models\StockMovement;
use App\Services\InventoryValuationService;
use App\Services\PurchaseCancellationInventoryValuationService;
use App\Services\PurchaseReturnInventoryValuationService;
use App\Services\SalesInventoryCostService;
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

        foreach (['sales_cost_snapshots', 'sales_items', 'sales_invoices', 'inventory_valuations', 'stock_movements', 'purchase_return_items', 'purchase_returns', 'purchase_items', 'purchase_invoices', 'products'] as $table) {
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
            $table->decimal('returned_qty', 20, 6)->default(0);
            $table->decimal('unit_price', 20, 8)->default(0);
            $table->integer('status')->default(1);
            $table->timestamps();
        });
        Schema::create('purchase_returns', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('financial_year_id');
            $table->unsignedBigInteger('purchase_invoice_id');
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->string('return_no');
            $table->date('return_date');
            $table->decimal('subtotal', 20, 4)->default(0);
            $table->decimal('total_vat', 20, 4)->default(0);
            $table->decimal('grand_total', 20, 4)->default(0);
            $table->decimal('refund_amount', 20, 4)->default(0);
            $table->decimal('adjust_amount', 20, 4)->default(0);
            $table->integer('status')->default(1);
            $table->timestamps();
        });
        Schema::create('purchase_return_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('financial_year_id');
            $table->unsignedBigInteger('purchase_return_id');
            $table->unsignedBigInteger('purchase_item_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('service_id')->nullable();
            $table->decimal('quantity', 20, 6);
            $table->decimal('unit_price', 20, 8)->default(0);
            $table->decimal('total_price', 20, 4)->default(0);
            $table->decimal('vat_rate', 20, 4)->default(0);
            $table->decimal('vat_amount', 20, 4)->default(0);
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
        Schema::create('sales_invoices', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('financial_year_id');
            $table->string('invoice_no');
            $table->date('sale_date');
            $table->integer('status')->default(1);
            $table->timestamps();
        });
        Schema::create('sales_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('financial_year_id');
            $table->unsignedBigInteger('sales_invoice_id');
            $table->string('item_type');
            $table->unsignedBigInteger('product_id');
            $table->decimal('quantity', 20, 6);
            $table->decimal('unit_price', 20, 8)->default(0);
            $table->timestamps();
        });
        Schema::create('sales_cost_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('sales_invoice_id');
            $table->unsignedBigInteger('sales_item_id')->unique();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('stock_movement_id')->unique();
            $table->unsignedBigInteger('inventory_valuation_id')->unique();
            $table->decimal('average_cost_used', 20, 8);
            $table->decimal('movement_unit_cost', 20, 8);
            $table->decimal('movement_value', 20, 4);
            $table->timestamps();
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

    public function test_old_purchase_with_later_purchase_can_be_cancelled_without_touching_other_company(): void
    {
        $product = $this->product(1, 'Old purchase');
        $old = $this->purchase(1, 'SHARED');
        $this->purchaseLine($old, $product, 2, '5.00000000');

        $later = $this->purchase(1, 'PU-LATER');
        $this->purchaseLine($later, $product->fresh(), 1, '8.00000000');

        $foreignProduct = $this->product(2, 'Foreign');
        $foreign = $this->purchase(2, 'SHARED');
        $this->purchaseLine($foreign, $foreignProduct, 4, '99.00000000');

        $movementCount = StockMovement::count();

        $reversals = app(PurchaseCancellationInventoryValuationService::class)
            ->reversePurchase($old, '2026-01-03', 1, 'mistaken purchase');

        $this->assertCount(1, $reversals);
        $this->assertSame($movementCount + 1, StockMovement::count());
        $this->assertSame('1.000000', number_format((float) $product->fresh()->current_stock, 6, '.', ''));

        $latest = InventoryValuation::query()
            ->where('company_id', 1)
            ->where('product_id', $product->id)
            ->latest('valuation_sequence')
            ->firstOrFail();

        $this->assertSame('purchase_cancel', $latest->movement_type);
        $this->assertSame('1.000000', $latest->quantity_after);
        $this->assertSame('8.0000', $latest->inventory_value_after);
        $this->assertSame('8.00000000', $latest->average_cost_after);

        $this->assertSame(
            1,
            InventoryValuation::where('company_id', 2)
                ->where('source_id', $foreign->id)
                ->where('source_event', 'created')
                ->count()
        );

        $this->assertSame('4.000000', number_format((float) $foreignProduct->fresh()->current_stock, 6, '.', ''));
    }

    public function test_purchase_return_uses_original_purchase_cost_and_keeps_sales_continuity(): void
    {
        $product = $this->product(1, 'Return then sale');
        StockService::increase($product, 5, 'opening_stock', 'OPENING', 1, '2026-01-01', '125.00000000');
        $purchase = $this->purchase(1, 'PU-RETURN');
        $purchaseItem = $this->purchaseLine($purchase, $product->fresh(), 3, '130.00000000');
        $return = $this->purchaseReturn($purchase, 'PR-1');
        $returnItem = $this->purchaseReturnLine($return, $purchaseItem, 1, '999.00000000');

        $valuation = $this->returnService()->recordReturn($return, $returnItem, $purchaseItem, '2026-01-03', 1);
        $movement = $valuation->stockMovement()->firstOrFail();

        $this->assertSame('purchase_return', $movement->type);
        $this->assertSame($movement->id, $valuation->stock_movement_id);
        $this->assertSame(PurchaseReturnItem::class, $valuation->source_type);
        $this->assertSame($returnItem->id, $valuation->source_id);
        $this->assertSame('created', $valuation->source_event);
        $this->assertSame('8.000000', number_format((float) $movement->before_stock, 6, '.', ''));
        $this->assertSame('7.000000', number_format((float) $movement->after_stock, 6, '.', ''));
        $this->assertSame('-1.000000', $valuation->quantity_change);
        $this->assertSame('8.000000', $valuation->quantity_before);
        $this->assertSame('7.000000', $valuation->quantity_after);
        $this->assertSame('-130.0000', $valuation->inventory_value_change);
        $this->assertSame('885.0000', $valuation->inventory_value_after);
        $this->assertSame('130.00000000', $valuation->movement_unit_cost);
        $this->assertSame('126.42857142', $valuation->average_cost_after);
        $this->assertSame('7.000000', number_format((float) $product->fresh()->current_stock, 6, '.', ''));

        $sale = SalesInvoice::create([
            'company_id' => 1, 'financial_year_id' => 1, 'invoice_no' => 'SI-AFTER-RETURN',
            'sale_date' => '2026-01-04', 'status' => 1,
        ]);
        $saleItem = SalesItem::create([
            'company_id' => 1, 'financial_year_id' => 1, 'sales_invoice_id' => $sale->id,
            'item_type' => 'product', 'product_id' => $product->id, 'quantity' => 1, 'unit_price' => 200,
        ]);
        $saleMovement = StockService::decrease($product->fresh(), 1, 'sale', $sale->invoice_no, 1, '2026-01-04');
        $snapshot = app(SalesInventoryCostService::class)->snapshot($sale, $saleItem, $saleMovement);

        $this->assertSame($saleMovement->id, $snapshot->stock_movement_id);
        $this->assertSame('6.000000', InventoryValuation::latest('valuation_sequence')->value('quantity_after'));
    }

    public function test_multi_product_return_creates_one_movement_and_valuation_per_product(): void
    {
        $first = $this->product(1, 'First return product');
        $second = $this->product(1, 'Second return product');
        $purchase = $this->purchase(1, 'PU-MULTI-RETURN');
        $firstPurchaseItem = $this->purchaseLine($purchase, $first, 4, '10.00000000');
        $secondPurchaseItem = $this->purchaseLine($purchase, $second, 5, '20.00000000');
        $return = $this->purchaseReturn($purchase, 'PR-MULTI');

        foreach ([[$firstPurchaseItem, 2], [$secondPurchaseItem, 3]] as [$purchaseItem, $quantity]) {
            $returnItem = $this->purchaseReturnLine($return, $purchaseItem, $quantity);
            $this->returnService()->recordReturn($return, $returnItem, $purchaseItem, '2026-01-03', 1);
        }

        $this->assertSame(2, StockMovement::where('type', 'purchase_return')->count());
        $this->assertSame(2, InventoryValuation::where('movement_type', 'purchase_return')->count());
        $this->assertSame('2.000000', number_format((float) $first->fresh()->current_stock, 6, '.', ''));
        $this->assertSame('2.000000', number_format((float) $second->fresh()->current_stock, 6, '.', ''));
    }

    public function test_service_only_return_does_not_create_inventory_evidence(): void
    {
        $purchase = $this->purchase(1, 'PU-SERVICE-RETURN');
        $purchaseItem = PurchaseItem::create([
            'company_id' => 1, 'financial_year_id' => 1, 'purchase_invoice_id' => $purchase->id,
            'item_type' => 'service', 'service_id' => 44, 'quantity' => 2, 'unit_price' => 50, 'status' => 1,
        ]);
        $return = $this->purchaseReturn($purchase, 'PR-SERVICE');
        PurchaseReturnItem::create([
            'company_id' => 1, 'financial_year_id' => 1, 'purchase_return_id' => $return->id,
            'purchase_item_id' => $purchaseItem->id, 'service_id' => 44, 'quantity' => 1,
            'unit_price' => 50, 'status' => 1,
        ]);

        $this->assertSame(0, StockMovement::count());
        $this->assertSame(0, InventoryValuation::count());
    }

    public function test_valuation_failure_rolls_back_return_item_stock_and_returned_quantity(): void
    {
        $product = $this->product(1, 'Rollback return');
        $purchase = $this->purchase(1, 'PU-RETURN-ROLLBACK');
        $purchaseItem = $this->purchaseLine($purchase, $product, 2, '15.00000000');
        InventoryValuation::query()->delete();

        try {
            DB::transaction(function () use ($purchase, $purchaseItem): void {
                $return = $this->purchaseReturn($purchase, 'PR-ROLLBACK');
                $returnItem = $this->purchaseReturnLine($return, $purchaseItem, 1);
                $purchaseItem->update(['returned_qty' => 1]);
                $this->returnService()->recordReturn($return, $returnItem, $purchaseItem, '2026-01-03', 1);
            });
            $this->fail('Missing original purchase valuation must roll back the complete return transaction.');
        } catch (RuntimeException $exception) {
            $this->assertSame('The original purchase inventory valuation could not be paired safely.', $exception->getMessage());
        }

        $this->assertSame(0, PurchaseReturn::count());
        $this->assertSame(0, PurchaseReturnItem::count());
        $this->assertSame('0.00', $purchaseItem->fresh()->returned_qty);
        $this->assertSame('2.000000', number_format((float) $product->fresh()->current_stock, 6, '.', ''));
        $this->assertSame(1, StockMovement::count());
    }

    public function test_purchase_return_cancel_creates_exact_reversal_after_later_inventory_movement(): void
    {
        $product = $this->product(1, 'Return cancellation');
        $purchase = $this->purchase(1, 'PU-RETURN-CANCEL');
        $purchaseItem = $this->purchaseLine($purchase, $product, 2, '10.00000000');
        $return = $this->purchaseReturn($purchase, 'PR-CANCEL');
        $returnItem = $this->purchaseReturnLine($return, $purchaseItem, 1);
        $original = $this->returnService()->recordReturn($return, $returnItem, $purchaseItem, '2026-01-03', 1);
        $later = $this->purchase(1, 'PU-AFTER-RETURN');
        $this->purchaseLine($later, $product->fresh(), 2, '20.00000000');

        $reversal = $this->returnService()->reverseReturn($return, $returnItem, '2026-01-05', 1, 'test cancel');

        $this->assertSame('purchase_return_cancel', $reversal->movement_type);
        $this->assertSame($original->id, $reversal->reversal_of_id);
        $this->assertSame('1.000000', $reversal->quantity_change);
        $this->assertSame('10.0000', $reversal->inventory_value_change);
        $this->assertSame('4.000000', $reversal->quantity_after);
        $this->assertSame('60.0000', $reversal->inventory_value_after);
        $this->assertSame('15.00000000', $reversal->average_cost_after);
        $this->assertSame('4.000000', number_format((float) $product->fresh()->current_stock, 6, '.', ''));
    }

    public function test_duplicate_purchase_return_and_duplicate_reversal_are_blocked(): void
    {
        $product = $this->product(1, 'Duplicate return');
        $purchase = $this->purchase(1, 'PU-DUP-RETURN');
        $purchaseItem = $this->purchaseLine($purchase, $product, 2, '10.00000000');
        $return = $this->purchaseReturn($purchase, 'PR-DUP');
        $returnItem = $this->purchaseReturnLine($return, $purchaseItem, 1);
        $service = $this->returnService();
        $service->recordReturn($return, $returnItem, $purchaseItem, '2026-01-03', 1);
        $movementCount = StockMovement::count();

        try {
            $service->recordReturn($return, $returnItem, $purchaseItem, '2026-01-03', 1);
            $this->fail('Duplicate purchase return valuation must be blocked.');
        } catch (RuntimeException $exception) {
            $this->assertSame('An inventory valuation already exists for this purchase return item.', $exception->getMessage());
        }
        $this->assertSame($movementCount, StockMovement::count());

        $service->reverseReturn($return, $returnItem, '2026-01-04', 1, 'first');
        $movementCount = StockMovement::count();
        try {
            $service->reverseReturn($return, $returnItem, '2026-01-04', 1, 'second');
            $this->fail('Duplicate purchase return reversal must be blocked.');
        } catch (RuntimeException $exception) {
            $this->assertSame('This purchase return inventory valuation has already been reversed.', $exception->getMessage());
        }
        $this->assertSame($movementCount, StockMovement::count());
    }

    public function test_purchase_return_rejects_cross_company_source_without_inventory_mutation(): void
    {
        $product = $this->product(1, 'Company one');
        $purchase = $this->purchase(1, 'PU-COMPANY-ONE');
        $purchaseItem = $this->purchaseLine($purchase, $product, 2, '10.00000000');
        $foreignProduct = $this->product(2, 'Company two');
        $foreignPurchase = $this->purchase(2, 'PU-COMPANY-TWO');
        $foreignItem = $this->purchaseLine($foreignPurchase, $foreignProduct, 1, '50.00000000');
        $return = $this->purchaseReturn($purchase, 'PR-COMPANY');
        $returnItem = $this->purchaseReturnLine($return, $purchaseItem, 1);
        $movementCount = StockMovement::count();

        try {
            $this->returnService()->recordReturn($return, $returnItem, $foreignItem, '2026-01-03', 1);
            $this->fail('A foreign-company purchase item must be rejected.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            $this->assertTrue(true);
        }

        $this->assertSame($movementCount, StockMovement::count());
        $this->assertSame('2.000000', number_format((float) $product->fresh()->current_stock, 6, '.', ''));
        $this->assertSame('1.000000', number_format((float) $foreignProduct->fresh()->current_stock, 6, '.', ''));
    }

    public function test_final_partial_return_absorbs_exact_original_value_residue(): void
    {
        $product = $this->product(1, 'Residual return');
        $purchase = $this->purchase(1, 'PU-RESIDUAL');
        $purchaseItem = $this->purchaseLine($purchase, $product, 3, '0.33335000');

        foreach ([1, 2, 3] as $number) {
            $return = $this->purchaseReturn($purchase, 'PR-RESIDUAL-'.$number);
            $returnItem = $this->purchaseReturnLine($return, $purchaseItem, 1);
            $this->returnService()->recordReturn($return, $returnItem, $purchaseItem, '2026-01-0'.($number + 2), 1);
        }

        $changes = InventoryValuation::where('movement_type', 'purchase_return')
            ->orderBy('valuation_sequence')
            ->pluck('inventory_value_change')
            ->all();
        $this->assertSame(['-0.3333', '-0.3333', '-0.3334'], $changes);
        $this->assertSame('0.000000', InventoryValuation::latest('valuation_sequence')->value('quantity_after'));
        $this->assertSame('0.0000', InventoryValuation::latest('valuation_sequence')->value('inventory_value_after'));
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

    private function purchaseReturn(PurchaseInvoice $purchase, string $returnNo): PurchaseReturn
    {
        return PurchaseReturn::create([
            'company_id' => $purchase->company_id,
            'financial_year_id' => $purchase->financial_year_id,
            'purchase_invoice_id' => $purchase->id,
            'return_no' => $returnNo,
            'return_date' => '2026-01-03',
            'status' => 1,
        ]);
    }

    private function purchaseReturnLine(
        PurchaseReturn $return,
        PurchaseItem $purchaseItem,
        int|float|string $quantity,
        ?string $unitPrice = null
    ): PurchaseReturnItem {
        return PurchaseReturnItem::create([
            'company_id' => $return->company_id,
            'financial_year_id' => $return->financial_year_id,
            'purchase_return_id' => $return->id,
            'purchase_item_id' => $purchaseItem->id,
            'product_id' => $purchaseItem->product_id,
            'quantity' => $quantity,
            'unit_price' => $unitPrice ?? $purchaseItem->unit_price,
            'status' => 1,
        ]);
    }

    private function returnService(): PurchaseReturnInventoryValuationService
    {
        return app(PurchaseReturnInventoryValuationService::class);
    }
}
