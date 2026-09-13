<?php

namespace App\Services\Accounting\Integrations;

use App\Models\AccountingEntry;
use App\Models\PurchaseReturn;
use App\Services\Accounting\AccountingPostingService;
use App\Services\Accounting\Builders\PurchaseReturnAccountingDataBuilder;
use App\Services\Accounting\Profiles\PurchaseReturnPostingProfile;
use InvalidArgumentException;
use RuntimeException;

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
            'description'=>'Reverse Purchase Return - '.$return->return_no,'posted_by'=>$postedBy,
        ]);
    }

    public function assertReversible(PurchaseReturn $return): void
    {
        if (! $return->exists) {
            throw new InvalidArgumentException('The Purchase Return must be saved before accounting can be reversed.');
        }

        $original = AccountingEntry::query()
            ->where('company_id', $return->company_id)
            ->where('source_key', 'purchase_return:'.$return->id.':created')
            ->whereIn('source_type', ['purchase_return', PurchaseReturn::class])
            ->where('source_id', $return->id)
            ->where('source_event', 'created')
            ->lockForUpdate()
            ->first();

        if (! $original || $original->status !== 'posted') {
            throw new RuntimeException('The original posted accounting entry could not be resolved for reversal.');
        }

        if (AccountingEntry::query()
            ->where('company_id', $return->company_id)
            ->where(function ($query) use ($return, $original): void {
                $query->where('source_key', 'purchase_return_cancel:'.$return->id.':cancelled')
                    ->orWhere('reversal_of_id', $original->id);
            })
            ->lockForUpdate()
            ->exists()) {
            throw new RuntimeException('This accounting entry has already been reversed.');
        }
    }
}
