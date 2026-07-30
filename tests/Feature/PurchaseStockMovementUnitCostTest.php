<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\PurchaseItem;
use App\Models\StockMovement;
use App\Services\StockService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class PurchaseStockMovementUnitCostTest extends TestCase
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
            $table->decimal('cost_price', 10, 2)->default(0);
            $table->decimal('current_stock', 15, 2)->default(0);
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('purchase_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('financial_year_id');
            $table->unsignedBigInteger('purchase_invoice_id');
            $table->string('item_type');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('service_id')->nullable();
            $table->decimal('quantity', 15, 2);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('total_price', 15, 2);
            $table->integer('status')->default(1);
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
            $table->unsignedBigInteger('financial_year_id')->nullable();
            $table->date('transaction_date')->nullable();
            $table->unsignedBigInteger('product_id');
            $table->string('type');
            $table->integer('quantity');
            $table->integer('before_stock')->default(0);
            $table->integer('after_stock')->default(0);
            $table->decimal('unit_price', 15, 2)->nullable();
            $table->string('reference_no')->nullable();
            $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        $this->createInventoryValuationsTable();
    }

    public function test_product_purchase_movement_persists_the_exact_purchase_item_unit_price(): void
    {
        $product = $this->product(1, 'Product A');
        $item = $this->purchaseItem(1, 'product', $product->id, null, '12.75');

        $this->postProductPurchaseMovement($product, $item, 4);

        $movement = StockMovement::where('product_id', $product->id)->firstOrFail();
        $this->assertSame('purchase', $movement->type);
        $this->assertSame('12.75', $this->money($movement->unit_price));
        $this->assertSame(4, $movement->quantity);
    }

    public function test_multiple_product_purchase_lines_preserve_their_individual_decimal_costs(): void
    {
        $first = $this->product(1, 'Product A');
        $second = $this->product(1, 'Product B');

        $this->postProductPurchaseMovement($first, $this->purchaseItem(1, 'product', $first->id, null, '10.10'), 2);
        $this->postProductPurchaseMovement($second, $this->purchaseItem(1, 'product', $second->id, null, '99.99'), 3);

        $this->assertSame('10.10', $this->money(StockMovement::where('product_id', $first->id)->value('unit_price')));
        $this->assertSame('99.99', $this->money(StockMovement::where('product_id', $second->id)->value('unit_price')));
    }

    public function test_service_purchase_lines_create_no_stock_movement_and_mixed_purchase_affects_only_products(): void
    {
        $product = $this->product(1, 'Product A');
        $productItem = $this->purchaseItem(1, 'product', $product->id, null, '25.50');
        $serviceItem = $this->purchaseItem(1, 'service', null, 50, '75.25');

        $this->postProductPurchaseMovement($product, $productItem, 2);

        $this->assertSame(1, StockMovement::count());
        $this->assertSame($product->id, StockMovement::firstOrFail()->product_id);
        $this->assertSame(0, StockMovement::where('reference_no', 'SERVICE-' . $serviceItem->id)->count());
    }

    public function test_outer_transaction_rollback_removes_purchase_stock_movement(): void
    {
        try {
            DB::transaction(function (): void {
                $product = $this->product(1, 'Rollback Product');
                $item = $this->purchaseItem(1, 'product', $product->id, null, '14.00');
                $this->postProductPurchaseMovement($product, $item, 2);

                throw new RuntimeException('Rollback test');
            });
        } catch (RuntimeException $exception) {
            $this->assertSame('Rollback test', $exception->getMessage());
        }

        $this->assertSame(0, StockMovement::count());
    }

    public function test_company_scoped_products_create_company_scoped_stock_movements(): void
    {
        $first = $this->product(1, 'Company One Product');
        $second = $this->product(2, 'Company Two Product');

        $this->postProductPurchaseMovement($first, $this->purchaseItem(1, 'product', $first->id, null, '11.00'), 1);
        $this->postProductPurchaseMovement($second, $this->purchaseItem(2, 'product', $second->id, null, '22.00'), 1);

        $this->assertSame(1, StockMovement::where('company_id', 1)->where('product_id', $first->id)->count());
        $this->assertSame(1, StockMovement::where('company_id', 2)->where('product_id', $second->id)->count());
    }

    private function postProductPurchaseMovement(Product $product, PurchaseItem $item, int $quantity): void
    {
        StockService::increase(
            $product,
            $quantity,
            'purchase',
            'PU-' . $item->purchase_invoice_id,
            $item->financial_year_id,
            '2026-01-01',
            $item->unit_price
        );
    }

    private function product(int $companyId, string $name): Product
    {
        return Product::create(['company_id' => $companyId, 'name' => $name, 'cost_price' => '0.00', 'current_stock' => 0, 'status' => 'active']);
    }

    private function purchaseItem(int $companyId, string $type, ?int $productId, ?int $serviceId, string $unitPrice): PurchaseItem
    {
        $purchaseInvoiceId = PurchaseItem::count() + 1;
        DB::table('purchase_invoices')->insert([
            'id' => $purchaseInvoiceId,
            'company_id' => $companyId,
            'financial_year_id' => 1,
            'invoice_no' => 'PU-' . $purchaseInvoiceId,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return PurchaseItem::create([
            'company_id' => $companyId,
            'financial_year_id' => 1,
            'purchase_invoice_id' => $purchaseInvoiceId,
            'item_type' => $type,
            'product_id' => $productId,
            'service_id' => $serviceId,
            'quantity' => '1.00',
            'unit_price' => $unitPrice,
            'total_price' => $unitPrice,
            'status' => 1,
        ]);
    }

    private function money(mixed $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }

    private function createInventoryValuationsTable(): void
    {
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
}
