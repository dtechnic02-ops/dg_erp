<?php

namespace Tests\Unit;

use App\Models\SalesInvoice;
use App\Models\SalesItem;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Services\SalesFiscalLineAmountService;
use App\Services\SalesFiscalReconciliationService;
use App\Services\SalesTaxClassificationService;
use Tests\TestCase;

class SalesFiscalReconciliationServiceTest extends TestCase
{
    private SalesFiscalReconciliationService $service;
    private SalesFiscalLineAmountService $lineAmounts;

    protected function setUp(): void
    {
        parent::setUp();
        $this->lineAmounts = new SalesFiscalLineAmountService();
        $this->service = new SalesFiscalReconciliationService($this->lineAmounts, new SalesTaxClassificationService());
    }

    public function test_single_and_multiple_taxable_lines_reconcile_from_persisted_evidence(): void
    {
        $single = $this->invoice([$this->line('vat_taxable', 2, 50, 10, 13)]);
        $result = $this->service->reconcile($single);
        $this->assertTrue($result['is_reconciled']);
        $this->assertSame(100.0, $result['gross_sales']);
        $this->assertSame(10.0, $result['total_discount']);
        $this->assertSame(90.0, $result['net_sales']);
        $this->assertSame(90.0, $result['taxable_sales']);
        $this->assertSame(11.7, $result['vat_total']);
        $this->assertSame(101.7, $result['grand_total']);

        $multiple = $this->invoice([
            $this->line('vat_taxable', 2, 50, 10, 13),
            $this->line('vat_taxable', 3, 20, 5, 13),
        ]);
        $result = $this->service->reconcile($multiple);
        $this->assertTrue($result['is_reconciled']);
        $this->assertSame(160.0, $result['gross_sales']);
        $this->assertSame(15.0, $result['total_discount']);
        $this->assertSame(145.0, $result['taxable_sales']);
        $this->assertSame(18.85, $result['vat_total']);
        $this->assertSame(163.85, $result['grand_total']);
    }

    public function test_every_legal_classification_has_an_independent_discounted_bucket(): void
    {
        $invoice = $this->invoice([
            $this->line('vat_taxable', 2, 50, 10, 13),
            $this->line('vat_exempt', 1, 80, 5, 0),
            $this->line('zero_rated', 1, 30, 3, 0),
            $this->line('export', 2, 40, 7, 0),
            $this->line('out_of_scope', 2, 25, 6, 0),
        ]);

        $result = $this->service->reconcile($invoice);

        $this->assertTrue($result['is_reconciled']);
        $this->assertSame(340.0, $result['gross_sales']);
        $this->assertSame(31.0, $result['total_discount']);
        $this->assertSame(309.0, $result['net_sales']);
        $this->assertSame(90.0, $result['taxable_sales']);
        $this->assertSame(75.0, $result['exempt_sales']);
        $this->assertSame(27.0, $result['zero_rated_sales']);
        $this->assertSame(73.0, $result['export_sales']);
        $this->assertSame(44.0, $result['out_of_scope_sales']);
        $this->assertSame(309.0, round($result['taxable_sales'] + $result['exempt_sales']
            + $result['zero_rated_sales'] + $result['export_sales'] + $result['out_of_scope_sales'], 2));
        $this->assertSame(11.7, $result['vat_total']);
        $this->assertSame(320.7, $result['grand_total']);
    }

    public function test_no_discount_and_decimal_rounding_use_persisted_rounded_line_values(): void
    {
        $noDiscount = $this->service->reconcile($this->invoice([$this->line('vat_taxable', 1, 100, 0, 13)]));
        $this->assertTrue($noDiscount['is_reconciled']);
        $this->assertSame(0.0, $noDiscount['total_discount']);

        $rounded = $this->service->reconcile($this->invoice([$this->line('vat_taxable', 1.37, 19.99, 2.17, 13)]));
        $this->assertTrue($rounded['is_reconciled']);
        $this->assertSame(27.39, $rounded['gross_sales']);
        $this->assertSame(25.22, $rounded['net_sales']);
        $this->assertSame(3.28, $rounded['vat_total']);
        $this->assertSame(28.5, $rounded['grand_total']);
    }

    public function test_each_header_mismatch_is_reported_and_not_reconciled(): void
    {
        $messages = [
            'subtotal' => 'subtotal', 'discount' => 'discount',
            'total_vat' => 'VAT', 'grand_total' => 'grand total',
        ];
        foreach ($messages as $field => $fragment) {
            $invoice = $this->invoice([$this->line('vat_taxable', 1, 100, 10, 13)]);
            $invoice->setAttribute($field, (float) $invoice->getAttribute($field) + 1);
            $result = $this->service->reconcile($invoice);
            $this->assertFalse($result['is_reconciled']);
            $this->assertStringContainsString($fragment, implode(' ', $result['errors']));
        }
    }

    public function test_missing_evidence_legacy_classification_and_historical_discount_are_never_inferred(): void
    {
        $missingDiscount = $this->line('vat_taxable', 1, 100, 0, 13);
        $missingDiscount->setAttribute('fiscal_discount_amount', null);
        $this->assertStringContainsString('discount evidence', implode(' ', $this->service->reconcile($this->invoice([$missingDiscount]))['errors']));

        $missingNet = $this->line('vat_taxable', 1, 100, 0, 13);
        $missingNet->setAttribute('fiscal_net_base', null);
        $this->assertStringContainsString('net-base evidence', implode(' ', $this->service->reconcile($this->invoice([$missingNet]))['errors']));

        $legacy = $this->line('vat_taxable', 1, 100, 0, 13);
        $legacy->tax_classification = 'legacy_unclassified';
        $this->assertStringContainsString('legacy_unclassified', implode(' ', $this->service->reconcile($this->invoice([$legacy]))['errors']));

        $historical = new SalesItem([
            'quantity' => 1, 'unit_price' => 100, 'vat_rate' => 13, 'vat_amount' => 13,
            'total_price' => 113, 'tax_classification' => 'vat_taxable',
        ]);
        $invoice = new SalesInvoice(['subtotal' => 100, 'discount' => 5, 'total_vat' => 13, 'grand_total' => 108]);
        $invoice->setRelation('items', collect([$historical]));
        $result = $this->service->reconcile($invoice);
        $this->assertFalse($result['is_reconciled']);
        $this->assertStringContainsString('discount evidence', implode(' ', $result['errors']));
        $this->assertStringContainsString('net-base evidence', implode(' ', $result['errors']));
    }

    public function test_full_and_partial_returns_reconcile_from_frozen_return_evidence(): void
    {
        $full = $this->returnDocument([$this->returnLine('vat_taxable', 3, 33.33, 10, 89.99, 11.70, 101.69)]);
        $result = $this->service->reconcileReturn($full);
        $this->assertTrue($result['is_reconciled']);
        $this->assertSame(99.99, $result['gross_sales']);
        $this->assertSame(10.0, $result['total_discount']);
        $this->assertSame(89.99, $result['taxable_sales']);
        $this->assertSame(11.7, $result['vat_total']);
        $this->assertSame(101.69, $result['grand_total']);

        $partials = [
            $this->returnDocument([$this->returnLine('vat_taxable', 1, 33.33, 3.33, 30, 3.90, 33.90)]),
            $this->returnDocument([$this->returnLine('vat_taxable', 1, 33.33, 3.33, 30, 3.90, 33.90)]),
            $this->returnDocument([$this->returnLine('vat_taxable', 1, 33.33, 3.34, 29.99, 3.90, 33.89)]),
        ];
        $results = collect($partials)->map(fn ($return) => $this->service->reconcileReturn($return));
        $this->assertTrue($results->every(fn ($partial) => $partial['is_reconciled']));
        $this->assertSame(10.0, round($results->sum('total_discount'), 2));
        $this->assertSame(89.99, round($results->sum('net_sales'), 2));
        $this->assertSame(11.7, round($results->sum('vat_total'), 2));
        $this->assertSame(101.69, round($results->sum('grand_total'), 2));
    }

    private function line(string $classification, float $quantity, float $unitPrice, float $discount, float $vatRate): SalesItem
    {
        $amounts = $this->lineAmounts->calculate($quantity, $unitPrice, $discount, $vatRate);
        return new SalesItem([
            'quantity' => $quantity, 'unit_price' => $unitPrice, 'vat_rate' => $vatRate,
            'vat_amount' => $amounts['vat_amount'], 'total_price' => $amounts['line_total'],
            'fiscal_discount_amount' => $amounts['discount_amount'], 'fiscal_net_base' => $amounts['net_base'],
            'tax_classification' => $classification,
        ]);
    }

    private function invoice(array $items): SalesInvoice
    {
        $gross = $discount = $vat = $grand = 0.0;
        foreach ($items as $item) {
            $gross = round($gross + round((float) $item->quantity * (float) $item->unit_price, 2), 2);
            $discount = round($discount + (float) $item->fiscal_discount_amount, 2);
            $vat = round($vat + (float) $item->vat_amount, 2);
            $grand = round($grand + (float) $item->total_price, 2);
        }
        $invoice = new SalesInvoice(['subtotal' => $gross, 'discount' => $discount, 'total_vat' => $vat, 'grand_total' => $grand]);
        $invoice->setRelation('items', collect($items));
        return $invoice;
    }

    private function returnLine(string $classification, float $quantity, float $unitPrice, float $discount, float $net, float $vat, float $total): SalesReturnItem
    {
        return new SalesReturnItem([
            'quantity' => $quantity, 'unit_price' => $unitPrice, 'fiscal_discount_amount' => $discount,
            'fiscal_net_base' => $net, 'vat_rate' => $classification === 'vat_taxable' ? 13 : 0,
            'vat_amount' => $vat, 'total_price' => $total, 'tax_classification' => $classification,
        ]);
    }

    private function returnDocument(array $items): SalesReturn
    {
        $gross = $vat = $grand = 0.0;
        foreach ($items as $item) {
            $gross = round($gross + (float) $item->fiscal_net_base + (float) $item->fiscal_discount_amount, 2);
            $vat = round($vat + (float) $item->vat_amount, 2);
            $grand = round($grand + (float) $item->total_price, 2);
        }
        $return = new SalesReturn(['subtotal' => $gross, 'total_vat' => $vat, 'grand_total' => $grand]);
        $return->setRelation('items', collect($items));
        return $return;
    }
}
