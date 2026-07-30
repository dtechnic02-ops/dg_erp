<?php

namespace App\Services\Accounting\Profiles;

use InvalidArgumentException;
use RuntimeException;

class CustomerOpeningBalancePostingProfile
{
    public function build(array $data): array
    {
        $amount = $this->amount($data['amount'] ?? null, 'amount');
        $customerId = $this->positiveInteger($data['customer_id'] ?? null, 'customer_id');

        if ($amount === '0.0000') {
            throw new RuntimeException('A zero customer opening balance must not be posted to accounting.');
        }

        return [
            'company_id' => $this->positiveInteger($data['company_id'] ?? null, 'company_id'),
            'entry_date' => $this->requiredString($data, 'transaction_date'),
            'reference_number' => $this->requiredString($data, 'reference_number'),
            'source_module' => 'customer',
            'source_type' => 'customer_opening_balance',
            'source_id' => $customerId,
            'source_event' => 'created',
            'source_key' => 'customer-opening-balance:' . $customerId,
            'description' => 'Customer opening balance - ' . $this->requiredString($data, 'reference_number'),
            'posted_by' => $data['created_by'] ?? null,
            'lines' => [
                [
                    'chart_account_system_code' => 'ACCOUNTS_RECEIVABLE',
                    'operational_account_id' => null,
                    'description' => 'Customer opening receivable',
                    'debit' => $amount,
                    'credit' => '0.0000',
                    'subledger_type' => 'customer',
                    'subledger_id' => $customerId,
                ],
                [
                    'chart_account_system_code' => 'OPENING_BALANCE_EQUITY',
                    'operational_account_id' => null,
                    'description' => 'Customer opening balance offset',
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
