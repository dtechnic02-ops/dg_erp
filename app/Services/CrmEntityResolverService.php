<?php

namespace App\Services;

use App\Models\CrmContact;
use App\Models\CrmFollowUp;
use App\Models\CrmLead;
use App\Models\CrmMeeting;
use App\Models\CrmOpportunity;
use App\Models\CrmTask;
use Illuminate\Database\Eloquent\Model;

class CrmEntityResolverService
{
    public function resolve(int $companyId, string $entityType, int $entityId): Model
    {
        return match ($entityType) {
            'lead' => CrmLead::where('company_id', $companyId)->findOrFail($entityId),
            'opportunity' => CrmOpportunity::where('company_id', $companyId)->findOrFail($entityId),
            'contact' => CrmContact::where('company_id', $companyId)->findOrFail($entityId),
            'follow_up' => CrmFollowUp::where('company_id', $companyId)->findOrFail($entityId),
            'meeting' => CrmMeeting::where('company_id', $companyId)->findOrFail($entityId),
            'task' => CrmTask::where('company_id', $companyId)->findOrFail($entityId),
            default => throw new \Exception('Invalid CRM entity type.'),
        };
    }
}
