<?php

namespace App\Services\Accounting\Profiles;

use App\Models\Income;
use InvalidArgumentException;
use RuntimeException;

class IncomePostingProfile
{
    public function build(array $data): array
    {
        $incomeId = $this->positiveInteger($data['income_id'] ?? null, 'income_id');
        $incomeNumber = $this->requiredString($data, 'income_number');
        $incomeChartAccountId = $this->positiveInteger($data['income_chart_account_id'] ?? null, 'income_chart_account_id');
        $accountType = $this->requiredString($data, 'account_type');
        $accountId = $this->positiveInteger($data['account_id'] ?? null, 'account_id');
        $amount = $this->amount($data['amount'] ?? null, 'amount');

        if ($amount === '0.0000') {
            throw new RuntimeException('An income accounting amount must be greater than zero.');
        }

        $operationalCode = match ($accountType) {
            'Cash' => 'CASH_IN_HAND',
            'Bank', 'ATM', 'Wallet' => 'BANK_ACCOUNTS',
            default => throw new RuntimeException('Unsupported operational account type for income accounting.'),
        };

        return [
            'company_id' => $this->positiveInteger($data['company_id'] ?? null, 'company_id'),
            'entry_date' => $this->requiredString($data, 'income_date'),
            'reference_number' => $incomeNumber,
            'source_module' => 'income',
            'source_type' => 'income',
            'source_type_aliases' => [Income::class],
            'source_id' => $incomeId,
            'source_event' => 'created',
            'source_key' => 'income:' . $incomeId . ':created',
            'description' => 'Income - ' . $incomeNumber,
            'posted_by' => $data['created_by'] ?? null,
            'lines' => [
                [
                    'chart_account_system_code' => $operationalCode,
                    'operational_account_id' => $accountId,
                    'description' => 'Income receipt - ' . $incomeNumber,
                    'debit' => $amount,
                    'credit' => '0.0000',
                    'subledger_type' => null,
                    'subledger_id' => null,
                ],
                [
                    'chart_account_id' => $incomeChartAccountId,
                    'operational_account_id' => null,
                    'description' => 'Income recognition - ' . $incomeNumber,
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
