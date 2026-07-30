<?php

namespace Tests\Feature;

use App\Models\AccountingEntry;
use App\Models\InventoryValuation;
use App\Models\Product;
use App\Models\SalesCostSnapshot;
use App\Models\SalesInvoice;
use App\Models\SalesItem;
use App\Models\StockMovement;
use App\Services\Accounting\Integrations\SalesCogsAccountingIntegrationService;
use App\Services\SalesInventoryCostService;
use App\Services\StockService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class SalesCogsInventoryAccountingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['sales_cost_snapshots', 'inventory_valuations', 'accounting_entry_lines', 'accounting_entries', 'stock_movements', 'sales_items', 'sales_invoices', 'purchase_invoices', 'products', 'chart_accounts'] as $table) {
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
        Schema::create('sales_invoices', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('financial_year_id');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('invoice_no');
            $table->date('sale_date');
            $table->decimal('grand_total', 20, 4)->default(0);
            $table->integer('status')->default(1);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
        Schema::create('sales_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('financial_year_id');
            $table->unsignedBigInteger('sales_invoice_id');
            $table->string('item_type');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('service_id')->nullable();
            $table->decimal('quantity', 20, 6);
            $table->decimal('returned_qty', 20, 6)->default(0);
            $table->decimal('unit_price', 20, 4)->default(0);
            $table->decimal('vat_rate', 20, 4)->default(0);
            $table->decimal('vat_amount', 20, 4)->default(0);
            $table->decimal('total_price', 20, 4)->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
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
        Schema::create('sales_cost_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('sales_invoice_id');
            $table->unsignedBigInteger('sales_item_id')->unique();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('stock_movement_id')->unique();
            $table->unsignedBigInteger('inventory_valuation_id');
            $table->decimal('average_cost_used', 20, 8);
            $table->decimal('movement_unit_cost', 20, 8);
            $table->decimal('movement_value', 20, 4);
            $table->timestamps();
        });
        Schema::create('chart_accounts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('code');
            $table->string('name');
            $table->string('account_class');
            $table->string('normal_balance');
            $table->string('system_code')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('accounting_entries', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('entry_number');
            $table->date('entry_date');
            $table->string('reference_number')->nullable();
            $table->string('source_module');
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('source_event')->nullable();
            $table->string('source_key')->nullable();
            $table->text('description')->nullable();
            $table->string('status');
            $table->unsignedBigInteger('reversal_of_id')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'source_key']);
        });
        Schema::create('accounting_entry_lines', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('accounting_entry_id');
            $table->unsignedBigInteger('chart_account_id');
            $table->unsignedBigInteger('operational_account_id')->nullable();
            $table->unsignedInteger('line_number');
            $table->text('description')->nullable();
            $table->decimal('debit', 20, 4)->default(0);
            $table->decimal('credit', 20, 4)->default(0);
            $table->string('subledger_type')->nullable();
            $table->unsignedBigInteger('subledger_id')->nullable();
            $table->timestamps();
        });
    }

    public function test_opening_stock_sale_creates_immutable_cost_snapshot_and_balanced_cogs_entry(): void
    {
        $this->seedCharts(1);
        $product = $this->product(1, 'Opening product');
        StockService::increase($product, '10.000000', 'opening_stock', 'OPENING', 1, '2026-01-01', '5.00000000');

        $sale = $this->sale(1, 'SA-1');
        $snapshot = $this->sell($sale, $product, '2.000000');
        $this->cogs()->postSaleCogs($sale);

        $this->assertSame('5.00000000', $snapshot->average_cost_used);
        $this->assertSame('10.0000', $snapshot->movement_value);
        $this->assertSame('sale', $snapshot->inventoryValuation->movement_type);
        $this->assertSame('8.000000', $snapshot->inventoryValuation->quantity_after);
        $this->assertSame('40.0000', $snapshot->inventoryValuation->inventory_value_after);
        $this->assertCogsEntry($sale, '10.0000');
    }

    public function test_purchase_weighted_average_is_snapshotted_for_each_product_sale_and_service_sales_do_not_post_cogs(): void
    {
        $this->seedCharts(1);
        $product = $this->product(1, 'Average product');
        StockService::increase($product, '2.000000', 'opening_stock', 'OPENING', 1, '2026-01-01', '10.00000000');
        DB::table('purchase_invoices')->insert(['company_id' => 1, 'financial_year_id' => 1, 'invoice_no' => 'PU-1', 'status' => 1, 'created_at' => now(), 'updated_at' => now()]);
        StockService::increase($product->fresh(), '2.000000', 'purchase', 'PU-1', 1, '2026-01-02', '20.00000000');

        $sale = $this->sale(1, 'SA-2');
        $snapshot = $this->sell($sale, $product, '2.000000');
        $this->serviceItem($sale);
        $secondSnapshot = $this->sell($sale, $product, '1.000000');
        $this->cogs()->postSaleCogs($sale);

        $this->assertSame('15.00000000', $snapshot->average_cost_used);
        $this->assertSame('30.0000', $snapshot->movement_value);
        $this->assertSame('15.0000', $secondSnapshot->movement_value);
        $this->assertCogsEntry($sale, '45.0000');

        $serviceOnly = $this->sale(1, 'SA-SERVICE');
        $this->serviceItem($serviceOnly);
        $this->cogs()->postSaleCogs($serviceOnly);
        $this->assertSame(0, SalesCostSnapshot::where('sales_invoice_id', $serviceOnly->id)->count());
        $this->assertSame(0, AccountingEntry::where('source_key', 'sales-cogs:' . $serviceOnly->id . ':created')->count());
    }

    public function test_duplicate_snapshot_and_duplicate_cogs_posting_are_rejected(): void
    {
        $this->seedCharts(1);
        $product = $this->product(1, 'Duplicate product');
        StockService::increase($product, 3, 'opening_stock', 'OPENING', 1, '2026-01-01', '8.00000000');
        $sale = $this->sale(1, 'SA-3');
        $snapshot = $this->sell($sale, $product, 1);

        try {
            app(SalesInventoryCostService::class)->snapshot($sale, $snapshot->salesItem, $snapshot->stockMovement);
            $this->fail('A stock movement must have only one immutable cost snapshot.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('sales cost snapshot already exists', $exception->getMessage());
        }

        $this->cogs()->postSaleCogs($sale);

        try {
            $this->cogs()->postSaleCogs($sale);
            $this->fail('A sales invoice must have only one COGS entry.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('already been posted for this source key', $exception->getMessage());
        }
    }

    public function test_missing_or_foreign_company_chart_accounts_roll_back_the_outer_sale_transaction(): void
    {
        $product = $this->product(1, 'Rollback product');
        StockService::increase($product, 3, 'opening_stock', 'OPENING', 1, '2026-01-01', '8.00000000');
        $this->seedCharts(2);
        $sale = $this->sale(1, 'SA-4');

        try {
            DB::transaction(function () use ($sale, $product): void {
                $this->sell($sale, $product, 1);
                $this->cogs()->postSaleCogs($sale);
            });
            $this->fail('Foreign-company chart accounts must reject the COGS posting.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('COST_OF_GOODS_SOLD', $exception->getMessage());
        }

        $this->assertSame('3.000000', number_format((float) $product->fresh()->current_stock, 6, '.', ''));
        $this->assertSame(1, StockMovement::where('product_id', $product->id)->count());
        $this->assertSame(1, InventoryValuation::where('product_id', $product->id)->count());
        $this->assertSame(0, SalesCostSnapshot::where('sales_invoice_id', $sale->id)->count());
        $this->assertSame(0, AccountingEntry::where('company_id', 1)->count());
    }

    public function test_negative_stock_is_rejected_without_a_sale_valuation_or_snapshot(): void
    {
        $product = $this->product(1, 'Insufficient product');
        StockService::increase($product, 1, 'opening_stock', 'OPENING', 1, '2026-01-01', '9.00000000');
        $sale = $this->sale(1, 'SA-5');
        $item = $this->productItem($sale, $product, 2);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient stock.');
        StockService::decrease($product->fresh(), $item->quantity, 'sale', $sale->invoice_no, 1, '2026-01-02');
    }

    public function test_product_sale_without_a_cost_snapshot_is_rejected_while_service_only_sale_is_ignored(): void
    {
        $product = $this->product(1, 'Snapshot required');
        $sale = $this->sale(1, 'SA-6');
        $this->productItem($sale, $product, 1);

        try {
            $this->cogs()->postSaleCogs($sale);
            $this->fail('A product sale without its immutable cost snapshot must be rejected.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('requires an inventory valuation cost snapshot', $exception->getMessage());
        }

        $serviceOnly = $this->sale(1, 'SA-7');
        $this->serviceItem($serviceOnly);
        $this->cogs()->postSaleCogs($serviceOnly);
        $this->assertSame(0, AccountingEntry::count());
    }

    private function sell(SalesInvoice $sale, Product $product, string|int $quantity): SalesCostSnapshot
    {
        $item = $this->productItem($sale, $product, $quantity);
        $movement = StockService::decrease($product->fresh(), $quantity, 'sale', $sale->invoice_no, 1, '2026-01-03');

        return app(SalesInventoryCostService::class)->snapshot($sale, $item, $movement);
    }

    private function product(int $companyId, string $name): Product
    {
        return Product::create(['company_id' => $companyId, 'name' => $name, 'cost_price' => '0.00000000', 'current_stock' => '0.000000', 'status' => 'active']);
    }

    private function sale(int $companyId, string $invoiceNo): SalesInvoice
    {
        return SalesInvoice::create(['company_id' => $companyId, 'financial_year_id' => 1, 'invoice_no' => $invoiceNo, 'sale_date' => '2026-01-03', 'grand_total' => '100.0000', 'status' => 1, 'created_by' => 1]);
    }

    private function productItem(SalesInvoice $sale, Product $product, string|int $quantity): SalesItem
    {
        return SalesItem::create(['company_id' => $sale->company_id, 'financial_year_id' => 1, 'sales_invoice_id' => $sale->id, 'item_type' => 'product', 'product_id' => $product->id, 'quantity' => $quantity, 'unit_price' => '50.0000', 'total_price' => '50.0000']);
    }

    private function serviceItem(SalesInvoice $sale): SalesItem
    {
        return SalesItem::create(['company_id' => $sale->company_id, 'financial_year_id' => 1, 'sales_invoice_id' => $sale->id, 'item_type' => 'service', 'service_id' => 1, 'quantity' => 1, 'unit_price' => '50.0000', 'total_price' => '50.0000']);
    }

    private function seedCharts(int $companyId): void
    {
        foreach ([['5110', 'Cost of Goods Sold', 'expense', 'COST_OF_GOODS_SOLD'], ['1140', 'Inventory', 'asset', 'INVENTORY']] as [$code, $name, $class, $systemCode]) {
            DB::table('chart_accounts')->insert(['company_id' => $companyId, 'code' => $code, 'name' => $name, 'account_class' => $class, 'normal_balance' => $class === 'expense' ? 'debit' : 'debit', 'system_code' => $systemCode, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    private function cogs(): SalesCogsAccountingIntegrationService
    {
        return app(SalesCogsAccountingIntegrationService::class);
    }

    private function assertCogsEntry(SalesInvoice $sale, string $amount): void
    {
        $entry = AccountingEntry::where('company_id', $sale->company_id)->where('source_key', 'sales-cogs:' . $sale->id . ':created')->with('lines.chartAccount')->firstOrFail();
        $this->assertSame('sales_cogs', $entry->source_type);
        $this->assertSame('created', $entry->source_event);
        $this->assertSame(2, $entry->lines->count());
        $this->assertSame($amount, $entry->lines->firstWhere('chartAccount.system_code', 'COST_OF_GOODS_SOLD')->debit);
        $this->assertSame($amount, $entry->lines->firstWhere('chartAccount.system_code', 'INVENTORY')->credit);
        $this->assertSame($entry->lines->sum('debit'), $entry->lines->sum('credit'));
    }
}
