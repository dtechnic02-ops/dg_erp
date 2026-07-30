<?php

namespace App\Services\Accounting\Integrations;

use App\Models\PurchasePayment;
use App\Services\Accounting\AccountingPostingService;
use App\Services\Accounting\Builders\PurchasePaymentAccountingDataBuilder;
use App\Services\Accounting\Profiles\PurchasePaymentPostingProfile;
use InvalidArgumentException;

class PurchasePaymentAccountingIntegrationService
{
    public function __construct(
        private readonly PurchasePaymentAccountingDataBuilder $builder,
        private readonly PurchasePaymentPostingProfile $profile,
        private readonly AccountingPostingService $postingService
    ) {
    }

    public function postPayment(PurchasePayment $payment): void
    {
        if (! $payment->exists) {
            throw new InvalidArgumentException('The purchase payment must be saved before accounting can be posted.');
        }

        $this->postingService->post(
            $this->profile->build($this->builder->build($payment))
        );
    }

    public function reversePayment(PurchasePayment $payment, string $date, ?int $postedBy = null): void
    {
        if (! $payment->exists) {
            throw new InvalidArgumentException('The purchase payment must be saved before accounting can be reversed.');
        }

        $this->postingService->reverseBySource([
            'company_id' => $payment->company_id,
            'entry_date' => $date,
            'original_source_key' => 'purchase_payment:' . $payment->id . ':created',
            'original_source_event' => 'created',
            'original_source_types' => [PurchasePayment::class],
            'reversal_source_key' => 'purchase_payment_cancel:' . $payment->id . ':cancelled',
            'source_module' => 'purchase_payment',
            'source_type' => 'purchase_payment',
            'source_id' => $payment->id,
            'source_event' => 'cancelled',
            'reference_number' => $payment->payment_no,
            'description' => 'Purchase payment cancellation - ' . $payment->payment_no,
            'posted_by' => $postedBy,
        ]);
    }
}
