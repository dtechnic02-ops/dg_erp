<?php

namespace App\Services\Accounting\Builders;

use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\FinancialYear;
use DateTimeImmutable;
use InvalidArgumentException;
use RuntimeException;

class ExpenseAccountingDataBuilder
{
    public function build(Expense $expense): array
    {
        if (! $expense->exists || ! $expense->isActive()) {
            throw new RuntimeException('Only an active persisted expense can be posted to accounting.');
        }

        $companyId = $this->positiveInteger($expense->company_id, 'company_id');
        $expenseId = $this->positiveInteger($expense->id, 'expense_id');
        $financialYearId = $this->positiveInteger($expense->financial_year_id, 'financial_year_id');
        $expenseDate = $this->date($expense->expense_date, 'expense_date');
        $amount = $this->amount($expense->amount, 'amount');

        if ($this->isZero($amount)) {
            throw new RuntimeException('The persisted expense amount must be greater than zero.');
        }

        $financialYear = FinancialYear::query()
            ->where('company_id', $companyId)
            ->where('is_active', 1)
            ->find($financialYearId);

        if (! $financialYear || $expenseDate < $financialYear->start_date || $expenseDate > $financialYear->end_date) {
            throw new RuntimeException('The expense date does not belong to its active company financial year.');
        }

        $category = ExpenseCategory::query()
            ->where('company_id', $companyId)
            ->where('status', ExpenseCategory::STATUS_ACTIVE)
            ->with('chartAccount')
            ->find($this->positiveInteger($expense->expense_category_id, 'expense_category_id'));

        if (! $category || ! $category->chart_account_id) {
            throw new RuntimeException('The expense category must be mapped to an expense chart account.');
        }

        $chartAccount = $category->chartAccount;

        if (
            ! $chartAccount
            || (int) $chartAccount->company_id !== $companyId
            || $chartAccount->status !== 'active'
            || $chartAccount->account_class !== 'expense'
        ) {
            throw new RuntimeException('The expense category chart account is invalid for this company.');
        }

        $expenseChartSystemCode = $this->requiredString($chartAccount->system_code, 'expense chart account system_code');
        $accountId = $this->positiveInteger($expense->account_id, 'account_id');

        $account = Account::query()
            ->where('company_id', $companyId)
            ->where('status', 1)
            ->find($accountId);

        if (! $account || ! in_array($account->account_type, ['Cash', 'Bank', 'ATM', 'Wallet'], true)) {
            throw new RuntimeException('The expense operational account is invalid.');
        }

        $transactions = AccountTransaction::query()
            ->where('company_id', $companyId)
            ->where('reference_type', 'Expense')
            ->where('reference_id', $expenseId)
            ->where('status', 1)
            ->get();

        if ($transactions->count() !== 1) {
            throw new RuntimeException('The expense must have exactly one active account transaction.');
        }

        $transaction = $transactions->first();

        if (
            (int) $transaction->account_id !== $accountId
            || (int) $transaction->financial_year_id !== $financialYearId
            || ! $this->isZero($this->amount($transaction->debit, 'account transaction debit'))
            || ! $this->same($this->amount($transaction->credit, 'account transaction credit'), $amount)
        ) {
            throw new RuntimeException('The expense account transaction does not match the persisted expense.');
        }

        return [
            'company_id' => $companyId,
            'expense_id' => $expenseId,
            'expense_date' => $expenseDate,
            'expense_number' => $this->requiredString($expense->expense_no, 'expense_no'),
            'expense_chart_system_code' => $expenseChartSystemCode,
            'account_id' => $accountId,
            'account_type' => $account->account_type,
            'amount' => $amount,
            'created_by' => $this->nullablePositiveInteger($expense->created_by, 'created_by'),
        ];
    }

    private function requiredString(mixed $value, string $key): string
    {
        if (! is_string($value) || trim($value) === '') {
            throw new InvalidArgumentException("The {$key} value is required.");
        }

        return trim($value);
    }

    private function positiveInteger(mixed $value, string $key): int
    {
        if (filter_var($value, FILTER_VALIDATE_INT) === false || (int) $value < 1) {
            throw new InvalidArgumentException("The {$key} value must be a positive integer.");
        }

        return (int) $value;
    }

    private function nullablePositiveInteger(mixed $value, string $key): ?int
    {
        return $value === null ? null : $this->positiveInteger($value, $key);
    }

    private function date(mixed $value, string $key): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (! is_string($value) || trim($value) === '') {
            throw new InvalidArgumentException("The {$key} value is required.");
        }

        $value = trim($value);
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        $errors = DateTimeImmutable::getLastErrors();

        if (! $date || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) || $date->format('Y-m-d') !== $value) {
            throw new InvalidArgumentException("The {$key} value must be a valid Y-m-d date.");
        }

        return $date->format('Y-m-d');
    }

    private function amount(mixed $value, string $key): string
    {
        if (is_int($value) || is_float($value)) {
            $value = (string) $value;
        }

        if (! is_string($value) || ! preg_match('/^\d+(?:\.\d{1,4})?$/', trim($value))) {
            throw new InvalidArgumentException("The {$key} value must be a non-negative decimal amount.");
        }

        [$whole, $fraction] = array_pad(explode('.', trim($value), 2), 2, '');

        return (ltrim($whole, '0') ?: '0') . '.' . str_pad($fraction, 4, '0');
    }

    private function isZero(string $amount): bool
    {
        return $this->scaled($amount) === '0';
    }

    private function same(string $left, string $right): bool
    {
        return $this->scaled($left) === $this->scaled($right);
    }

    private function scaled(string $amount): string
    {
        [$whole, $fraction] = explode('.', $amount, 2);

        return ltrim($whole . $fraction, '0') ?: '0';
    }
}
