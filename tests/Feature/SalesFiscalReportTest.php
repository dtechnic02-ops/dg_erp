<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanyIrdCbmsSetting;
use App\Models\Country;
use App\Models\SalesInvoice;
use App\Models\SalesItem;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Services\SalesFiscalReportService;
use App\Services\Accounting\Builders\SalesAccountingDataBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SalesFiscalReportTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private int $fy;
    private int $customer;

    protected function setUp(): void
    {
        parent::setUp();
        [$this->company, $this->fy, $this->customer] = $this->context('Report A');
    }

    public function test_mixed_fiscal_sales_use_canonical_buckets_discounts_and_frozen_buyer(): void
    {
        $invoice = $this->invoice('SI-MIX', '2026-04-10', [
            ['vat_taxable', 100, 10, 90, 11.70, 101.70],
            ['vat_exempt', 200, 0, 200, 0, 200],
            ['zero_rated', 50, 0, 50, 0, 50],
            ['export', 75, 0, 75, 0, 75],
            ['out_of_scope', 25, 0, 25, 0, 25],
        ]);
        DB::table('customers')->where('id', $this->customer)->update(['name' => 'Changed Buyer', 'tax_no' => '000000000']);
        $beforeEvents = DB::table('fiscal_document_audit_events')->count();

        $report = app(SalesFiscalReportService::class)->report($this->company->id, $this->fy);
        $row = $report['sales']->sole();

        $this->assertTrue($row['is_ready']);
        $this->assertSame('Frozen Buyer', $row['buyer_name']);
        $this->assertSame('123456789', $row['buyer_pan']);
        $this->assertSame('2026', $row['financial_year']);
        $this->assertSame('2026-04-10', $row['transaction_date']);
        $this->assertNotNull($row['fiscal_issue_date']);
        $this->assertSame(450.0, $row['gross_sales']);
        $this->assertSame(10.0, $row['total_discount']);
        $this->assertSame(440.0, $row['net_sales']);
        $this->assertSame(90.0, $row['taxable_sales']);
        $this->assertSame(200.0, $row['exempt_sales']);
        $this->assertSame(50.0, $row['zero_rated_sales']);
        $this->assertSame(75.0, $row['export_sales']);
        $this->assertSame(25.0, $row['out_of_scope_sales']);
        $this->assertSame(11.7, $row['vat_total']);
        $this->assertSame(451.7, $row['grand_total']);
        $this->assertSame((float) $invoice->grand_total, $report['summary']['sales']['grand_total']);
        $accounting = app(SalesAccountingDataBuilder::class)->build($invoice);
        $this->assertSame('440.0000', $accounting['totals']['revenue_before_tax']);
        $this->assertSame('11.7000', $accounting['totals']['tax_amount']);
        $this->assertSame('451.7000', $accounting['totals']['grand_total']);
        $this->assertSame($beforeEvents, DB::table('fiscal_document_audit_events')->count());
    }

    public function test_full_partial_and_multiple_credit_notes_reduce_their_original_buckets(): void
    {
        $invoice = $this->invoice('SI-RET', '2026-05-01', [
            ['vat_taxable', 100, 0, 100, 13, 113],
            ['vat_exempt', 80, 0, 80, 0, 80],
        ]);
        $items = $invoice->items()->orderBy('id')->get();
        $this->creditNote($invoice, 'CN-1', '2026-05-02', [[$items[0], 40, 0, 40, 5.2, 45.2]], 'Partial return');
        $this->creditNote($invoice, 'CN-2', '2026-05-03', [[$items[0], 60, 0, 60, 7.8, 67.8], [$items[1], 80, 0, 80, 0, 80]], 'Final return');

        $report = app(SalesFiscalReportService::class)->report($this->company->id);

        $this->assertCount(2, $report['credit_notes']);
        $this->assertSame(180.0, $report['summary']['returns']['gross_sales']);
        $this->assertSame(100.0, $report['summary']['returns']['taxable_sales']);
        $this->assertSame(80.0, $report['summary']['returns']['exempt_sales']);
        $this->assertSame(13.0, $report['summary']['returns']['vat_total']);
        $this->assertSame(193.0, $report['summary']['returns']['grand_total']);
        $this->assertSame(0.0, $report['summary']['net']['taxable_sales']);
        $this->assertSame(0.0, $report['summary']['net']['exempt_sales']);
        $this->assertSame(0.0, $report['summary']['net']['vat_total']);
        $this->assertSame(0.0, $report['summary']['net']['grand_total']);
        $this->assertSame('SI-RET', $report['credit_notes'][0]['original_invoice_no']);
        $this->assertSame('Frozen Buyer', $report['credit_notes'][0]['buyer_name']);
        $this->assertSame('Partial return', $report['credit_notes'][0]['reason']);
    }

    public function test_durable_predicates_exclude_legacy_documents_and_survive_cbms_being_disabled(): void
    {
        $issued = $this->invoice('SI-ISSUED', '2026-06-01', [['vat_exempt', 100, 0, 100, 0, 100]]);
        $legacy = $this->invoice('SI-LEGACY', '2026-06-02', [['vat_exempt', 50, 0, 50, 0, 50]], false);
        $legacyReturn = $this->creditNote($issued, 'CN-LEGACY', '2026-06-03', [[$issued->items->first(), 10, 0, 10, 0, 10]], null, false);
        CompanyIrdCbmsSetting::where('company_id', $this->company->id)->update(['is_enabled' => false]);

        $report = app(SalesFiscalReportService::class)->report($this->company->id);

        $this->assertSame([$issued->id], $report['sales']->pluck('id')->all());
        $this->assertNotContains($legacy->id, $report['sales']->pluck('id')->all());
        $this->assertNotContains($legacyReturn->id, $report['credit_notes']->pluck('id')->all());
    }

    public function test_incomplete_and_legacy_unclassified_fiscal_documents_are_visible_but_never_totalled(): void
    {
        $invoice = $this->invoice('SI-BAD', '2026-07-01', [['vat_exempt', 100, 0, 100, 0, 100]]);
        $invoice->items()->first()->forceFill(['tax_classification' => 'legacy_unclassified'])->save();

        $report = app(SalesFiscalReportService::class)->report($this->company->id);
        $row = $report['sales']->sole();

        $this->assertFalse($row['is_ready']);
        $this->assertNull($row['grand_total']);
        $this->assertStringContainsString('legacy_unclassified', implode(' ', $row['errors']));
        $this->assertSame(1, $report['summary']['not_ready_count']);
        $this->assertSame(0.0, $report['summary']['sales']['grand_total']);
    }

    public function test_company_financial_year_and_canonical_ad_date_filters_are_isolated(): void
    {
        $inside = $this->invoice('SI-IN', '2026-08-10', [['vat_exempt', 100, 0, 100, 0, 100]]);
        $this->invoice('SI-OUT-DATE', '2026-09-10', [['vat_exempt', 200, 0, 200, 0, 200]]);
        [$other, $otherFy, $otherCustomer] = $this->context('Report B');
        $original = [$this->company, $this->fy, $this->customer];
        [$this->company, $this->fy, $this->customer] = [$other, $otherFy, $otherCustomer];
        $this->invoice('SI-OTHER', '2026-08-10', [['vat_exempt', 999, 0, 999, 0, 999]]);
        [$this->company, $this->fy, $this->customer] = $original;

        $report = app(SalesFiscalReportService::class)->report($this->company->id, $this->fy, '2026-08-01', '2026-08-31');

        $this->assertSame([$inside->id], $report['sales']->pluck('id')->all());
        $this->assertSame(100.0, $report['summary']['sales']['grand_total']);
        $this->assertSame(0, $report['summary']['not_ready_count']);
    }

    private function context(string $name): array
    {
        $country = Country::firstOrCreate(['iso_code' => 'NP'], ['name' => 'Nepal', 'is_active' => true]);
        $company = Company::create(['company_name' => $name, 'mobile' => '98'.random_int(10000000, 99999999), 'email' => strtolower(str_replace(' ', '-', $name)).'@example.test', 'country_id' => $country->id, 'status' => 'active']);
        $fy = DB::table('financial_years')->insertGetId(['company_id' => $company->id, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => 1, 'is_closed' => 0, 'is_locked' => 0, 'created_at' => now(), 'updated_at' => now()]);
        $customer = DB::table('customers')->insertGetId(['company_id' => $company->id, 'name' => 'Original Buyer', 'tax_no' => '111111111', 'opening_balance' => 0, 'current_balance' => 0, 'credit_days' => 0, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        CompanyIrdCbmsSetting::create(['company_id' => $company->id, 'is_enabled' => true]);
        return [$company, $fy, $customer];
    }

    private function invoice(string $number, string $date, array $lines, bool $issued = true): SalesInvoice
    {
        $gross = array_sum(array_column($lines, 1));
        $discount = array_sum(array_column($lines, 2));
        $vat = array_sum(array_column($lines, 4));
        $total = array_sum(array_column($lines, 5));
        $invoice = SalesInvoice::create(['created_by' => 1, 'company_id' => $this->company->id, 'financial_year_id' => $this->fy, 'customer_id' => $this->customer, 'invoice_no' => $number, 'sale_date' => $date, 'subtotal' => $gross, 'discount' => $discount, 'total_vat' => $vat, 'grand_total' => $total, 'paid_amount' => 0, 'due_amount' => $total, 'payment_status' => 'unpaid', 'status' => 1]);
        if ($issued) {
            $invoice->forceFill(['fiscal_snapshot_captured_at' => now(), 'seller_name_snapshot' => $this->company->company_name, 'buyer_name_snapshot' => 'Frozen Buyer', 'buyer_tax_no_snapshot' => '123456789', 'fiscal_payment_mode' => 'credit', 'fiscal_payment_mode_captured_at' => now(), 'fiscal_issued_at' => now()])->save();
        }
        foreach ($lines as $index => [$classification, $lineGross, $lineDiscount, $net, $lineVat, $lineTotal]) {
            SalesItem::create(['created_by' => 1, 'company_id' => $this->company->id, 'financial_year_id' => $this->fy, 'sales_invoice_id' => $invoice->id, 'item_type' => 'service', 'quantity' => 1, 'returned_qty' => 0, 'unit_price' => $lineGross, 'vat_rate' => $classification === 'vat_taxable' ? 13 : 0, 'vat_amount' => $lineVat, 'fiscal_discount_amount' => $lineDiscount, 'fiscal_net_base' => $net, 'tax_classification' => $classification, 'total_price' => $lineTotal])->forceFill(['item_name_snapshot' => 'Service '.($index + 1), 'unit_name_snapshot' => 'Service'])->save();
        }
        return $invoice->fresh('items');
    }

    private function creditNote(SalesInvoice $invoice, string $number, string $date, array $lines, ?string $reason = null, bool $issued = true): SalesReturn
    {
        $gross = array_sum(array_column($lines, 1));
        $vat = array_sum(array_column($lines, 4));
        $total = array_sum(array_column($lines, 5));
        $return = SalesReturn::create(['company_id' => $this->company->id, 'financial_year_id' => $this->fy, 'sales_invoice_id' => $invoice->id, 'customer_id' => $this->customer, 'return_no' => $number, 'return_date' => $date, 'subtotal' => $gross, 'total_vat' => $vat, 'grand_total' => $total, 'refund_amount' => $total, 'adjust_amount' => 0, 'note' => $reason, 'status' => 1]);
        if ($issued) $return->forceFill(['fiscal_issued_at' => now()])->save();
        foreach ($lines as [$salesItem, $lineGross, $lineDiscount, $net, $lineVat, $lineTotal]) {
            SalesReturnItem::create(['company_id' => $this->company->id, 'financial_year_id' => $this->fy, 'sales_return_id' => $return->id, 'sales_item_id' => $salesItem->id, 'quantity' => $lineGross / (float) $salesItem->unit_price, 'unit_price' => $salesItem->unit_price, 'vat_rate' => $salesItem->vat_rate, 'vat_amount' => $lineVat, 'fiscal_discount_amount' => $lineDiscount, 'fiscal_net_base' => $net, 'tax_classification' => $salesItem->tax_classification, 'total_price' => $lineTotal, 'status' => 1]);
        }
        return $return->fresh('items');
    }
}
