<?php

namespace App\Services\Accounting\Integrations;

use App\Models\SalesPayment;
use App\Services\Accounting\AccountingPostingService;
use App\Services\Accounting\Builders\SalesPaymentAccountingDataBuilder;
use App\Services\Accounting\Profiles\SalesPaymentPostingProfile;
use InvalidArgumentException;

class SalesPaymentAccountingIntegrationService
{
    public function __construct(
        private readonly SalesPaymentAccountingDataBuilder $builder,
        private readonly SalesPaymentPostingProfile $profile,
        private readonly AccountingPostingService $postingService
    ) {
    }

    public function postPayment(SalesPayment $payment): void
    {
        if (! $payment->exists) {
            throw new InvalidArgumentException('The sales payment must be saved before accounting can be posted.');
        }

        $this->postingService->post(
            $this->profile->build($this->builder->build($payment))
        );
    }

    public function reversePayment(SalesPayment $payment, string $date, ?int $postedBy = null): void
    {
        if (! $payment->exists) {
            throw new InvalidArgumentException('The sales payment must be saved before accounting can be reversed.');
        }

        $this->postingService->reverseBySource([
            'company_id' => $payment->company_id,
            'entry_date' => $date,
            'original_source_key' => 'sales_payment:' . $payment->id . ':created',
            'original_source_event' => 'created',
            'original_source_types' => [SalesPayment::class],
            'reversal_source_key' => 'sales_payment_cancel:' . $payment->id . ':cancelled',
            'source_module' => 'sales_payment',
            'source_type' => 'sales_payment',
            'source_id' => $payment->id,
            'source_event' => 'cancelled',
            'reference_number' => $payment->payment_no,
            'description' => 'Sales payment cancellation - ' . $payment->payment_no,
            'posted_by' => $postedBy,
        ]);
    }
}
