<?php

namespace App\Services\Accounting\Integrations;

use App\Models\SalesReturnRefund;
use App\Services\Accounting\AccountingPostingService;
use App\Services\Accounting\Builders\SalesReturnRefundAccountingDataBuilder;
use App\Services\Accounting\Profiles\SalesReturnRefundPostingProfile;
use InvalidArgumentException;

class SalesReturnRefundAccountingIntegrationService
{
    public function __construct(private readonly SalesReturnRefundAccountingDataBuilder $builder, private readonly SalesReturnRefundPostingProfile $profile, private readonly AccountingPostingService $postingService) {}
    public function postRefund(SalesReturnRefund $refund): void
    {
        if (! $refund->exists) throw new InvalidArgumentException('The sales return refund must be saved before accounting can be posted.');
        $this->postingService->post($this->profile->build($this->builder->build($refund)));
    }
    public function reverseRefund(SalesReturnRefund $refund, string $reversalDate, ?int $postedBy = null): void
    {
        if (! $refund->exists) throw new InvalidArgumentException('The sales return refund must be saved before accounting can be reversed.');
        $this->postingService->reverseBySource(['company_id'=>$refund->company_id,'entry_date'=>$reversalDate,'original_source_key'=>'sales_return_refund:'.$refund->id.':created','original_source_event'=>'created','original_source_types'=>[SalesReturnRefund::class],'reversal_source_key'=>'sales_return_refund_cancel:'.$refund->id.':cancelled','source_module'=>'sales_return_refund','source_type'=>'sales_return_refund','source_id'=>$refund->id,'source_event'=>'cancelled','reference_number'=>$refund->refund_no,'description'=>'Sales return refund cancellation - '.$refund->refund_no,'posted_by'=>$postedBy]);
    }
}
