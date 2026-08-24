<?php

namespace App\Services\Accounting\Integrations;

use App\Models\PurchaseReturn;
use App\Services\Accounting\AccountingPostingService;
use App\Services\Accounting\Builders\PurchaseReturnAccountingDataBuilder;
use App\Services\Accounting\Profiles\PurchaseReturnPostingProfile;
use InvalidArgumentException;

class PurchaseReturnAccountingIntegrationService
{
    public function __construct(private readonly PurchaseReturnAccountingDataBuilder $builder, private readonly PurchaseReturnPostingProfile $profile, private readonly AccountingPostingService $postingService) {}

    public function postReturn(PurchaseReturn $return): void
    {
        if (! $return->exists) throw new InvalidArgumentException('The Purchase Return must be saved before accounting can be posted.');
        $this->postingService->post($this->profile->build($this->builder->build($return)));
    }

    public function reverseReturn(PurchaseReturn $return, string $date, ?int $postedBy = null): void
    {
        if (! $return->exists) throw new InvalidArgumentException('The Purchase Return must be saved before accounting can be reversed.');
        $this->postingService->reverseBySource([
            'company_id'=>$return->company_id,'financial_year_id'=>$return->financial_year_id,'entry_date'=>$date,
            'original_source_key'=>'purchase_return:'.$return->id.':created','original_source_event'=>'created','original_source_types'=>['purchase_return',PurchaseReturn::class],
            'reversal_source_key'=>'purchase_return_cancel:'.$return->id.':cancelled','source_module'=>'purchase_return','source_type'=>'purchase_return',
            'source_id'=>$return->id,'source_event'=>'cancelled','reference_number'=>$return->return_no,
            'description'=>'Purchase Return cancellation - '.$return->return_no,'posted_by'=>$postedBy,
        ]);
    }
}
