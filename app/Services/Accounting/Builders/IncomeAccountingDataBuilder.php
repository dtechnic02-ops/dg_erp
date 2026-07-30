<?php

namespace App\Services\Accounting\Builders;

use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\ChartAccount;
use App\Models\FinancialYear;
use App\Models\Income;
use App\Models\IncomeCategory;
use DateTimeImmutable;
use InvalidArgumentException;
use RuntimeException;

class IncomeAccountingDataBuilder
{
    public function build(Income $income): array
    {
        if (! $income->exists || ! $income->isActive()) {
            throw new RuntimeException('Only an active persisted income can be posted to accounting.');
        }

        $companyId = $this->positiveInteger($income->company_id, 'company_id');
        $incomeId = $this->positiveInteger($income->id, 'income_id');
        $financialYearId = $this->positiveInteger($income->financial_year_id, 'financial_year_id');
        $incomeDate = $this->date($income->income_date, 'income_date');
        $amount = $this->amount($income->amount, 'amount');

        if ($this->isZero($amount)) {
            throw new RuntimeException('The persisted income amount must be greater than zero.');
        }

        $financialYear = FinancialYear::query()
            ->where('company_id', $companyId)
            ->where('is_active', 1)
            ->find($financialYearId);

        if (! $financialYear || $incomeDate < $financialYear->start_date || $incomeDate > $financialYear->end_date) {
            throw new RuntimeException('The income date does not belong to its active company financial year.');
        }

        $category = IncomeCategory::query()
            ->where('company_id', $companyId)
            ->where('status', IncomeCategory::STATUS_ACTIVE)
            ->find($this->positiveInteger($income->income_category_id, 'income_category_id'));

        if (! $category || ! $category->chart_account_id) {
            throw new RuntimeException('The income category must be mapped to an income chart account.');
        }

        $incomeChartAccountId = $this->positiveInteger($category->chart_account_id, 'income category chart_account_id');
        $chartAccount = ChartAccount::query()
            ->forCompany($companyId)
            ->where('status', 'active')
            ->find($incomeChartAccountId);

        if (! $chartAccount || $chartAccount->account_class !== 'income') {
            throw new RuntimeException('The income category chart account is invalid for this company.');
        }

        $accountId = $this->positiveInteger($income->account_id, 'account_id');
        $account = Account::query()
            ->where('company_id', $companyId)
            ->where('status', 1)
            ->find($accountId);

        if (! $account || ! in_array($account->account_type, ['Cash', 'Bank', 'ATM', 'Wallet'], true)) {
            throw new RuntimeException('The income operational account is invalid.');
        }

        $transactions = AccountTransaction::query()
            ->where('company_id', $companyId)
            ->where('reference_type', 'Income')
            ->where('reference_id', $incomeId)
            ->where('status', 1)
            ->get();

        if ($transactions->count() !== 1) {
            throw new RuntimeException('The income must have exactly one active account transaction.');
        }

        $transaction = $transactions->first();

        if (
            (int) $transaction->account_id !== $accountId
            || (int) $transaction->financial_year_id !== $financialYearId
            || ! $this->same($this->amount($transaction->debit, 'account transaction debit'), $amount)
            || ! $this->isZero($this->amount($transaction->credit, 'account transaction credit'))
        ) {
            throw new RuntimeException('The income account transaction does not match the persisted income.');
        }

        return [
            'company_id' => $companyId,
            'income_id' => $incomeId,
            'income_date' => $incomeDate,
            'income_number' => $this->requiredString($income->income_no, 'income_no'),
            'income_chart_account_id' => $incomeChartAccountId,
            'account_id' => $accountId,
            'account_type' => $account->account_type,
            'amount' => $amount,
            'created_by' => $this->nullablePositiveInteger($income->created_by, 'created_by'),
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
