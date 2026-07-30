<?php

namespace Tests\Feature;

use App\Models\AccountingEntry;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\Accounting\Integrations\ProductOpeningStockAccountingIntegrationService;
use App\Services\StockService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class ProductOpeningStockAccountingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['inventory_valuations', 'accounting_entry_lines', 'accounting_entries', 'stock_movements', 'products', 'chart_accounts', 'financial_years'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('financial_years', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(true);
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

        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->decimal('cost_price', 10, 2)->default(0);
            $table->decimal('current_stock', 15, 2)->default(0);
            $table->string('status')->default('active');
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

        foreach ([1, 2] as $companyId) {
            DB::table('financial_years')->insert([
                'company_id' => $companyId,
                'name' => '2026',
                'start_date' => '2026-01-01',
                'end_date' => '2026-12-31',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function test_positive_opening_stock_persists_cost_and_posts_a_balanced_inventory_entry(): void
    {
        $this->seedCharts(1);
        $product = $this->product('12.50');

        StockService::increase(
            $product,
            4,
            'opening_stock',
            'OPENING',
            1,
            '2026-01-01',
            $product->cost_price
        );

        $this->integration()->postOpeningStock($product);

        $movement = StockMovement::where('product_id', $product->id)->firstOrFail();
        $entry = $this->entry($product);
        $this->assertSame('12.5000', $this->decimal($movement->unit_price));
        $this->assertSame('product', $entry->source_module);
        $this->assertSame('product_opening_stock', $entry->source_type);
        $this->assertSame('created', $entry->source_event);
        $this->assertSame('product-opening-stock:' . $product->id, $entry->source_key);
        $this->assertLine($entry, 'INVENTORY', '50.0000', '0.0000');
        $this->assertLine($entry, 'OPENING_BALANCE_EQUITY', '0.0000', '50.0000');
        $this->assertBalanced($entry);
    }

    public function test_zero_quantity_creates_no_movement_or_accounting_entry(): void
    {
        $product = $this->product('12.50');

        $this->assertSame(0, StockMovement::where('product_id', $product->id)->count());
        $this->assertSame(0, AccountingEntry::count());
    }

    public function test_zero_cost_rejects_accounting_without_creating_an_entry(): void
    {
        $this->seedCharts(1);
        $product = $this->product('0.00');
        $this->openingMovement($product, 4, '0.00');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('zero-value product opening stock movement');
        $this->integration()->postOpeningStock($product);
    }

    public function test_duplicate_and_invalid_company_chart_accounts_are_rejected(): void
    {
        $product = $this->product('10.00');
        $this->openingMovement($product, 2, '10.00');
        $this->seedCharts(2);

        try {
            $this->integration()->postOpeningStock($product);
            $this->fail('Foreign-company charts must not be used.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('INVENTORY', $exception->getMessage());
        }

        $this->seedCharts(1);
        $this->integration()->postOpeningStock($product);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('An accounting entry has already been posted for this source key.');
        $this->integration()->postOpeningStock($product);
    }

    public function test_accounting_failure_rolls_back_product_and_opening_stock_movement_in_the_outer_transaction(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('INVENTORY');

        try {
            DB::transaction(function (): void {
                $product = $this->product('10.00');
                $this->openingMovement($product, 3, '10.00');
                $this->integration()->postOpeningStock($product);
            });
        } finally {
            $this->assertSame(0, Product::count());
            $this->assertSame(0, StockMovement::count());
            $this->assertSame(0, AccountingEntry::count());
        }
    }

    private function seedCharts(int $companyId): void
    {
        foreach ([
            ['code' => '1140', 'name' => 'Inventory', 'class' => 'asset', 'normal' => 'debit', 'system' => 'INVENTORY'],
            ['code' => '3140', 'name' => 'Opening Balance Equity', 'class' => 'equity', 'normal' => 'credit', 'system' => 'OPENING_BALANCE_EQUITY'],
        ] as $chart) {
            DB::table('chart_accounts')->updateOrInsert(
                ['company_id' => $companyId, 'system_code' => $chart['system']],
                [
                    'code' => $chart['code'], 'name' => $chart['name'], 'account_class' => $chart['class'],
                    'normal_balance' => $chart['normal'], 'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
                ]
            );
        }
    }

    private function product(string $costPrice): Product
    {
        return Product::create(['company_id' => 1, 'name' => 'Product', 'cost_price' => $costPrice, 'current_stock' => 0, 'status' => 'active']);
    }

    private function openingMovement(Product $product, int $quantity, string $unitPrice): void
    {
        StockMovement::create([
            'company_id' => 1, 'financial_year_id' => 1, 'transaction_date' => '2026-01-01', 'product_id' => $product->id,
            'type' => 'opening_stock', 'quantity' => $quantity, 'before_stock' => 0, 'after_stock' => $quantity,
            'unit_price' => $unitPrice, 'reference_no' => 'OPENING', 'created_by' => 1,
        ]);
    }

    private function integration(): ProductOpeningStockAccountingIntegrationService
    {
        return app(ProductOpeningStockAccountingIntegrationService::class);
    }

    private function entry(Product $product): AccountingEntry
    {
        return AccountingEntry::where('source_type', 'product_opening_stock')->where('source_id', $product->id)->where('source_event', 'created')->firstOrFail();
    }

    private function assertLine(AccountingEntry $entry, string $systemCode, string $debit, string $credit): void
    {
        $line = $entry->lines()->whereHas('chartAccount', fn ($query) => $query->where('system_code', $systemCode))->firstOrFail();
        $this->assertSame($debit, $line->debit);
        $this->assertSame($credit, $line->credit);
    }

    private function assertBalanced(AccountingEntry $entry): void
    {
        $this->assertSame($this->decimal($entry->lines()->sum('debit')), $this->decimal($entry->lines()->sum('credit')));
    }

    private function decimal(mixed $amount): string
    {
        return number_format((float) $amount, 4, '.', '');
    }
}
