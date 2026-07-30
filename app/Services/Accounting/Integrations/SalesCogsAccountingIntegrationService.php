<?php

namespace App\Services\Accounting\Integrations;

use App\Models\SalesInvoice;
use App\Services\Accounting\AccountingPostingService;
use App\Services\Accounting\Profiles\SalesCogsPostingProfile;
use InvalidArgumentException;

class SalesCogsAccountingIntegrationService
{
    public function __construct(
        private readonly SalesCogsPostingProfile $profile,
        private readonly AccountingPostingService $postingService
    ) {
    }

    public function postSaleCogs(SalesInvoice $sale): void
    {
        if (! $sale->exists) {
            throw new InvalidArgumentException('The sales invoice must be saved before COGS can be posted.');
        }

        if (! $this->profile->hasSnapshots($sale)) {
            if ($this->profile->hasProductItems($sale)) {
                throw new \RuntimeException('Every product sale requires an inventory valuation cost snapshot before COGS can be posted.');
            }

            return;
        }

        $this->postingService->post($this->profile->build($sale));
    }

    public function reverseSaleCogs(SalesInvoice $sale, string $date, ?int $postedBy = null): void
    {
        if (! $this->profile->hasSnapshots($sale)) {
            if ($this->profile->hasProductItems($sale)) throw new \RuntimeException('Every product sale requires an inventory valuation cost snapshot before COGS can be reversed.');
            return;
        }
        $this->postingService->reverseBySource(['company_id' => $sale->company_id, 'entry_date' => $date, 'original_source_key' => 'sales-cogs:' . $sale->id . ':created', 'original_source_event' => 'created', 'original_source_types' => ['sales_cogs'], 'reversal_source_key' => 'sales-cogs:' . $sale->id . ':cancelled', 'source_module' => 'sales_cogs', 'source_type' => 'sales_cogs', 'source_id' => $sale->id, 'source_event' => 'cancelled', 'reference_number' => $sale->invoice_no, 'description' => 'Sales COGS cancellation - ' . $sale->invoice_no, 'posted_by' => $postedBy]);
    }
}
