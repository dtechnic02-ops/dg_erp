<?php

namespace App\Services;

use App\Models\CrmStatusHistory;

class CrmStatusHistoryService
{
    public function record(
        int $companyId,
        string $entityType,
        int $entityId,
        string $event,
        ?string $previousValue = null,
        ?string $currentValue = null,
        ?string $remarks = null
    ): void {
        CrmStatusHistory::create([
            'company_id' => $companyId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'event' => $event,
            'previous_value' => $previousValue,
            'current_value' => $currentValue,
            'remarks' => $remarks,
            'changed_by' => auth()->id(),
            'changed_at' => now(),
        ]);
    }
}
