<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesCompanyPermission;
use App\Http\Controllers\Concerns\AuthorizesSubscriptionModule;
use App\Http\Controllers\Concerns\HandlesTransactionDocumentationEdit;
use Illuminate\Routing\Controllers\HasMiddleware;
use App\Models\EmployeeAccount;
use App\Models\FinancialYear;
use App\Models\SalarySheet;
use App\Services\ValidationService;
use App\Services\SalarySheetPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SalarySheetController extends Controller implements HasMiddleware
{
    use AuthorizesCompanyPermission;
    use AuthorizesSubscriptionModule;
    use HandlesTransactionDocumentationEdit;

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

        $employees = EmployeeAccount::where('company_id', $companyId)
            ->orderBy('first_name')
            ->get();

        $financialYears = FinancialYear::where('company_id', $companyId)
            ->latest('id')
            ->get();

        $activeFy = FinancialYear::where('company_id', $companyId)
            ->where('is_active', 1)
            ->first();

        $query = $this->filteredSalarySheetQuery($request, $companyId, $activeFy);

        $totalsQuery = (clone $query)
            ->where('status', '!=', SalarySheet::STATUS_CANCELLED);

        $totalAmount = (float) (clone $totalsQuery)->sum('net_salary');
        $totalPaid = (float) (clone $totalsQuery)->sum('paid_amount');
        $totalDue = (float) (clone $totalsQuery)->sum('due_amount');

        $allowedPerPage = [10, 20, 100, 200];
        $perPage = (int) $request->get('per_page', 10);

        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $salarySheets = $query
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('company.salary-sheets.index', compact(
            'salarySheets',
            'employees',
            'financialYears',
            'activeFy',
            'totalAmount',
            'totalPaid',
            'totalDue',
            'perPage'
        ));
    }

    public function print(Request $request)
    {
        $this->authorizeCompanyPermission('salary.view');

        $companyId = auth()->user()->company_id;

        $activeFy = FinancialYear::where('company_id', $companyId)
            ->where('is_active', 1)
            ->first();

        $salarySheets = $this->filteredSalarySheetQuery($request, $companyId, $activeFy)
            ->orderByDesc('salary_month')
            ->orderBy('employee_id')
            ->get();

        $totalsQuery = $salarySheets->where('status', '!=', SalarySheet::STATUS_CANCELLED);

        $totalAmount = (float) $totalsQuery->sum('net_salary');
        $totalPaid = (float) $totalsQuery->sum('paid_amount');
        $totalDue = (float) $totalsQuery->sum('due_amount');

        $financialYears = FinancialYear::where('company_id', $companyId)
            ->orderByDesc('id')
            ->get();

        return view('company.salary-sheets.print', compact(
            'salarySheets',
            'totalAmount',
            'totalPaid',
            'totalDue',
            'financialYears',
            'activeFy'
        ));
    }

    private function filteredSalarySheetQuery(Request $request, int $companyId, ?FinancialYear $activeFy)
    {
        $query = SalarySheet::with(['employee', 'financialYear'])
            ->where('company_id', $companyId);

        if (!$request->has('financial_year_id')) {
            if ($activeFy) {
                $query->where('financial_year_id', $activeFy->id);
            }
        } elseif ($request->filled('financial_year_id')) {
            $query->where('financial_year_id', $request->financial_year_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;

            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('first_name', 'like', '%' . $search . '%')
                    ->orWhere('middle_name', 'like', '%' . $search . '%')
                    ->orWhere('last_name', 'like', '%' . $search . '%')
                    ->orWhere('employee_code', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('salary_month')) {
            $query->where('salary_month', $request->salary_month);
        }

        if (!$request->has('status')) {
            $query->where('status', '!=', SalarySheet::STATUS_CANCELLED);
        } elseif ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        return $query;
    }

    public function create()
    {
        $this->authorizeCompanyPermission('salary.create');

        $companyId = auth()->user()->company_id;

        $employees = EmployeeAccount::where('company_id', $companyId)
            ->active()
            ->orderBy('first_name')
            ->get();

        $financialYears = FinancialYear::where('company_id', $companyId)
            ->orderByDesc('id')
            ->get();

        return view('company.salary-sheets.create', compact('employees', 'financialYears'));
    }

    public function store(Request $request)
    {
        $this->authorizeCompanyPermission('salary.create');

        $companyId = auth()->user()->company_id;

        $request->validate([
            'financial_year_id' => [
                'required',
                ValidationService::existsForCompany('financial_years', $companyId),
            ],
            'employee_id' => [
                'required',
                ValidationService::existsForCompany('employee_accounts', $companyId),
            ],
            'salary_month' => [
                'required',
                Rule::unique('salary_sheets', 'salary_month')->where(function ($query) use ($request, $companyId) {
                    return $query
                        ->where('company_id', $companyId)
                        ->where('financial_year_id', $request->financial_year_id)
                        ->where('employee_id', $request->employee_id)
                        ->where('status', '!=', SalarySheet::STATUS_CANCELLED);
                }),
            ],
            'working_days' => 'required|integer|min:1',
            'present_days' => 'required|integer|min:0',
            'absent_days' => 'nullable|integer|min:0',
            'allowance' => ValidationService::amount(),
            'bonus' => ValidationService::amount(),
            'overtime_amount' => ValidationService::amount(),
            'deduction' => ValidationService::amount(),
        ]);

        $employee = EmployeeAccount::where('company_id', $companyId)
            ->active()
            ->findOrFail($request->employee_id);

        if ($request->present_days + ($request->absent_days ?? 0) > $request->working_days) {
            return back()
                ->withInput()
                ->withErrors([
                    'present_days' => 'Present + Absent days cannot exceed Working Days.',
                ]);
        }

        if ($employee->basic_salary <= 0) {
            return back()
                ->withInput()
                ->withErrors([
                    'employee_id' => 'Employee basic salary is not set.',
                ]);
        }

        $financialYear = FinancialYear::where('company_id', $companyId)
            ->findOrFail($request->financial_year_id);

        try {
            $activeFy = $this->assertActiveFinancialYear($companyId);
            $this->assertFinancialYearIsActive($financialYear);
            $this->assertTransactionFinancialYear(
                new SalarySheet(['financial_year_id' => $financialYear->id]),
                $activeFy,
                'Salary sheet must belong to the active financial year.'
            );
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        DB::transaction(function () use ($request, $companyId, $employee) {
            $netSalary = $this->calculateNetSalary($employee, $request);

            SalarySheet::create([
                'company_id' => $companyId,
                'financial_year_id' => $request->financial_year_id,
                'employee_id' => $employee->id,
                'salary_month' => $request->salary_month,
                'basic_salary' => $employee->basic_salary,
                'working_days' => $request->working_days,
                'present_days' => $request->present_days,
                'absent_days' => $request->absent_days ?? 0,
                'allowance' => $request->allowance ?? 0,
                'bonus' => $request->bonus ?? 0,
                'overtime_amount' => $request->overtime_amount ?? 0,
                'deduction' => $request->deduction ?? 0,
                'net_salary' => $netSalary,
                'paid_amount' => 0,
                'due_amount' => $netSalary,
                'status' => SalarySheet::STATUS_UNPAID,
                'note' => $request->note,
                'created_by' => auth()->id(),
            ]);
        });

        return redirect()
            ->route('company.salary-sheets.index')
            ->with('success', 'Salary Sheet Created Successfully');
    }

    public function show($id)
    {
        $this->authorizeCompanyPermission('salary.view');

        $salarySheet = SalarySheet::with([
            'employee',
            'financialYear',
            'creator',
            'updater',
            'canceller',
            'employeePayments.account',
        ])
            ->where('company_id', auth()->user()->company_id)
            ->findOrFail($id);

        return view('company.salary-sheets.show', compact('salarySheet'));
    }

    public function edit($id)
    {
        $this->authorizeCompanyPermission('salary.edit');

        $companyId = auth()->user()->company_id;

        $salarySheet = SalarySheet::with('financialYear')
            ->where('company_id', $companyId)
            ->findOrFail($id);

        try {
            $this->assertEditable($salarySheet);
        } catch (\Exception $e) {
            return redirect()
                ->route('company.salary-sheets.show', $salarySheet->id)
                ->with('error', $e->getMessage());
        }

        $employees = EmployeeAccount::where('company_id', $companyId)
            ->where(function ($query) use ($salarySheet) {
                $query->active()
                    ->orWhere('id', $salarySheet->employee_id);
            })
            ->orderBy('first_name')
            ->get();

        return view('company.salary-sheets.edit', compact('salarySheet', 'employees'));
    }

    public function update(Request $request, $id)
    {
        $this->authorizeCompanyPermission('salary.edit');

        $companyId = auth()->user()->company_id;

        $salarySheet = SalarySheet::where('company_id', $companyId)
            ->findOrFail($id);

        try {
            $this->assertEditable($salarySheet);
            $this->assertFinancialYearIsActive(
                $salarySheet->financialYear
                    ?? FinancialYear::where('company_id', $companyId)
                        ->findOrFail($salarySheet->financial_year_id),
                'Inactive financial year cannot be edited.'
            );
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        $request->validate([
            'financial_year_id' => [
                'required',
                Rule::in([$salarySheet->financial_year_id]),
                ValidationService::existsForCompany('financial_years', $companyId),
            ],
            'employee_id' => [
                'required',
                ValidationService::existsForCompany('employee_accounts', $companyId),
            ],
            'salary_month' => [
                'required',
                Rule::unique('salary_sheets', 'salary_month')
                    ->ignore($salarySheet->id)
                    ->where(function ($query) use ($request, $companyId, $salarySheet) {
                        return $query
                            ->where('company_id', $companyId)
                            ->where('financial_year_id', $salarySheet->financial_year_id)
                            ->where('employee_id', $request->employee_id)
                            ->where('status', '!=', SalarySheet::STATUS_CANCELLED);
                    }),
            ],
            'working_days' => 'required|integer|min:1',
            'present_days' => 'required|integer|min:0',
            'absent_days' => 'nullable|integer|min:0',
            'allowance' => ValidationService::amount(),
            'bonus' => ValidationService::amount(),
            'overtime_amount' => ValidationService::amount(),
            'deduction' => ValidationService::amount(),
        ]);

        $employee = EmployeeAccount::where('company_id', $companyId)
            ->findOrFail($request->employee_id);

        if ($request->present_days + ($request->absent_days ?? 0) > $request->working_days) {
            return back()
                ->withInput()
                ->withErrors([
                    'present_days' => 'Present + Absent days cannot exceed Working Days.',
                ]);
        }

        $netSalary = $this->calculateNetSalary($employee, $request);

        DB::transaction(function () use ($request, $companyId, $employee, $salarySheet, $netSalary) {
            $lockedSheet = SalarySheet::where('company_id', $companyId)
                ->lockForUpdate()
                ->findOrFail($salarySheet->id);

            $this->assertEditable($lockedSheet);

            $lockedSheet->update([
                'employee_id' => $employee->id,
                'salary_month' => $request->salary_month,
                'basic_salary' => $employee->basic_salary,
                'working_days' => $request->working_days,
                'present_days' => $request->present_days,
                'absent_days' => $request->absent_days ?? 0,
                'allowance' => $request->allowance ?? 0,
                'bonus' => $request->bonus ?? 0,
                'overtime_amount' => $request->overtime_amount ?? 0,
                'deduction' => $request->deduction ?? 0,
                'net_salary' => $netSalary,
                'note' => $request->note,
                'updated_by' => auth()->id(),
            ]);

            app(SalarySheetPaymentService::class)->sync($lockedSheet->fresh());
        });

        return redirect()
            ->route('company.salary-sheets.index')
            ->with('success', 'Salary Sheet Updated Successfully');
    }

    public function cancel(Request $request, $id)
    {
        $this->authorizeCompanyPermission('salary.cancel');

        $request->validate([
            'cancel_reason' => ValidationService::requiredString(500),
        ]);

        try {
            DB::transaction(function () use ($request, $id) {
                $companyId = auth()->user()->company_id;

                $salarySheet = SalarySheet::with('financialYear')
                    ->where('company_id', $companyId)
                    ->lockForUpdate()
                    ->findOrFail($id);

                $this->assertCancellable($salarySheet);
                $this->assertFinancialYearIsActive(
                    $salarySheet->financialYear
                        ?? FinancialYear::where('company_id', $companyId)
                            ->findOrFail($salarySheet->financial_year_id),
                    'Inactive financial year cannot be cancelled.'
                );

                $cancelReason = trim($request->cancel_reason);

                $salarySheet->update([
                    'status' => SalarySheet::STATUS_CANCELLED,
                    'cancelled_by' => auth()->id(),
                    'cancelled_at' => now(),
                    'cancel_reason' => $cancelReason,
                    'updated_by' => auth()->id(),
                    'note' => trim(($salarySheet->note ?? '') . ' [Cancelled: ' . $cancelReason . ']'),
                ]);
            });

            return back()->with('success', 'Salary sheet cancelled successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    private function calculateNetSalary(EmployeeAccount $employee, Request $request): float
    {
        $perDaySalary = $request->working_days > 0
            ? ($employee->basic_salary / $request->working_days)
            : 0;

        $earnedSalary = $perDaySalary * $request->present_days;

        return round(
            $earnedSalary
            + ($request->allowance ?? 0)
            + ($request->bonus ?? 0)
            + ($request->overtime_amount ?? 0)
            - ($request->deduction ?? 0),
            2
        );
    }

    private function assertEditable(SalarySheet $salarySheet): void
    {
        if ($salarySheet->isCancelled()) {
            throw new \Exception('Cancelled salary sheet cannot be edited.');
        }

        if ($salarySheet->isPaid()) {
            throw new \Exception('Paid salary sheet cannot be edited.');
        }

        if ($salarySheet->isPartial()) {
            throw new \Exception('Partial salary sheet cannot be edited.');
        }
    }

    private function assertCancellable(SalarySheet $salarySheet): void
    {
        if ($salarySheet->isCancelled()) {
            throw new \Exception('Salary sheet already cancelled.');
        }

        if ($salarySheet->isPaid()) {
            throw new \Exception('Paid salary sheet cannot be cancelled.');
        }

        if ($salarySheet->isPartial()) {
            throw new \Exception('Partial salary sheet cannot be cancelled.');
        }

        if ($salarySheet->hasActiveEmployeePayments()) {
            throw new \Exception(
                'Salary Sheet cannot be cancelled because active salary payments exist. Cancel all salary payments before cancelling the salary sheet.'
            );
        }
    }
}
