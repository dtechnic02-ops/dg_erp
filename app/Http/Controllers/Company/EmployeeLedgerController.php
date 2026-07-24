<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesCompanyPermission;
use App\Http\Controllers\Concerns\AuthorizesSubscriptionModule;
use Illuminate\Routing\Controllers\HasMiddleware;
use App\Models\FinancialYear;
use App\Services\HrPayrollSummaryService;
use Illuminate\Http\Request;

class EmployeeLedgerController extends Controller implements HasMiddleware
{
    use AuthorizesCompanyPermission;
    use AuthorizesSubscriptionModule;

    public static function middleware(): array
    {
        return self::subscriptionModuleMiddleware();
    }

    protected static function subscriptionModuleCode(): string
    {
        return 'hr';
    }

    public function __construct(
        private HrPayrollSummaryService $hrPayrollSummaryService
    ) {
    }

    public function show(Request $request, $id)
    {
        $this->authorizeCompanyPermission('salary.view');

        $companyId = auth()->user()->company_id;

        $financialYear = null;

        if ($request->filled('financial_year_id')) {
            $financialYear = FinancialYear::where('company_id', $companyId)
                ->find($request->financial_year_id);
        } else {
            $financialYear = FinancialYear::where('company_id', $companyId)
                ->where('is_active', 1)
                ->first();
        }

        $ledger = $this->hrPayrollSummaryService->employeeLedger(
            $companyId,
            (int) $id,
            $financialYear
        );

        $financialYears = FinancialYear::where('company_id', $companyId)
            ->orderByDesc('id')
            ->get();

        return view('company.employee-ledger.show', array_merge($ledger, [
            'financialYears' => $financialYears,
            'selectedFinancialYear' => $financialYear,
        ]));
    }
}
