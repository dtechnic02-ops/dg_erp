<?php

namespace App\Services\Accounting\Profiles;

use InvalidArgumentException;
use RuntimeException;

class ProductOpeningStockPostingProfile
{
    public function build(array $data): array
    {
        $amount = $this->amount($data['amount'] ?? null, 'amount');
        $productId = $this->positiveInteger($data['product_id'] ?? null, 'product_id');

        if ($amount === '0.0000') {
            throw new RuntimeException('A zero-value product opening stock movement must not be posted to accounting.');
        }

        return [
            'company_id' => $this->positiveInteger($data['company_id'] ?? null, 'company_id'),
            'entry_date' => $this->requiredString($data, 'transaction_date'),
            'reference_number' => $this->requiredString($data, 'reference_number'),
            'source_module' => 'product',
            'source_type' => 'product_opening_stock',
            'source_id' => $productId,
            'source_event' => 'created',
            'source_key' => 'product-opening-stock:' . $productId,
            'description' => 'Product opening stock - ' . $this->requiredString($data, 'reference_number'),
            'posted_by' => $data['created_by'] ?? null,
            'lines' => [
                [
                    'chart_account_system_code' => 'INVENTORY',
                    'operational_account_id' => null,
                    'description' => 'Product opening inventory',
                    'debit' => $amount,
                    'credit' => '0.0000',
                    'subledger_type' => null,
                    'subledger_id' => null,
                ],
                [
                    'chart_account_system_code' => 'OPENING_BALANCE_EQUITY',
                    'operational_account_id' => null,
                    'description' => 'Product opening stock offset',
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
