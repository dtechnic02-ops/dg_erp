<?php

namespace App\Services\Accounting;

use App\Models\PurchaseInvoice;
use App\Services\Accounting\Profiles\PurchasePostingProfile;
use InvalidArgumentException;

class PurchaseAccountingIntegrationService
{
    public function __construct(
        private readonly PurchasePostingProfile $profile,
        private readonly AccountingPostingService $postingService
    ) {
    }

    public function postPurchase(PurchaseInvoice $purchase): void
    {
        if (! $purchase->exists) {
            throw new InvalidArgumentException(
                'The purchase invoice must be saved before accounting can be posted.'
            );
        }

        $postingData = $this->profile->build($purchase);

        $this->postingService->post($postingData);
    }

    public function reversePurchase(PurchaseInvoice $purchase, string $date, ?int $postedBy = null): void
    {
        if (! $purchase->exists) {
            throw new InvalidArgumentException(
                'The purchase invoice must be saved before accounting can be reversed.'
            );
        }

        $this->postingService->reverseBySource([
            'company_id' => $purchase->company_id,
            'entry_date' => $date,
            'original_source_key' => 'purchase:' . $purchase->id . ':created',
            'original_source_event' => 'created',
            'original_source_types' => [PurchaseInvoice::class],
            'reversal_source_key' => 'purchase_cancel:' . $purchase->id . ':cancelled',
            'source_module' => 'purchase',
            'source_type' => 'purchase',
            'source_id' => $purchase->id,
            'source_event' => 'cancelled',
            'reference_number' => $purchase->invoice_no,
            'description' => 'Purchase cancellation - ' . $purchase->invoice_no,
            'posted_by' => $postedBy,
        ]);
    }
}
