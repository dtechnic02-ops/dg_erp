<?php

namespace App\Services;

use App\Models\FinancialYear;
use Carbon\Carbon;

class FinancialYearService
{
    public static function resolveForDate(
        int $companyId,
        string $date
    ): ?FinancialYear {
        $activeFy = FinancialYear::where('company_id', $companyId)
            ->where('is_active', 1)
            ->first();

        if (!$activeFy) {
            return null;
        }

        $parsedDate = Carbon::parse($date);
        $startDate = Carbon::parse($activeFy->start_date);
        $endDate = Carbon::parse($activeFy->end_date);

        if ($parsedDate->lt($startDate) || $parsedDate->gt($endDate)) {
            return null;
        }

        return $activeFy;
    }

    public static function activeForCompany(int $companyId): ?FinancialYear
    {
        return FinancialYear::where('company_id', $companyId)
            ->where('is_active', 1)
            ->first();
    }

    public static function assertDateWithinActiveFinancialYear(
        int $companyId,
        string $date,
        string $message = 'Date must belong to the active financial year.'
    ): FinancialYear {
        $activeFy = self::activeForCompany($companyId);

        if (!$activeFy) {
            throw new \Exception('Active financial year not found.');
        }

        $parsedDate = Carbon::parse($date);
        $startDate = Carbon::parse($activeFy->start_date);
        $endDate = Carbon::parse($activeFy->end_date);

        if ($parsedDate->lt($startDate) || $parsedDate->gt($endDate)) {
            throw new \Exception($message);
        }

        return $activeFy;
    }
}
