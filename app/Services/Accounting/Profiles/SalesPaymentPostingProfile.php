<?php

namespace App\Services\Accounting\Profiles;

use App\Models\SalesPayment;
use InvalidArgumentException;
use RuntimeException;

class SalesPaymentPostingProfile
{
    public function build(array $data): array
    {
        $accountType = $this->requiredString($data, 'account_type');
        $amount = $this->amount($data['amount'] ?? null, 'amount');
        $operationalAccountId = $this->positiveInteger($data['account_id'] ?? null, 'account_id');
        $customerId = $this->positiveInteger($data['customer_id'] ?? null, 'customer_id');
        $paymentId = $this->positiveInteger($data['payment_id'] ?? null, 'payment_id');
        $paymentNumber = $this->requiredString($data, 'payment_number');

        if ($amount === '0.0000') {
            throw new RuntimeException('A sales payment accounting amount must be greater than zero.');
        }

        $operationalCode = match ($accountType) {
            'Cash' => 'CASH_IN_HAND',
            'Bank', 'ATM', 'Wallet' => 'BANK_ACCOUNTS',
            default => throw new RuntimeException('Unsupported operational account type for sales payment accounting.'),
        };

        return [
            'company_id' => $this->positiveInteger($data['company_id'] ?? null, 'company_id'),
            'entry_date' => $this->requiredString($data, 'payment_date'),
            'reference_number' => $paymentNumber,
            'source_module' => 'sales_payment',
            'source_type' => 'sales_payment',
            'source_type_aliases' => [SalesPayment::class],
            'source_id' => $paymentId,
            'source_event' => 'created',
            'source_key' => 'sales_payment:' . $paymentId . ':created',
            'description' => 'Sales payment - ' . $paymentNumber,
            'posted_by' => $data['created_by'] ?? null,
            'lines' => [
                [
                    'chart_account_system_code' => $operationalCode,
                    'operational_account_id' => $operationalAccountId,
                    'description' => 'Sales payment receipt - ' . $paymentNumber,
                    'debit' => $amount,
                    'credit' => '0.0000',
                    'subledger_type' => null,
                    'subledger_id' => null,
                ],
                [
                    'chart_account_system_code' => 'ACCOUNTS_RECEIVABLE',
                    'operational_account_id' => null,
                    'description' => 'Sales receivable settlement - ' . $paymentNumber,
                    'debit' => '0.0000',
                    'credit' => $amount,
                    'subledger_type' => 'customer',
                    'subledger_id' => $customerId,
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
