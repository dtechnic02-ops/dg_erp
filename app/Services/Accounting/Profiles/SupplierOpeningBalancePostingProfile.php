<?php

namespace App\Services\Accounting\Profiles;

use InvalidArgumentException;
use RuntimeException;

class SupplierOpeningBalancePostingProfile
{
    public function build(array $data): array
    {
        $amount = $this->amount($data['amount'] ?? null, 'amount');
        $supplierId = $this->positiveInteger($data['supplier_id'] ?? null, 'supplier_id');

        if ($amount === '0.0000') {
            throw new RuntimeException('A zero supplier opening balance must not be posted to accounting.');
        }

        return [
            'company_id' => $this->positiveInteger($data['company_id'] ?? null, 'company_id'),
            'entry_date' => $this->requiredString($data, 'transaction_date'),
            'reference_number' => $this->requiredString($data, 'reference_number'),
            'source_module' => 'supplier',
            'source_type' => 'supplier_opening_balance',
            'source_id' => $supplierId,
            'source_event' => 'created',
            'source_key' => 'supplier-opening-balance:' . $supplierId,
            'description' => 'Supplier opening balance - ' . $this->requiredString($data, 'reference_number'),
            'posted_by' => $data['created_by'] ?? null,
            'lines' => [
                [
                    'chart_account_system_code' => 'OPENING_BALANCE_EQUITY',
                    'operational_account_id' => null,
                    'description' => 'Supplier opening balance offset',
                    'debit' => $amount,
                    'credit' => '0.0000',
                    'subledger_type' => null,
                    'subledger_id' => null,
                ],
                [
                    'chart_account_system_code' => 'ACCOUNTS_PAYABLE',
                    'operational_account_id' => null,
                    'description' => 'Supplier opening payable',
                    'debit' => '0.0000',
                    'credit' => $amount,
                    'subledger_type' => 'supplier',
                    'subledger_id' => $supplierId,
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
