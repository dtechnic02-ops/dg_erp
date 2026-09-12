<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\SalesInvoice;
use App\Models\SalesItem;
use App\Services\NepalIrdCbmsModeService;
use App\Services\SalesFiscalIssueDateTimeService;
use App\Services\SalesFiscalPaymentModeService;
use App\Services\SalesFiscalReadinessService;
use App\Services\SalesFiscalSnapshotService;
use App\Services\SalesTaxClassificationService;
use App\Services\SalesFiscalLineAmountService;
use App\Services\SalesFiscalReconciliationService;
use Mockery;
use Tests\TestCase;

class SalesFiscalReadinessServiceTest extends TestCase
{
    public function test_ready_active_invoice_has_no_errors(): void
    {
        $invoice = $this->invoice(226, 26, 0, [
            $this->line(SalesTaxClassificationService::VAT_TAXABLE, 2, 100),
        ]);
        $service = $this->service($invoice, true, true, true, true, []);

        $this->assertSame([], $service->errors($invoice));
        $this->assertTrue($service->isReady($invoice));
    }

    public function test_every_existing_readiness_blocker_is_preserved(): void
    {
        $invoice = $this->invoice(999, 26, 1, [
            $this->line(SalesTaxClassificationService::LEGACY_UNCLASSIFIED, 1, 100),
            $this->line(SalesTaxClassificationService::ZERO_RATED, 1, 50),
        ]);
        $details = [
            'One or more physical sales lines have no authoritative fiscal origin snapshot.',
            'One or more imported sales lines have no valid immutable H.S. Code snapshot.',
        ];
        $errors = $this->service($invoice, true, false, false, false, $details)->errors($invoice);

        $this->assertSame([
            'The sales invoice has no complete immutable fiscal identity snapshot.',
            'The sales invoice has no authoritative fiscal payment mode snapshot.',
            'The sales invoice has no immutable fiscal issue timestamp.',
            ...$details,
            'Sales line 1 is legacy_unclassified and cannot be fiscally reconciled.',
            'Fiscal classification buckets do not equal canonical net sales.',
            'Fiscal header subtotal does not equal canonical gross sales.',
            'Fiscal header VAT does not equal canonical VAT.',
            'Fiscal header grand total does not equal canonical grand total.',
            'Fiscal header discount does not equal canonical line discounts.',
            'One or more sales lines have no authoritative tax classification.',
            'Zero Rated and Out of Scope sales are not mapped to an official IRD CBMS Version 1 payload bucket.',
        ], $errors);
    }

    public function test_inactive_mode_keeps_legacy_behavior_and_skips_fiscal_evidence_checks(): void
    {
        $invoice = $this->invoice(100, 0, 0, [
            $this->line(SalesTaxClassificationService::VAT_EXEMPT, 1, 100),
        ]);

        $this->assertSame([], $this->service($invoice, false)->errors($invoice));
    }

    public function test_active_readiness_is_blocked_by_each_canonical_header_mismatch(): void
    {
        foreach (['subtotal', 'discount', 'total_vat', 'grand_total'] as $field) {
            $invoice = $this->invoice(113, 13, 0, [
                $this->line(SalesTaxClassificationService::VAT_TAXABLE, 1, 100),
            ]);
            $invoice->setAttribute($field, (float) $invoice->getAttribute($field) + 1);
            $errors = $this->service($invoice, true, true, true, true)->errors($invoice);

            $this->assertNotEmpty($errors);
            $this->assertFalse($this->service($invoice, true, true, true, true)->isReady($invoice));
        }
    }

    public function test_unsupported_frozen_payment_mode_is_blocked_by_aggregate_readiness(): void
    {
        $invoice = $this->invoice(113, 13, 0, [
            $this->line(SalesTaxClassificationService::VAT_TAXABLE, 1, 100),
        ]);
        $errors = $this->service(
            $invoice,
            true,
            true,
            true,
            true,
            [],
            ['The sales invoice contains an unsupported fiscal payment mode snapshot.'],
        )->errors($invoice);

        $this->assertContains('The sales invoice contains an unsupported fiscal payment mode snapshot.', $errors);
    }

    private function service(
        SalesInvoice $invoice,
        bool $active,
        bool $snapshotComplete = false,
        bool $paymentComplete = false,
        bool $issueTimeComplete = false,
        array $itemErrors = [],
        ?array $paymentErrors = null,
    ): SalesFiscalReadinessService {
        $mode = Mockery::mock(NepalIrdCbmsModeService::class);
        $mode->shouldReceive('isActiveForCompany')->with($invoice->company)->andReturn($active);
        $snapshots = Mockery::mock(SalesFiscalSnapshotService::class);
        $payments = Mockery::mock(SalesFiscalPaymentModeService::class);
        $issueTime = Mockery::mock(SalesFiscalIssueDateTimeService::class);

        if ($active) {
            $snapshots->shouldReceive('isComplete')->with($invoice)->andReturn($snapshotComplete);
            $snapshots->shouldReceive('itemDetailComplianceErrors')->with($invoice)->andReturn($itemErrors);
            $payments->shouldReceive('readinessErrors')->with($invoice)->andReturn($paymentErrors
                ?? ($paymentComplete ? [] : ['The sales invoice has no authoritative fiscal payment mode snapshot.']));
            $issueTime->shouldReceive('isComplete')->with($invoice)->andReturn($issueTimeComplete);
        }

        return new SalesFiscalReadinessService(
            $mode,
            new SalesTaxClassificationService(),
            $snapshots,
            $payments,
            $issueTime,
            new SalesFiscalReconciliationService(
                new SalesFiscalLineAmountService(),
                new SalesTaxClassificationService(),
            ),
        );
    }

    private function invoice(float $grandTotal, float $vat, float $discount, array $items): SalesInvoice
    {
        $invoice = new SalesInvoice(['subtotal' => $grandTotal - $vat + $discount, 'grand_total' => $grandTotal, 'total_vat' => $vat, 'discount' => $discount]);
        $invoice->setRelation('company', new Company(['company_name' => 'Readiness Company']));
        $invoice->setRelation('items', collect($items));
        return $invoice;
    }

    private function line(string $classification, float $quantity, float $unitPrice): SalesItem
    {
        $gross = round($quantity * $unitPrice, 2);
        $vat = $classification === SalesTaxClassificationService::VAT_TAXABLE ? round($gross * 0.13, 2) : 0;
        return new SalesItem([
            'tax_classification' => $classification,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'vat_rate' => $vat > 0 ? 13 : 0,
            'vat_amount' => $vat,
            'fiscal_discount_amount' => 0,
            'fiscal_net_base' => $gross,
            'total_price' => $gross + $vat,
        ]);
    }
}
