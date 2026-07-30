<?php

namespace App\Services\Accounting\Integrations;

use App\Models\SalesReturn;
use App\Services\Accounting\AccountingPostingService;
use App\Services\Accounting\Profiles\SalesReturnCogsPostingProfile;

class SalesReturnCogsAccountingIntegrationService
{
    public function __construct(private readonly SalesReturnCogsPostingProfile $profile, private readonly AccountingPostingService $postingService) {}
    public function postReturn(SalesReturn $return): void { if ($this->profile->hasProductItems($return)) $this->postingService->post($this->profile->build($return)); }
    public function reverseReturn(SalesReturn $return, string $date, ?int $postedBy = null): void
    {
        if (! $this->profile->hasProductItems($return)) return;
        $this->postingService->reverseBySource(['company_id' => $return->company_id, 'entry_date' => $date, 'original_source_key' => 'sales-return-cogs:' . $return->id . ':created', 'original_source_event' => 'created', 'original_source_types' => ['sales_return_cogs'], 'reversal_source_key' => 'sales-return-cogs:' . $return->id . ':cancelled', 'source_module' => 'sales_return_cogs', 'source_type' => 'sales_return_cogs', 'source_id' => $return->id, 'source_event' => 'cancelled', 'reference_number' => $return->return_no, 'description' => 'Sales return cancellation - ' . $return->return_no, 'posted_by' => $postedBy]);
    }
}
