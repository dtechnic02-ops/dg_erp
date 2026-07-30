<?php

namespace App\Services\Accounting\Integrations;

use App\Models\PurchaseReturnRefund;
use App\Services\Accounting\AccountingPostingService;
use App\Services\Accounting\Builders\PurchaseReturnRefundAccountingDataBuilder;
use App\Services\Accounting\Profiles\PurchaseReturnRefundPostingProfile;
use InvalidArgumentException;

class PurchaseReturnRefundAccountingIntegrationService
{
    public function __construct(private readonly PurchaseReturnRefundAccountingDataBuilder $builder, private readonly PurchaseReturnRefundPostingProfile $profile, private readonly AccountingPostingService $postingService) {}
    public function postRefund(PurchaseReturnRefund $refund): void { if (! $refund->exists) throw new InvalidArgumentException('The purchase return refund must be saved before accounting can be posted.'); $this->postingService->post($this->profile->build($this->builder->build($refund))); }
    public function reverseRefund(PurchaseReturnRefund $refund, string $date, ?int $postedBy = null): void { if (! $refund->exists) throw new InvalidArgumentException('The purchase return refund must be saved before accounting can be reversed.'); $this->postingService->reverseBySource(['company_id'=>$refund->company_id,'entry_date'=>$date,'original_source_key'=>'purchase_return_refund:'.$refund->id.':created','original_source_event'=>'created','original_source_types'=>[PurchaseReturnRefund::class],'reversal_source_key'=>'purchase_return_refund_cancel:'.$refund->id.':cancelled','source_module'=>'purchase_return_refund','source_type'=>'purchase_return_refund','source_id'=>$refund->id,'source_event'=>'cancelled','reference_number'=>$refund->refund_no,'description'=>'Purchase return refund cancellation - '.$refund->refund_no,'posted_by'=>$postedBy]); }
}
