<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesCompanyPermission;
use App\Http\Controllers\Concerns\AuthorizesSubscriptionModule;
use App\Services\CrmDashboardService;
use App\Services\CrmConfigurationService;
use Illuminate\Routing\Controllers\HasMiddleware;

class CrmDashboardController extends Controller implements HasMiddleware
{
    use AuthorizesCompanyPermission;
    use AuthorizesSubscriptionModule;

    public static function middleware(): array
    {
        return self::subscriptionModuleMiddleware();
    }

    protected static function subscriptionModuleCode(): string
    {
        return 'crm';
    }

    public function __construct(
        private CrmDashboardService $dashboardService,
        private CrmConfigurationService $configurationService
    ) {
    }

    public function index()
    {
        $this->authorizeCompanyPermission('view_crm_dashboard');

        $companyId = auth()->user()->company_id;
        $this->configurationService->ensureDefaults($companyId);
        $summary = $this->dashboardService->summary($companyId);

        return view('company.crm.dashboard.index', compact('summary'));
    }
}
