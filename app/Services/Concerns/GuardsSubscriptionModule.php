<?php

namespace App\Services\Concerns;

use App\Models\Company;
use App\Services\SubscriptionService;

trait GuardsSubscriptionModule
{
    protected function assertSubscriptionModule(int $companyId, string $module): void
    {
        $company = Company::find($companyId);

        if ($company) {
            app(SubscriptionService::class)->assertModuleAccess($company, $module);
        }
    }
}
