<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesCompanyPermission;
use App\Http\Controllers\Concerns\AuthorizesSubscriptionModule;
use Illuminate\Routing\Controllers\HasMiddleware;
use App\Models\EmployeeAccount;
use App\Models\FinancialYear;
use App\Models\SalarySheet;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class PayrollRegisterController extends Controller implements HasMiddleware
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

    public function index(Request $request)
    {
        $this->authorizeCompanyPermission('salary.view');

        $companyId = auth()->user()->company_id;
        $company = auth()->user()->company;

        $query = $this->buildRegisterQuery($request, $companyId);

        $totalsQuery = (clone $query)
            ->where('status', '!=', SalarySheet::STATUS_CANCELLED);

        $totals = [
            'net_salary' => (float) (clone $totalsQuery)->sum('net_salary'),
            'paid' => (float) (clone $totalsQuery)->sum('paid_amount'),
            'due' => (float) (clone $totalsQuery)->sum('due_amount'),
        ];

        $totalCount = (clone $query)->count();
        $activeCount = (clone $query)
            ->where('status', '!=', SalarySheet::STATUS_CANCELLED)
            ->count();
        $cancelledCount = (clone $query)
            ->where('status', SalarySheet::STATUS_CANCELLED)
            ->count();

        $salarySheets = $query
            ->orderByDesc('salary_month')
            ->orderBy('employee_id')
            ->paginate(25)
            ->withQueryString();

        $financialYears = FinancialYear::where('company_id', $companyId)
            ->orderByDesc('id')
            ->get();

        $departments = EmployeeAccount::where('company_id', $companyId)
            ->whereNotNull('department')
            ->where('department', '!=', '')
            ->distinct()
            ->orderBy('department')
            ->pluck('department');

        return view('company.payroll-register.index', compact(
            'salarySheets',
            'totals',
            'totalCount',
            'activeCount',
            'cancelledCount',
            'financialYears',
            'departments',
            'company'
        ));
    }

    public function print(Request $request)
    {
        $this->authorizeCompanyPermission('salary.view');

        $companyId = auth()->user()->company_id;
        $company = auth()->user()->company;

        $query = $this->buildRegisterQuery($request, $companyId);

        $salarySheets = $query
            ->orderByDesc('salary_month')
            ->orderBy('employee_id')
            ->get();

        $totalsQuery = $salarySheets
            ->where('status', '!=', SalarySheet::STATUS_CANCELLED);

        $totals = [
            'net_salary' => (float) $totalsQuery->sum('net_salary'),
            'paid' => (float) $totalsQuery->sum('paid_amount'),
            'due' => (float) $totalsQuery->sum('due_amount'),
        ];

        $activeCount = $salarySheets
            ->where('status', '!=', SalarySheet::STATUS_CANCELLED)
            ->count();
        $cancelledCount = $salarySheets
            ->where('status', SalarySheet::STATUS_CANCELLED)
            ->count();

        return view('company.payroll-register.print', compact(
            'salarySheets',
            'totals',
            'activeCount',
            'cancelledCount',
            'company'
        ));
    }

    private function buildRegisterQuery(Request $request, int $companyId): Builder
    {
        $query = SalarySheet::with(['employee', 'financialYear'])
            ->where('company_id', $companyId);

        if ($request->filled('financial_year_id')) {
            $query->where('financial_year_id', $request->financial_year_id);
        } else {
            $activeFy = FinancialYear::where('company_id', $companyId)
                ->where('is_active', 1)
                ->first();

            if ($activeFy) {
                $query->where('financial_year_id', $activeFy->id);
            }
        }

        if ($request->filled('salary_month')) {
            $query->where('salary_month', $request->salary_month);
        }

        if (!$request->has('status')) {
            $query->where('status', '!=', SalarySheet::STATUS_CANCELLED);
        } elseif ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('department')) {
            $query->whereHas('employee', function ($employeeQuery) use ($request) {
                $employeeQuery->where('department', 'like', '%' . $request->department . '%');
            });
        }

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->whereHas('employee', function ($employeeQuery) use ($search) {
                $employeeQuery->where('first_name', 'like', '%' . $search . '%')
                    ->orWhere('employee_code', 'like', '%' . $search . '%')
                    ->orWhere('department', 'like', '%' . $search . '%');
            });
        }

        return $query;
    }
}
