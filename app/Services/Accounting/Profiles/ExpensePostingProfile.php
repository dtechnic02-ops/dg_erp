<?php

namespace App\Services\Accounting\Profiles;

use App\Models\Expense;
use InvalidArgumentException;
use RuntimeException;

class ExpensePostingProfile
{
    public function build(array $data): array
    {
        $expenseId = $this->positiveInteger($data['expense_id'] ?? null, 'expense_id');
        $expenseNumber = $this->requiredString($data, 'expense_number');
        $expenseChartSystemCode = $this->requiredString($data, 'expense_chart_system_code');
        $accountType = $this->requiredString($data, 'account_type');
        $accountId = $this->positiveInteger($data['account_id'] ?? null, 'account_id');
        $amount = $this->amount($data['amount'] ?? null, 'amount');

        if ($amount === '0.0000') {
            throw new RuntimeException('An expense accounting amount must be greater than zero.');
        }

        $operationalCode = match ($accountType) {
            'Cash' => 'CASH_IN_HAND',
            'Bank', 'ATM', 'Wallet' => 'BANK_ACCOUNTS',
            default => throw new RuntimeException('Unsupported operational account type for expense accounting.'),
        };

        return [
            'company_id' => $this->positiveInteger($data['company_id'] ?? null, 'company_id'),
            'entry_date' => $this->requiredString($data, 'expense_date'),
            'reference_number' => $expenseNumber,
            'source_module' => 'expense',
            'source_type' => 'expense',
            'source_type_aliases' => [Expense::class],
            'source_id' => $expenseId,
            'source_event' => 'created',
            'source_key' => 'expense:' . $expenseId . ':created',
            'description' => 'Expense - ' . $expenseNumber,
            'posted_by' => $data['created_by'] ?? null,
            'lines' => [
                [
                    'chart_account_system_code' => $expenseChartSystemCode,
                    'operational_account_id' => null,
                    'description' => 'Expense recognition - ' . $expenseNumber,
                    'debit' => $amount,
                    'credit' => '0.0000',
                    'subledger_type' => null,
                    'subledger_id' => null,
                ],
                [
                    'chart_account_system_code' => $operationalCode,
                    'operational_account_id' => $accountId,
                    'description' => 'Expense payment - ' . $expenseNumber,
                    'debit' => '0.0000',
                    'credit' => $amount,
                    'subledger_type' => null,
                    'subledger_id' => null,
                ],
            ],
        ];
    }

    private function requiredString(array $data, string $key): string
    {
        $value = $data[$key] ?? null;

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
}
