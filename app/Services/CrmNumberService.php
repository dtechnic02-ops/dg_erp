<?php

namespace App\Services;

use App\Models\CrmFollowUp;
use App\Models\CrmLead;
use App\Models\CrmMeeting;
use App\Models\CrmOpportunity;
use App\Models\CrmTask;
use App\Models\CrmContact;

class CrmNumberService
{
    public static function generateLeadNo(int $companyId): string
    {
        return self::nextNumber($companyId, CrmLead::class, 'lead_no', 'REL');
    }

    public static function generateOpportunityNo(int $companyId): string
    {
        return self::nextNumber($companyId, CrmOpportunity::class, 'opportunity_no', 'OPP');
    }

    public static function generateContactNo(int $companyId): string
    {
        return self::nextNumber($companyId, CrmContact::class, 'contact_no', 'CONTACT');
    }

    public static function generateActivityNo(int $companyId, string $modelClass): string
    {
        $column = match ($modelClass) {
            CrmFollowUp::class => 'activity_no',
            CrmMeeting::class => 'activity_no',
            CrmTask::class => 'activity_no',
            default => 'activity_no',
        };

        return self::nextNumber($companyId, $modelClass, $column, 'ACT');
    }

    private static function nextNumber(int $companyId, string $modelClass, string $column, string $prefix): string
    {
        $year = now()->format('Y');
        $pattern = $prefix . '-' . $year . '-%';

        $last = $modelClass::where('company_id', $companyId)
            ->where($column, 'like', $pattern)
            ->lockForUpdate()
            ->orderByDesc('id')
            ->first();

        $next = 1;

        if ($last && preg_match('/(\d+)$/', $last->{$column}, $match)) {
            $next = ((int) $match[1]) + 1;
        }

        return $prefix . '-' . $year . '-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
