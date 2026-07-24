<?php

namespace App\Services;

use App\Models\EmployeePayment;
use App\Models\FinancialYear;
use App\Models\SalarySheet;
use App\Services\Concerns\GuardsSubscriptionModule;

class SalarySheetPaymentService
{
    use GuardsSubscriptionModule;
    public static function generateVoucherNo(int $companyId, FinancialYear $activeFy): string
    {
        $lastPayment = EmployeePayment::where('company_id', $companyId)
            ->where('financial_year_id', $activeFy->id)
            ->lockForUpdate()
            ->latest('id')
            ->first();

        $next = 1;

        if ($lastPayment && preg_match('/(\d+)$/', $lastPayment->voucher_no, $match)) {
            $next = ((int) $match[1]) + 1;
        }

        return 'EP-'
            . $companyId
            . '-'
            . $activeFy->id
            . '-'
            . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    public function sync(SalarySheet $salarySheet): SalarySheet
    {
        $this->assertSubscriptionModule((int) $salarySheet->company_id, 'hr');

        return self::syncSalarySheetPaymentState($salarySheet);
    }

    public static function syncSalarySheetPaymentState(SalarySheet $salarySheet): SalarySheet
    {
        (new static())->assertSubscriptionModule((int) $salarySheet->company_id, 'hr');

        $salarySheet->refresh();

        if ($salarySheet->isCancelled()) {
            return $salarySheet;
        }

        $paidAmount = round((float) EmployeePayment::query()
            ->where('company_id', $salarySheet->company_id)
            ->where('salary_sheet_id', $salarySheet->id)
            ->where('status', EmployeePayment::STATUS_ACTIVE)
            ->sum('amount'), 2);

        $netSalary = round((float) $salarySheet->net_salary, 2);
        $dueAmount = max(0, round($netSalary - $paidAmount, 2));

        $salarySheet->update([
            'paid_amount' => $paidAmount,
            'due_amount' => $dueAmount,
            'status' => self::resolveSalarySheetPaymentStatus($paidAmount, $dueAmount),
        ]);

        return $salarySheet->fresh();
    }

    public static function resolveSalarySheetPaymentStatus(float $paidAmount, float $dueAmount): string
    {
        if ($dueAmount <= 0) {
            return SalarySheet::STATUS_PAID;
        }

        if ($paidAmount > 0) {
            return SalarySheet::STATUS_PARTIAL;
        }

        return SalarySheet::STATUS_UNPAID;
    }

    public function assertPaymentAmount(
        SalarySheet $salarySheet,
        float $amount,
        ?int $excludePaymentId = null
    ): void {
        if ($salarySheet->isCancelled()) {
            throw new \Exception('Cannot pay a cancelled salary sheet.');
        }

        $query = EmployeePayment::query()
            ->where('company_id', $salarySheet->company_id)
            ->where('salary_sheet_id', $salarySheet->id)
            ->where('status', EmployeePayment::STATUS_ACTIVE);

        if ($excludePaymentId) {
            $query->where('id', '!=', $excludePaymentId);
        }

        $alreadyPaid = round((float) $query->sum('amount'), 2);
        $netSalary = round((float) $salarySheet->net_salary, 2);
        $remaining = max(0, round($netSalary - $alreadyPaid, 2));

        if ($amount > $remaining) {
            throw new \Exception(
                'Payment amount exceeds remaining due salary. Net Salary: '
                . number_format($netSalary, 2)
                . ', Already Paid: '
                . number_format($alreadyPaid, 2)
                . ', Maximum Allowed: '
                . number_format($remaining, 2)
                . '.'
            );
        }
    }
}
