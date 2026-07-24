<?php

namespace App\Services;

use App\Models\CrmConfiguration;
use App\Models\FinancialYear;
use Illuminate\Database\Eloquent\Model;

class CrmActivityService
{
    public function __construct(
        private CrmConfigurationService $configurationService
    ) {
    }

    public function resolveActiveFinancialYear(int $companyId): FinancialYear
    {
        $activeFy = FinancialYear::where('company_id', $companyId)
            ->where('is_active', 1)
            ->first();

        if (!$activeFy) {
            throw new \Exception('Please activate financial year first.');
        }

        return $activeFy;
    }

    public function validateBusinessDate(int $companyId, string $businessDate, string $message = null): FinancialYear
    {
        $activeFy = $this->resolveActiveFinancialYear($companyId);
        $this->configurationService->assertDateWithinActiveFinancialYear($activeFy, $businessDate, $message);

        return $activeFy;
    }

    public function financialYearPayload(FinancialYear $activeFy): array
    {
        return ['financial_year_id' => $activeFy->id];
    }
}
