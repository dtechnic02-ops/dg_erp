<?php

namespace Tests\Feature;

use App\Models\SalesInvoice;
use App\Models\SalesItem;
use App\Services\SalesTaxClassificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SalesTaxClassificationFoundationTest extends TestCase
{
    use RefreshDatabase;

    private SalesTaxClassificationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SalesTaxClassificationService::class);
    }

    public function test_schema_has_historical_tax_classification_snapshots(): void
    {
        $this->assertTrue(Schema::hasColumn('sales_items', 'tax_classification'));
        $this->assertTrue(Schema::hasColumn('sales_return_items', 'tax_classification'));
    }

    public function test_rate_and_classification_consistency_is_enforced_server_side(): void
    {
        $this->assertSame('vat_taxable', $this->service->normalizeForNewLine('vat_taxable', 13, true));
        $this->assertSame('vat_exempt', $this->service->normalizeForNewLine('vat_exempt', 0, true));
        $this->assertSame('zero_rated', $this->service->normalizeForNewLine('zero_rated', 0, true));
        $this->assertSame('export', $this->service->normalizeForNewLine('export', 0, true));
        $this->assertSame('out_of_scope', $this->service->normalizeForNewLine('out_of_scope', 0, true));

        foreach ([
            ['vat_taxable', 0],
            ['vat_exempt', 13],
            ['zero_rated', 13],
            ['export', 13],
            ['out_of_scope', 13],
        ] as [$classification, $rate]) {
            try {
                $this->service->normalizeForNewLine($classification, $rate, true);
                $this->fail('A mismatched rate/classification pair was accepted.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('tax_classification', $exception->errors());
            }
        }
    }

    public function test_non_nepal_legacy_fallback_never_guesses_zero_rate_as_exempt(): void
    {
        $this->assertSame('vat_taxable', $this->service->normalizeForNewLine(null, 13, false));
        $this->assertSame('legacy_unclassified', $this->service->normalizeForNewLine(null, 0, false));

        $this->expectException(ValidationException::class);
        $this->service->normalizeForNewLine(null, 0, true);
    }

    public function test_mixed_tax_summary_separates_every_canonical_base_without_changing_math(): void
    {
        $items = collect([
            $this->line('vat_taxable', 2, 100, 13),
            $this->line('vat_exempt', 1, 50, 0),
            $this->line('export', 3, 20, 0),
            $this->line('zero_rated', 1, 30, 0),
            $this->line('out_of_scope', 1, 10, 0),
            $this->line('legacy_unclassified', 1, 5, 0),
        ]);

        $summary = $this->service->summarize($items);

        $this->assertSame(200.0, $summary['taxable_sales_vat']);
        $this->assertSame(50.0, $summary['tax_exempted_sales']);
        $this->assertSame(60.0, $summary['export_sales']);
        $this->assertSame(30.0, $summary['zero_rated_sales']);
        $this->assertSame(10.0, $summary['out_of_scope_sales']);
        $this->assertSame(5.0, $summary['legacy_unclassified_sales']);
    }

    public function test_cbms_readiness_accepts_reconciled_supported_bases_and_blocks_unsafe_cases(): void
    {
        $ready = $this->invoice(276, 26, 0, collect([
            $this->line('vat_taxable', 2, 100, 13),
            $this->line('vat_exempt', 1, 50, 0),
        ]));
        $readiness = app(\App\Services\SalesFiscalReadinessService::class);
        $this->assertSame([], $readiness->errors($ready));

        $discounted = $this->invoice(275, 26, 1, $ready->items);
        $this->assertStringContainsString('invoice-level discount', implode(' ', $readiness->errors($discounted)));

        $unsupported = $this->invoice(286, 26, 0, collect([
            $this->line('vat_taxable', 2, 100, 13),
            $this->line('zero_rated', 1, 60, 0),
        ]));
        $this->assertStringContainsString('not mapped', implode(' ', $readiness->errors($unsupported)));

        $legacy = $this->invoice(281, 26, 0, collect([
            $this->line('vat_taxable', 2, 100, 13),
            $this->line('legacy_unclassified', 1, 55, 0),
        ]));
        $this->assertStringContainsString('no authoritative tax classification', implode(' ', $readiness->errors($legacy)));
    }

    private function line(string $classification, float $quantity, float $unitPrice, float $vatRate): SalesItem
    {
        return new SalesItem([
            'tax_classification' => $classification,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'vat_rate' => $vatRate,
        ]);
    }

    private function invoice(float $grandTotal, float $vat, float $discount, $items): SalesInvoice
    {
        $invoice = new SalesInvoice([
            'grand_total' => $grandTotal,
            'total_vat' => $vat,
            'discount' => $discount,
        ]);
        $invoice->setRelation('items', $items);

        return $invoice;
    }
}
