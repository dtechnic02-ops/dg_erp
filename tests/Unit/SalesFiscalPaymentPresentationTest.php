<?php

namespace Tests\Unit;

use App\Models\SalesInvoice;
use App\Services\NepalIrdCbmsModeService;
use App\Services\SalesFiscalPaymentModeService;
use Mockery;
use Tests\TestCase;

class SalesFiscalPaymentPresentationTest extends TestCase
{
    public function test_every_frozen_mode_has_the_exact_schedule_five_presentation(): void
    {
        $service = $this->service();
        $expected = [
            'cash' => ['category' => 'Cash', 'detail' => null, 'display' => 'Cash'],
            'credit' => ['category' => 'Credit', 'detail' => null, 'display' => 'Credit'],
            'bank' => ['category' => 'Other', 'detail' => 'Bank Transfer', 'display' => 'Other (Bank Transfer)'],
            'digital' => ['category' => 'Other', 'detail' => 'Digital Payment', 'display' => 'Other (Digital Payment)'],
            'mixed' => ['category' => 'Other', 'detail' => 'Mixed', 'display' => 'Other (Mixed)'],
            'other' => ['category' => 'Other', 'detail' => null, 'display' => 'Other'],
        ];

        foreach ($expected as $mode => $presentation) {
            $this->assertSame($presentation, $service->irdPresentation($mode));
            $this->assertStringNotContainsString('Cheque', $presentation['display']);
        }
    }

    public function test_missing_and_unsupported_modes_have_no_fabricated_presentation_and_block_readiness(): void
    {
        $service = $this->service();
        $missing = new SalesInvoice();
        $unsupported = new SalesInvoice();
        $unsupported->forceFill(['fiscal_payment_mode' => 'cheque', 'fiscal_payment_mode_captured_at' => now()]);

        $this->assertNull($service->irdPresentation(null));
        $this->assertNull($service->irdPresentation('cheque'));
        $this->assertSame(
            ['The sales invoice has no authoritative fiscal payment mode snapshot.'],
            $service->readinessErrors($missing)
        );
        $this->assertSame(
            ['The sales invoice contains an unsupported fiscal payment mode snapshot.'],
            $service->readinessErrors($unsupported)
        );
        $this->assertFalse($service->isComplete($missing));
        $this->assertFalse($service->isComplete($unsupported));
    }

    public function test_presentation_depends_only_on_the_frozen_mode(): void
    {
        $service = $this->service();
        $invoice = new SalesInvoice();
        $invoice->forceFill(['fiscal_payment_mode' => 'bank', 'fiscal_payment_mode_captured_at' => now()]);
        $before = $service->irdPresentation($invoice->fiscal_payment_mode);

        $invoice->setRelation('payments', collect([(object) ['payment_method' => 'Cash']]));
        $invoice->setRelation('customer', (object) ['name' => 'Changed Customer']);

        $this->assertSame($before, $service->irdPresentation($invoice->fiscal_payment_mode));
        $this->assertSame('Other (Bank Transfer)', $before['display']);
        $this->assertTrue($service->isComplete($invoice));
    }

    private function service(): SalesFiscalPaymentModeService
    {
        return new SalesFiscalPaymentModeService(Mockery::mock(NepalIrdCbmsModeService::class));
    }
}
