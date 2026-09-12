<?php

namespace Tests\Feature;

use App\Models\AccountingEntry;
use App\Models\Company;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchasePayment;
use App\Models\User;
use App\Services\DefaultChartAccountBootstrapService;
use App\Services\InvoiceNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PurchaseLifecycleEndToEndTest extends TestCase
{
    use RefreshDatabase;

    private const DATE = '2026-06-15';

    public function test_complete_purchase_payment_cancellation_and_repurchase_lifecycle(): void
    {
        $this->withoutMiddleware();

        [$company, $user, $financialYearId] = $this->companyUserAndFinancialYear('Primary');
        [$foreignCompany] = $this->companyUserAndFinancialYear('Foreign');
        $this->seedPermissions();
        $this->actingAs($user);

        $bootstrap = app(DefaultChartAccountBootstrapService::class);
        $bootstrap->seedForCompany($company->id);
        $requiredCodes = $bootstrap->requiredSystemCodes();
        $this->assertSame(
            count($requiredCodes),
            DB::table('chart_accounts')->where('company_id', $company->id)
                ->whereIn('system_code', $requiredCodes)->distinct()->count('system_code')
        );

        $unitId = DB::table('units')->insertGetId([
            'company_id' => $company->id, 'name' => 'Pieces', 'short_name' => 'pcs',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $categoryId = DB::table('product_categories')->insertGetId([
            'company_id' => $company->id, 'name' => 'Test Products', 'status' => 'active',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $brandId = DB::table('brands')->insertGetId([
            'company_id' => $company->id, 'name' => 'Test Brand', 'status' => 1,
            'created_by' => $user->id, 'updated_by' => $user->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $supplierId = DB::table('suppliers')->insertGetId([
            'company_id' => $company->id, 'created_by' => $user->id, 'name' => 'Lifecycle Supplier',
            'opening_balance' => 0, 'current_balance' => 0, 'credit_days' => 0, 'status' => 'active',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $accountId = DB::table('accounts')->insertGetId([
            'company_id' => $company->id, 'account_group' => 'cash', 'account_type' => 'Cash',
            'bank_name' => 'Test Cash', 'account_name' => 'Lifecycle Cash', 'currency' => 'AED',
            'opening_balance' => 10000, 'current_balance' => 10000, 'status' => 'active',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('account_transactions')->insert([
            'company_id' => $company->id, 'financial_year_id' => $financialYearId,
            'account_id' => $accountId, 'transaction_date' => '2026-01-01',
            'voucher_no' => 'OPEN-CASH', 'reference_type' => 'opening_balance', 'reference_id' => $accountId,
            'description' => 'Test cash opening balance', 'debit' => 10000, 'credit' => 0,
            'balance' => 10000, 'created_by' => $user->id, 'status' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $productResponse = $this->post(route('company.products.store'), [
            'name' => 'test_product', 'barcode' => 'TEST-PROD-E2E', 'category_id' => $categoryId,
            'unit_id' => $unitId, 'brand_id' => $brandId, 'cost_price' => 125,
            'retail_price' => 175, 'wholesale_price' => 150, 'opening_stock' => 5,
            'stock_alert' => 0, 'status' => 'active',
        ]);
        $productResponse->assertRedirect(route('company.products.index'));
        $productResponse->assertSessionHas('success', 'Product Created Successfully');

        $testProduct = Product::where('company_id', $company->id)->where('name', 'test_product')->firstOrFail();
        $this->assertSame('5.00', $this->money($testProduct->current_stock));
        $openingMovement = DB::table('stock_movements')->where('company_id', $company->id)
            ->where('product_id', $testProduct->id)->where('type', 'opening_stock')->first();
        $this->assertNotNull($openingMovement);
        $this->assertSame('0.00', $this->money($openingMovement->before_stock));
        $this->assertSame('5.00', $this->money($openingMovement->after_stock));
        $openingValuation = DB::table('inventory_valuations')->where('stock_movement_id', $openingMovement->id)->first();
        $this->assertNotNull($openingValuation);
        $this->assertSame('5.000000', number_format((float) $openingValuation->quantity_change, 6, '.', ''));
        $this->assertSame('625.0000', number_format((float) $openingValuation->inventory_value_change, 4, '.', ''));
        $this->assertBalancedEntry('product_opening_stock', $testProduct->id, 'created');

        $products = [$testProduct];
        foreach ([['Product B', 80], ['Product C', 95], ['Product D', 110]] as [$name, $cost]) {
            $products[] = Product::create([
                'company_id' => $company->id, 'category_id' => $categoryId, 'brand_id' => $brandId,
                'unit_id' => $unitId, 'name' => $name, 'cost_price' => $cost,
                'retail_price' => $cost + 40, 'wholesale_price' => $cost + 20,
                'current_stock' => 0, 'stock_alert' => 0, 'status' => 'active',
            ]);
        }
        $serviceCategoryId = DB::table('service_categories')->insertGetId([
            'company_id' => $company->id, 'name' => 'Lifecycle Services', 'slug' => 'lifecycle-services',
            'status' => 'active', 'created_by' => $user->id, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $services = collect([['Freight Service', 70], ['Inspection Service', 55]])->map(function ($row) use ($company, $serviceCategoryId, $user) {
            $id = DB::table('services')->insertGetId([
                'company_id' => $company->id, 'service_category_id' => $serviceCategoryId,
                'name' => $row[0], 'price' => $row[1], 'status' => 'active', 'created_by' => $user->id,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            return (object) ['id' => $id];
        })->all();

        $first = $this->postPurchase($company->id, $financialYearId, $supplierId, $products, $services, [3, 2, 4, 1], [130, 82, 98, 115], [1, 2], [70, 55]);
        $this->assertPurchaseCreatedCorrectly($first, $company->id, $products, $services);
        $this->assertSame('8.00', $this->money($testProduct->fresh()->current_stock));
        $this->assertBalancedEntry('purchase_invoice', $first->id, 'created');
        $this->assertSame($this->money(-1 * (float) $first->grand_total), $this->money(DB::table('suppliers')->where('id', $supplierId)->value('current_balance')));

        $firstPayment = $this->payInFull($first, $accountId);
        $first->refresh();
        $this->assertSame($this->money($first->grand_total), $this->money($firstPayment->amount));
        $this->assertSame('0.00', $this->money($first->due_amount));
        $this->assertSame('paid', $first->payment_status);
        $this->assertSame('0.00', $this->money(DB::table('suppliers')->where('id', $supplierId)->value('current_balance')));
        $this->assertSame($this->money(10000 - (float) $first->grand_total), $this->money(DB::table('accounts')->where('id', $accountId)->value('current_balance')));
        $this->assertDatabaseHas('account_transactions', ['company_id' => $company->id, 'reference_type' => 'purchase_payment', 'reference_id' => $firstPayment->id]);
        $this->assertBalancedEntry('purchase_payment', $firstPayment->id, 'created');

        $movementCountBeforeBlockedCancel = DB::table('stock_movements')->count();
        $valuationCountBeforeBlockedCancel = DB::table('inventory_valuations')->count();
        $this->post(route('company.purchases.cancel', $first->id), [
            'cancel_date' => self::DATE, 'cancel_reason' => 'Must be blocked by active payment',
        ])->assertSessionHas('error', 'Invoice cannot be cancelled because one or more active payments exist.');
        $this->assertSame(1, (int) $first->fresh()->status);
        $this->assertSame($movementCountBeforeBlockedCancel, DB::table('stock_movements')->count());
        $this->assertSame($valuationCountBeforeBlockedCancel, DB::table('inventory_valuations')->count());

        $this->post(route('company.purchase-payments.cancel', $firstPayment->id), [
            'cancel_date' => self::DATE, 'cancel_reason' => 'Automated lifecycle reversal',
        ])->assertSessionHas('success', 'Payment cancelled successfully.');
        $first->refresh();
        $this->assertSame(0, (int) $firstPayment->fresh()->status);
        $this->assertSame('unpaid', $first->payment_status);
        $this->assertSame($this->money($first->grand_total), $this->money($first->due_amount));
        $this->assertSame('10000.00', $this->money(DB::table('accounts')->where('id', $accountId)->value('current_balance')));
        $this->assertDatabaseCountFor('account_transactions', 'purchase_payment_cancel', $firstPayment->id, 1);
        $this->assertDatabaseCountFor('supplier_transactions', 'purchase_payment_cancel', $firstPayment->id, 1);
        $this->assertBalancedEntry('purchase_payment', $firstPayment->id, 'cancelled');

        $this->post(route('company.purchases.cancel', $first->id), [
            'cancel_date' => self::DATE, 'cancel_reason' => 'Automated lifecycle reversal',
        ])->assertSessionHas('success', 'Purchase cancelled successfully.');
        $this->assertSame(0, (int) $first->fresh()->status);
        $this->assertSame('5.00', $this->money($testProduct->fresh()->current_stock));
        $this->assertSame(4, DB::table('stock_movements')->where('company_id', $company->id)->where('type', 'purchase_cancel')->count());
        $this->assertSame(4, DB::table('inventory_valuations')->where('company_id', $company->id)->where('source_module', 'purchase')->where('source_id', $first->id)->where('source_event', 'cancelled')->count());
        $this->assertSame('0.00', $this->money(DB::table('suppliers')->where('id', $supplierId)->value('current_balance')));
        $this->assertBalancedEntry('purchase_invoice', $first->id, 'cancelled');

        $cancelMovementCount = DB::table('stock_movements')->where('company_id', $company->id)->where('type', 'purchase_cancel')->count();
        $cancelValuationCount = DB::table('inventory_valuations')->where('company_id', $company->id)->where('source_module', 'purchase')->where('source_id', $first->id)->where('source_event', 'cancelled')->count();
        $this->post(route('company.purchases.cancel', $first->id), [
            'cancel_date' => self::DATE, 'cancel_reason' => 'Duplicate cancellation attempt',
        ])->assertSessionHas('error', 'Purchase Already Cancelled.');
        $this->assertSame($cancelMovementCount, DB::table('stock_movements')->where('company_id', $company->id)->where('type', 'purchase_cancel')->count());
        $this->assertSame($cancelValuationCount, DB::table('inventory_valuations')->where('company_id', $company->id)->where('source_module', 'purchase')->where('source_id', $first->id)->where('source_event', 'cancelled')->count());

        $second = $this->postPurchase($company->id, $financialYearId, $supplierId, $products, $services, [4, 3, 2, 5], [135, 84, 99, 118], [2, 1], [72, 58]);
        $this->assertPurchaseCreatedCorrectly($second, $company->id, $products, $services);
        $this->assertSame('9.00', $this->money($testProduct->fresh()->current_stock));
        $this->assertBalancedEntry('purchase_invoice', $second->id, 'created');
        $secondPayment = $this->payInFull($second, $accountId);
        $second->refresh();
        $this->assertSame('0.00', $this->money($second->due_amount));
        $this->assertSame('paid', $second->payment_status);
        $this->assertSame(1, (int) $secondPayment->fresh()->status);
        $this->assertBalancedEntry('purchase_payment', $secondPayment->id, 'created');

        $this->assertSame(0, DB::table('stock_movements')->whereNotIn('product_id', collect($products)->pluck('id'))->count());
        $this->assertSame(0, DB::table('inventory_valuations')->where('company_id', $foreignCompany->id)->count());
        $this->assertSame(0, DB::table('purchase_invoices')->where('company_id', $foreignCompany->id)->count());
        $this->assertSame(0, DB::table('products')->where('company_id', $company->id)->where('current_stock', '<', 0)->count());
        $this->assertSame(0, DB::table('inventory_valuations')->where('company_id', $company->id)->where('quantity_after', '<', 0)->count());
        $this->assertSame(0, DB::table('purchase_items')->leftJoin('purchase_invoices', 'purchase_invoices.id', '=', 'purchase_items.purchase_invoice_id')->whereNull('purchase_invoices.id')->count());
        $this->assertSame(0, DB::table('accounting_entries')->select('source_key')->groupBy('source_key')->havingRaw('COUNT(*) > 1')->count());
        $this->assertSame(0, DB::table('inventory_valuations')->where('company_id', $company->id)->whereNotIn('product_id', collect($products)->pluck('id'))->count());

        fwrite(STDOUT, PHP_EOL.'PURCHASE_LIFECYCLE_IDS '.json_encode([
            'company_id' => $company->id, 'product_id' => $testProduct->id,
            'purchase_1_id' => $first->id, 'purchase_1_no' => $first->invoice_no,
            'payment_1_id' => $firstPayment->id, 'payment_1_no' => $firstPayment->payment_no,
            'purchase_2_id' => $second->id, 'purchase_2_no' => $second->invoice_no,
            'payment_2_id' => $secondPayment->id, 'payment_2_no' => $secondPayment->payment_no,
            'purchase_1_total' => $first->grand_total, 'purchase_2_total' => $second->grand_total,
            'purchase_2_paid' => $secondPayment->amount, 'purchase_2_due' => $second->due_amount,
            'supplier_balance' => DB::table('suppliers')->where('id', $supplierId)->value('current_balance'),
            'cash_balance' => DB::table('accounts')->where('id', $accountId)->value('current_balance'),
            'final_stock' => $testProduct->fresh()->current_stock,
        ], JSON_THROW_ON_ERROR).PHP_EOL);
    }

    private function companyUserAndFinancialYear(string $suffix): array
    {
        DB::table('roles')->insertOrIgnore(['id' => 2, 'name' => 'Company Admin', 'created_at' => now(), 'updated_at' => now()]);
        $company = Company::create([
            'company_name' => $suffix.' Lifecycle Company', 'mobile' => '9800000'.random_int(100, 999),
            'email' => strtolower($suffix).uniqid().'@example.test', 'status' => 'active',
        ]);
        $user = User::create([
            'name' => $suffix.' Admin', 'email' => strtolower($suffix).uniqid().'@example.test',
            'password' => 'password', 'role_id' => 2, 'company_id' => $company->id, 'account_status' => 'active',
        ]);
        $fy = DB::table('financial_years')->insertGetId([
            'company_id' => $company->id, 'name' => 'FY 2026', 'start_date' => '2026-01-01',
            'end_date' => '2026-12-31', 'is_active' => 1, 'is_closed' => 0, 'is_locked' => 0,
            'created_by' => $user->id, 'created_at' => now(), 'updated_at' => now(),
        ]);
        return [$company, $user, $fy];
    }

    private function seedPermissions(): void
    {
        foreach (['module_stock', 'view_stock', 'module_purchase', 'view_purchase', 'create_purchase', 'cancel_purchase'] as $name) {
            DB::table('permissions')->insert(['name' => $name, 'scope' => 'company', 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    private function postPurchase(int $companyId, int $financialYearId, int $supplierId, array $products, array $services, array $productQty, array $productRate, array $serviceQty, array $serviceRate): PurchaseInvoice
    {
        $invoiceNo = InvoiceNumberService::generate('PU', $companyId, $financialYearId, PurchaseInvoice::class, 'invoice_no');
        $this->withSession(['pending_purchase_invoice' => [
            'company_id' => $companyId,
            'financial_year_id' => $financialYearId,
            'invoice_no' => $invoiceNo,
        ]]);
        $payload = [
            'supplier_id' => $supplierId, 'purchase_date' => self::DATE,
            'item_type' => [], 'product_id' => [], 'service_id' => [], 'quantity' => [],
            'unit_price' => [], 'vat_rate' => [], 'discount_amount' => 0, 'paid_amount' => 0,
        ];
        foreach ($products as $index => $product) {
            $payload['item_type'][] = 'product'; $payload['product_id'][] = $product->id;
            $payload['service_id'][] = null; $payload['quantity'][] = $productQty[$index];
            $payload['unit_price'][] = $productRate[$index]; $payload['vat_rate'][] = 0;
        }
        foreach ($services as $index => $service) {
            $payload['item_type'][] = 'service'; $payload['product_id'][] = null;
            $payload['service_id'][] = $service->id; $payload['quantity'][] = $serviceQty[$index];
            $payload['unit_price'][] = $serviceRate[$index]; $payload['vat_rate'][] = 0;
        }
        $response = $this->post(route('company.purchases.store'), $payload);
        $invoice = PurchaseInvoice::where('company_id', $companyId)->where('financial_year_id', $financialYearId)->latest('id')->firstOrFail();
        $response->assertRedirect(route('company.purchases.show', $invoice->id));
        return $invoice;
    }

    private function assertPurchaseCreatedCorrectly(PurchaseInvoice $invoice, int $companyId, array $products, array $services): void
    {
        $this->assertSame(4, $invoice->items()->where('item_type', 'product')->count());
        $this->assertSame(2, $invoice->items()->where('item_type', 'service')->count());
        $this->assertSame(4, DB::table('stock_movements')->where('company_id', $companyId)->where('type', 'purchase')->where('reference_no', $invoice->invoice_no)->count());
        $this->assertSame(4, DB::table('inventory_valuations')->where('company_id', $companyId)->where('source_module', 'purchase')->where('source_id', $invoice->id)->where('source_event', 'created')->count());
        $this->assertSame(0, $invoice->items()->where('item_type', 'service')->whereNotNull('product_id')->count());
        foreach ($services as $service) {
            $this->assertDatabaseHas('purchase_items', ['purchase_invoice_id' => $invoice->id, 'item_type' => 'service', 'service_id' => $service->id, 'product_id' => null]);
        }
    }

    private function payInFull(PurchaseInvoice $invoice, int $accountId): PurchasePayment
    {
        $response = $this->post(route('company.purchase-payments.store'), [
            'purchase_invoice_id' => $invoice->id, 'account_id' => $accountId,
            'amount' => $invoice->due_amount, 'payment_date' => self::DATE,
            'payment_method' => 'cash', 'reference_no' => 'E2E-'.$invoice->id,
        ]);
        $response->assertRedirect(route('company.purchase-payments.index'));
        $response->assertSessionHas('success', 'Purchase payment recorded successfully.');
        return PurchasePayment::where('purchase_invoice_id', $invoice->id)->latest('id')->firstOrFail();
    }

    private function assertBalancedEntry(string $sourceType, int $sourceId, string $event): void
    {
        $persistedType = match ([$sourceType, $event]) {
            ['purchase_invoice', 'created'] => PurchaseInvoice::class,
            ['purchase_invoice', 'cancelled'] => 'purchase',
            default => $sourceType,
        };
        $entry = AccountingEntry::where('source_type', $persistedType)->where('source_id', $sourceId)->where('source_event', $event)->firstOrFail();
        $this->assertSame(
            number_format((float) $entry->lines()->sum('debit'), 4, '.', ''),
            number_format((float) $entry->lines()->sum('credit'), 4, '.', '')
        );
    }

    private function assertDatabaseCountFor(string $table, string $referenceType, int $referenceId, int $expected): void
    {
        $this->assertSame($expected, DB::table($table)->where('reference_type', $referenceType)->where('reference_id', $referenceId)->count());
    }

    private function money(mixed $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }
}
