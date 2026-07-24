<?php

namespace App\Services;

use App\Models\EmployeeAccount;
use App\Models\EmployeePayment;
use App\Models\FinancialYear;
use App\Models\SalarySheet;
use App\Services\Concerns\GuardsSubscriptionModule;
use Illuminate\Support\Collection;

class HrPayrollSummaryService
{
    use GuardsSubscriptionModule;
    public function summary(int $companyId, ?FinancialYear $financialYear = null): array
    {
        $this->assertSubscriptionModule($companyId, 'hr');

        $sheetQuery = SalarySheet::query()
            ->where('company_id', $companyId);

        if ($financialYear) {
            $sheetQuery->where('financial_year_id', $financialYear->id);
        }

        $activeSheets = (clone $sheetQuery)
            ->where('status', '!=', SalarySheet::STATUS_CANCELLED);

        $salaryGenerated = (float) (clone $activeSheets)->sum('net_salary');
        $salaryPaid = (float) (clone $activeSheets)->sum('paid_amount');
        $salaryDue = (float) (clone $activeSheets)->sum('due_amount');

        $paidEmployees = (clone $activeSheets)
            ->where('status', SalarySheet::STATUS_PAID)
            ->distinct('employee_id')
            ->count('employee_id');

        $partialEmployees = (clone $activeSheets)
            ->where('status', SalarySheet::STATUS_PARTIAL)
            ->distinct('employee_id')
            ->count('employee_id');

        $unpaidEmployees = (clone $activeSheets)
            ->where('status', SalarySheet::STATUS_UNPAID)
            ->distinct('employee_id')
            ->count('employee_id');

        $cancelledSalarySheets = (clone $sheetQuery)
            ->where('status', SalarySheet::STATUS_CANCELLED)
            ->count();

        $currentMonth = now()->format('Y-m');
        $currentMonthQuery = SalarySheet::query()
            ->where('company_id', $companyId)
            ->where('salary_month', $currentMonth)
            ->where('status', '!=', SalarySheet::STATUS_CANCELLED);

        if ($financialYear) {
            $currentMonthQuery->where('financial_year_id', $financialYear->id);
        }

        $currentMonthSummary = [
            'salary_month' => $currentMonth,
            'generated' => (float) (clone $currentMonthQuery)->sum('net_salary'),
            'paid' => (float) (clone $currentMonthQuery)->sum('paid_amount'),
            'due' => (float) (clone $currentMonthQuery)->sum('due_amount'),
            'sheets' => (clone $currentMonthQuery)->count(),
        ];

        return [
            'total_employees' => EmployeeAccount::where('company_id', $companyId)->count(),
            'salary_generated' => $salaryGenerated,
            'salary_paid' => $salaryPaid,
            'salary_due' => $salaryDue,
            'paid_employees' => $paidEmployees,
            'partial_employees' => $partialEmployees,
            'unpaid_employees' => $unpaidEmployees,
            'cancelled_salary_sheets' => $cancelledSalarySheets,
            'current_month' => $currentMonthSummary,
        ];
    }

    public function employeeLedger(int $companyId, int $employeeId, ?FinancialYear $financialYear = null): array
    {
        $employee = EmployeeAccount::where('company_id', $companyId)
            ->findOrFail($employeeId);

        $sheetQuery = SalarySheet::with(['employeePayments.account', 'employeePayments.creator'])
            ->where('company_id', $companyId)
            ->where('employee_id', $employee->id);

        if ($financialYear) {
            $sheetQuery->where('financial_year_id', $financialYear->id);
        }

        /** @var Collection<int, SalarySheet> $salarySheets */
        $salarySheets = $sheetQuery
            ->orderByDesc('salary_month')
            ->get();

        $payments = EmployeePayment::with(['account', 'salarySheet', 'creator', 'canceller'])
            ->where('company_id', $companyId)
            ->where('employee_account_id', $employee->id)
            ->when($financialYear, fn ($query) => $query->where('financial_year_id', $financialYear->id))
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->get();

        $openingDueSalary = round((float) ($employee->opening_due_salary ?? 0), 2);

        $activeSheetDue = round((float) $salarySheets
            ->where('status', '!=', SalarySheet::STATUS_CANCELLED)
            ->sum(function (SalarySheet $sheet) {
                return (float) $sheet->due_amount;
            }), 2);

        // Outstanding = opening due + active sheet due. Cancelled sheets/payments never participate (§10B).
        $outstandingDue = round($openingDueSalary + $activeSheetDue, 2);

        return [
            'employee' => $employee,
            'salary_sheets' => $salarySheets,
            'payments' => $payments,
            'opening_due_salary' => $openingDueSalary,
            'active_sheet_due' => $activeSheetDue,
            'outstanding_due' => $outstandingDue,
        ];
    }
}
