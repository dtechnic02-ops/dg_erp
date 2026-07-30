<?php

namespace App\Services\Accounting;

use App\Models\SalesInvoice;
use App\Services\Accounting\Profiles\SalesPostingProfile;
use InvalidArgumentException;

class SalesAccountingIntegrationService
{
    public function __construct(
        private readonly SalesPostingProfile $profile,
        private readonly AccountingPostingService $postingService
    ) {
    }

    public function postSale(SalesInvoice $sale): void
    {
        if (! $sale->exists) {
            throw new InvalidArgumentException(
                'The sales invoice must be saved before accounting can be posted.'
            );
        }

        $postingData = $this->profile->build($sale);

        $this->postingService->post($postingData);
    }

    public function reverseSale(SalesInvoice $sale, string $date, ?int $postedBy = null): void
    {
        $this->postingService->reverseBySource(['company_id' => $sale->company_id, 'entry_date' => $date, 'original_source_key' => 'sales:' . $sale->id . ':created', 'original_source_event' => 'created', 'original_source_types' => [SalesInvoice::class], 'reversal_source_key' => 'sales:' . $sale->id . ':cancelled', 'source_module' => 'sales', 'source_type' => 'sales', 'source_id' => $sale->id, 'source_event' => 'cancelled', 'reference_number' => $sale->invoice_no, 'description' => 'Sales cancellation - ' . $sale->invoice_no, 'posted_by' => $postedBy]);
    }
}
