<?php

namespace Tests\Feature;

use App\Http\Controllers\Company\SalesController;
use App\Http\Controllers\Company\SalesReturnController;
use App\Models\Company;
use App\Models\CompanyIrdCbmsSetting;
use App\Models\Country;
use App\Models\Service;
use App\Models\SalesInvoice;
use App\Models\SalesItem;
use App\Models\User;
use App\Services\Accounting\Builders\SalesAccountingDataBuilder;
use App\Services\SalesFiscalLineAmountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;
use Tests\TestCase;

class SalesFiscalLineDiscountFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_schema_preserves_future_only_fiscal_discount_evidence(): void
    {
        foreach (['sales_items', 'sales_return_items'] as $table) {
            $this->assertTrue(Schema::hasColumn($table, 'fiscal_discount_amount'));
            $this->assertTrue(Schema::hasColumn($table, 'fiscal_net_base'));
        }
    }

    public function test_fiscal_lines_cover_taxable_mixed_and_zero_vat_classifications_and_ignore_header_forgery(): void
    {
        [$company, $services] = $this->companyAndServices('NP', true, 5);
        $request = $this->salesRequest($services, [
            ['vat_taxable', 3, 19.99, 2.17, 13],
            ['vat_taxable', 2, 50, 4, 13],
            ['vat_exempt', 1, 80, 5, 0],
            ['zero_rated', 1, 30, 3, 0],
            ['export', 2, 40, 7, 0],
        ], 999);

        $amounts = app(SalesController::class)->calculateStoreAmounts($request, $company->id);

        $this->assertSame(59.97, $amounts['lineItems'][0]['fiscal_net_base'] + $amounts['lineItems'][0]['fiscal_discount_amount']);
        $this->assertSame(57.80, $amounts['lineItems'][0]['fiscal_net_base']);
        $this->assertSame(7.51, $amounts['lineItems'][0]['vat_amount']);
        $this->assertSame(65.31, $amounts['lineItems'][0]['total_price']);
        $this->assertSame(21.17, $amounts['discount']);
        $this->assertSame(349.97, $amounts['subtotal']);
        $this->assertSame(19.99, $amounts['totalVat']);
        $this->assertSame(348.79, $amounts['grandTotal']);
        $this->assertSame(0.0, $amounts['lineItems'][2]['vat_amount']);
        $this->assertSame(0.0, $amounts['lineItems'][3]['vat_amount']);
        $this->assertSame(0.0, $amounts['lineItems'][4]['vat_amount']);
    }

    public function test_out_of_scope_line_has_explicit_discount_and_zero_vat(): void
    {
        [$company, $services] = $this->companyAndServices('NP', true, 1);
        $amounts = app(SalesController::class)->calculateStoreAmounts(
            $this->salesRequest($services, [['out_of_scope', 2, 25, 6, 0]], 100),
            $company->id
        );

        $this->assertSame(50.0, $amounts['subtotal']);
        $this->assertSame(6.0, $amounts['discount']);
        $this->assertSame(44.0, $amounts['lineItems'][0]['fiscal_net_base']);
        $this->assertSame(0.0, $amounts['totalVat']);
        $this->assertSame(44.0, $amounts['grandTotal']);
    }

    public function test_invalid_fiscal_line_discounts_are_rejected(): void
    {
        $service = app(SalesFiscalLineAmountService::class);

        foreach ([-0.01, 100.01] as $discount) {
            try {
                $service->calculate(1, 100, $discount, 13);
                $this->fail('Invalid fiscal discount was accepted.');
            } catch (InvalidArgumentException $exception) {
                $this->assertStringContainsString('discount', strtolower($exception->getMessage()));
            }
        }
    }

    public function test_partial_returns_use_quantity_shares_and_final_return_absorbs_residual(): void
    {
        $service = app(SalesFiscalLineAmountService::class);
        $original = ['quantity' => 3, 'discount_amount' => 10, 'net_base' => 89.99, 'vat_amount' => 11.70, 'line_total' => 101.69];
        $prior = ['quantity' => 0, 'discount_amount' => 0, 'net_base' => 0, 'vat_amount' => 0, 'line_total' => 0];

        $first = $service->returnShare($original, $prior, 1);
        $this->assertSame(3.33, $first['discount_amount']);
        $this->assertSame(30.0, $first['net_base']);
        $this->assertSame(3.9, $first['vat_amount']);
        $this->assertSame(33.9, $first['line_total']);

        $prior = ['quantity' => 1, 'discount_amount' => 3.33, 'net_base' => 30, 'vat_amount' => 3.9, 'line_total' => 33.9];
        $second = $service->returnShare($original, $prior, 1);
        $prior = ['quantity' => 2, 'discount_amount' => 6.66, 'net_base' => 60, 'vat_amount' => 7.8, 'line_total' => 67.8];
        $final = $service->returnShare($original, $prior, 1);

        $this->assertSame(3.34, $final['discount_amount']);
        $this->assertSame(29.99, $final['net_base']);
        $this->assertSame(3.9, $final['vat_amount']);
        $this->assertSame(33.89, $final['line_total']);
        $this->assertSame(10.0, round($first['discount_amount'] + $second['discount_amount'] + $final['discount_amount'], 2));
        $this->assertSame(101.69, round($first['line_total'] + $second['line_total'] + $final['line_total'], 2));
    }

    public function test_existing_accounting_builder_consumes_fiscal_net_revenue_without_allocating_header_discount(): void
    {
        [$company, $services] = $this->companyAndServices('NP', true, 1);
        $financialYearId = DB::table('financial_years')->insertGetId([
            'company_id' => $company->id, 'name' => '2026', 'start_date' => '2026-01-01',
            'end_date' => '2026-12-31', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $customerId = DB::table('customers')->insertGetId([
            'company_id' => $company->id, 'name' => 'Fiscal Customer', 'opening_balance' => 0,
            'current_balance' => 0, 'credit_days' => 0, 'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $invoice = SalesInvoice::create([
            'created_by' => 1, 'company_id' => $company->id, 'financial_year_id' => $financialYearId, 'customer_id' => $customerId,
            'invoice_no' => 'SI-FISCAL-DISCOUNT', 'sale_date' => '2026-09-09', 'subtotal' => 100,
            'discount' => 10, 'total_vat' => 11.70, 'grand_total' => 101.70, 'paid_amount' => 0,
            'due_amount' => 101.70, 'payment_status' => 'unpaid', 'status' => 1,
        ]);
        SalesItem::create([
            'created_by' => 1, 'company_id' => $company->id, 'financial_year_id' => $financialYearId,
            'sales_invoice_id' => $invoice->id, 'item_type' => 'service', 'service_id' => $services->first()->id,
            'quantity' => 1, 'unit_price' => 100, 'vat_rate' => 13, 'vat_amount' => 11.70,
            'fiscal_discount_amount' => 10, 'fiscal_net_base' => 90, 'total_price' => 101.70,
        ]);

        $data = app(SalesAccountingDataBuilder::class)->build($invoice);

        $this->assertSame('0.0000', $data['totals']['product_revenue']);
        $this->assertSame('90.0000', $data['totals']['service_revenue']);
        $this->assertSame('90.0000', $data['totals']['revenue_before_tax']);
        $this->assertSame('11.7000', $data['totals']['tax_amount']);
        $this->assertSame('101.7000', $data['totals']['grand_total']);
        $this->assertSame('10.0000', $data['totals']['discount_amount']);

        $invoice->forceFill(['discount' => 9])->save();
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Fiscal sales line discounts do not reconcile');
        app(SalesAccountingDataBuilder::class)->build($invoice->fresh());
    }

    public function test_real_sales_return_flow_persists_partial_shares_and_exact_final_residual(): void
    {
        [$company, $services] = $this->companyAndServices('NP', true, 1);
        DB::table('roles')->updateOrInsert(['id' => 2], ['name' => 'company_admin', 'created_at' => now(), 'updated_at' => now()]);
        $user = User::create([
            'name' => 'Fiscal Return Admin', 'email' => uniqid().'@example.test', 'password' => Hash::make('password'),
            'role_id' => 2, 'company_id' => $company->id, 'account_status' => 'active',
        ]);
        $financialYearId = DB::table('financial_years')->insertGetId([
            'company_id' => $company->id, 'name' => '2026', 'start_date' => '2026-01-01',
            'end_date' => '2026-12-31', 'is_active' => 1, 'created_by' => $user->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $customerId = DB::table('customers')->insertGetId([
            'company_id' => $company->id, 'created_by' => $user->id, 'name' => 'Return Customer',
            'opening_balance' => 0, 'current_balance' => 0, 'credit_days' => 0, 'status' => 'active',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $invoice = SalesInvoice::create([
            'created_by' => $user->id, 'company_id' => $company->id, 'financial_year_id' => $financialYearId,
            'customer_id' => $customerId, 'invoice_no' => 'SI-FISCAL-RETURN', 'sale_date' => '2026-09-01',
            'subtotal' => 99.99, 'discount' => 10, 'total_vat' => 11.70, 'grand_total' => 101.69,
            'paid_amount' => 0, 'due_amount' => 101.69, 'payment_status' => 'unpaid', 'status' => 1,
        ]);
        $item = SalesItem::create([
            'created_by' => $user->id, 'company_id' => $company->id, 'financial_year_id' => $financialYearId,
            'sales_invoice_id' => $invoice->id, 'item_type' => 'service', 'service_id' => $services->first()->id,
            'quantity' => 3, 'returned_qty' => 0, 'unit_price' => 33.33, 'vat_rate' => 13,
            'vat_amount' => 11.70, 'tax_classification' => 'vat_taxable', 'fiscal_discount_amount' => 10,
            'fiscal_net_base' => 89.99, 'total_price' => 101.69,
        ]);
        $this->actingAs($user);

        foreach ([1, 1, 1] as $index => $quantity) {
            app(SalesReturnController::class)->store(new Request([
                'sales_invoice_id' => $invoice->id, 'customer_id' => $customerId,
                'return_date' => '2026-09-0'.($index + 2), 'sales_item_id' => [$item->id],
                'quantity' => [$quantity], 'note' => 'Fiscal partial '.($index + 1),
            ]));
        }

        $rows = DB::table('sales_return_items')->where('sales_item_id', $item->id)->orderBy('id')->get();
        $this->assertCount(3, $rows);
        $this->assertSame([3.33, 3.33, 3.34], $rows->map(fn ($row) => (float) $row->fiscal_discount_amount)->all());
        $this->assertSame([30.0, 30.0, 29.99], $rows->map(fn ($row) => (float) $row->fiscal_net_base)->all());
        $this->assertSame(10.0, round((float) $rows->sum('fiscal_discount_amount'), 2));
        $this->assertSame(89.99, round((float) $rows->sum('fiscal_net_base'), 2));
        $this->assertSame(11.70, round((float) $rows->sum('vat_amount'), 2));
        $this->assertSame(101.69, round((float) $rows->sum('total_price'), 2));
        $this->assertSame(3.0, (float) $item->fresh()->returned_qty);
    }

    public function test_nepal_cbms_off_and_non_nepal_keep_legacy_header_discount(): void
    {
        foreach ([['NP', false], ['AE', true]] as [$iso, $enabled]) {
            [$company, $services] = $this->companyAndServices($iso, $enabled, 1);
            $amounts = app(SalesController::class)->calculateStoreAmounts(
                $this->salesRequest($services, [['vat_taxable', 1, 100, 40, 13]], 5),
                $company->id
            );

            $this->assertSame(5.0, $amounts['discount']);
            $this->assertSame(13.0, $amounts['totalVat']);
            $this->assertSame(108.0, $amounts['grandTotal']);
            $this->assertNull($amounts['lineItems'][0]['fiscal_discount_amount']);
            $this->assertNull($amounts['lineItems'][0]['fiscal_net_base']);
        }
    }

    private function companyAndServices(string $iso, bool $enabled, int $count): array
    {
        $country = Country::firstOrCreate(
            ['iso_code' => $iso],
            ['name' => $iso === 'NP' ? 'Nepal' : 'Country '.$iso, 'is_active' => true]
        );
        $company = Company::create(['company_name' => 'Fiscal Discount '.uniqid(), 'email' => uniqid().'@example.test', 'mobile' => uniqid(), 'status' => 'active']);
        $company->forceFill(['country_id' => $country->id])->save();
        if ($enabled) {
            CompanyIrdCbmsSetting::create(['company_id' => $company->id, 'is_enabled' => true]);
        }

        $services = collect();
        for ($i = 0; $i < $count; $i++) {
            $services->push(Service::create(['company_id' => $company->id, 'name' => 'Service '.$i.' '.uniqid(), 'price' => 1, 'status' => 'active']));
        }

        return [$company->fresh(), $services];
    }

    private function salesRequest($services, array $lines, float $headerDiscount): Request
    {
        return new Request([
            'item_type' => array_fill(0, count($lines), 'service'),
            'product_id' => array_fill(0, count($lines), null),
            'service_id' => $services->pluck('id')->all(),
            'quantity' => array_column($lines, 1),
            'unit_price' => array_column($lines, 2),
            'line_discount_amount' => array_column($lines, 3),
            'vat_rate' => array_column($lines, 4),
            'tax_classification' => array_column($lines, 0),
            'discount_amount' => $headerDiscount,
        ]);
    }
}
