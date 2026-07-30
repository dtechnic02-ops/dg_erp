<?php

namespace App\Services\Accounting\Profiles;

use App\Models\SalesInvoice;
use App\Services\Accounting\Builders\SalesAccountingDataBuilder;
use InvalidArgumentException;
use RuntimeException;

class SalesPostingProfile
{
    public function __construct(
        private readonly SalesAccountingDataBuilder $builder
    ) {
    }

    public function build(SalesInvoice $sale): array
    {
        $data = $this->builder->build($sale);

        $companyId = $this->positiveInteger($this->required($data, 'company_id'), 'company_id');
        $saleId = $this->positiveInteger($this->required($data, 'sale_id'), 'sale_id');
        $saleDate = $this->requiredString($this->required($data, 'sale_date'), 'sale_date');
        $invoiceNumber = $this->requiredString($this->required($data, 'invoice_number'), 'invoice_number');
        $customerId = $this->nullablePositiveInteger($data['customer_id'] ?? null, 'customer_id');
        $postedBy = $this->nullablePositiveInteger($data['created_by'] ?? null, 'created_by');
        $this->required($data, 'status');
        $totals = $this->requiredArray($data, 'totals');
        $payments = $this->requiredArray($data, 'payments');

        $productRevenue = $this->normalizedAmount($this->required($totals, 'product_revenue'), 'product_revenue');
        $serviceRevenue = $this->normalizedAmount($this->required($totals, 'service_revenue'), 'service_revenue');
        $taxAmount = $this->normalizedAmount($this->required($totals, 'tax_amount'), 'tax_amount');
        $dueAmount = $this->normalizedAmount($this->required($totals, 'due_amount'), 'due_amount');

        foreach (['revenue_before_tax', 'grand_total', 'paid_amount', 'discount_amount'] as $key) {
            $this->normalizedAmount($this->required($totals, $key), $key);
        }

        $lines = $this->paymentLines($payments);

        if (! $this->isZero($dueAmount)) {
            if ($customerId === null) {
                throw new RuntimeException('Customer information is required for a due sales amount.');
            }

            $lines[] = $this->line(
                'ACCOUNTS_RECEIVABLE',
                null,
                'Sales receivable - ' . $invoiceNumber,
                $dueAmount,
                '0.0000',
                'customer',
                $customerId
            );
        }

        if (! $this->isZero($productRevenue)) {
            $lines[] = $this->line(
                'SALES_REVENUE',
                null,
                'Product sales revenue - ' . $invoiceNumber,
                '0.0000',
                $productRevenue
            );
        }

        if (! $this->isZero($serviceRevenue)) {
            $lines[] = $this->line(
                'SERVICE_REVENUE',
                null,
                'Service sales revenue - ' . $invoiceNumber,
                '0.0000',
                $serviceRevenue
            );
        }

        if (! $this->isZero($taxAmount)) {
            $lines[] = $this->line(
                'OUTPUT_TAX_PAYABLE',
                null,
                'Output tax - ' . $invoiceNumber,
                '0.0000',
                $taxAmount
            );
        }

        $this->validateLines($lines);

        return [
            'company_id' => $companyId,
            'entry_date' => $saleDate,
            'reference_number' => $invoiceNumber,
            'source_module' => 'sales',
            'source_type' => SalesInvoice::class,
            'source_id' => $saleId,
            'source_event' => 'created',
            'source_key' => 'sales:' . $saleId . ':created',
            'description' => 'Sales invoice - ' . $invoiceNumber,
            'posted_by' => $postedBy,
            'lines' => $lines,
        ];
    }

    private function paymentLines(array $payments): array
    {
        $lines = [];

        foreach ($payments as $payment) {
            if (! is_array($payment)) {
                throw new InvalidArgumentException('Each payment must be a normalized array.');
            }

            $accountType = $this->requiredString($this->required($payment, 'account_type'), 'payment account_type');
            $amount = $this->normalizedAmount($this->required($payment, 'amount'), 'payment amount');
            $operationalAccountId = $this->positiveInteger(
                $this->required($payment, 'operational_account_id'),
                'operational_account_id'
            );
            $this->positiveInteger($this->required($payment, 'payment_id'), 'payment_id');

            if ($this->isZero($amount)) {
                throw new InvalidArgumentException('A payment amount must be greater than zero.');
            }

            $systemCode = match ($accountType) {
                'Cash' => 'CASH_IN_HAND',
                'Bank', 'ATM', 'Wallet' => 'BANK_ACCOUNTS',
                default => throw new RuntimeException('Unsupported payment account type for sales accounting.'),
            };

            $lines[] = $this->line(
                $systemCode,
                $operationalAccountId,
                'Sales payment',
                $amount,
                '0.0000'
            );
        }

        return $lines;
    }

    private function line(
        string $systemCode,
        ?int $operationalAccountId,
        string $description,
        string $debit,
        string $credit,
        ?string $subledgerType = null,
        ?int $subledgerId = null
    ): array {
        return [
            'chart_account_system_code' => $systemCode,
            'operational_account_id' => $operationalAccountId,
            'description' => $description,
            'debit' => $debit,
            'credit' => $credit,
            'subledger_type' => $subledgerType,
            'subledger_id' => $subledgerId,
        ];
    }

    private function validateLines(array $lines): void
    {
        if (count($lines) < 2) {
            throw new RuntimeException('Sales accounting requires at least two non-zero lines.');
        }

        $totalDebit = '0.0000';
        $totalCredit = '0.0000';

        foreach ($lines as $line) {
            $debit = $this->normalizedAmount($line['debit'], 'line debit');
            $credit = $this->normalizedAmount($line['credit'], 'line credit');

            if ($this->isZero($debit) && $this->isZero($credit)) {
                throw new RuntimeException('A generated accounting line cannot have zero debit and credit.');
            }

            if (! $this->isZero($debit) && ! $this->isZero($credit)) {
                throw new RuntimeException('A generated accounting line cannot contain both debit and credit.');
            }

            $totalDebit = $this->addAmounts($totalDebit, $debit);
            $totalCredit = $this->addAmounts($totalCredit, $credit);
        }

        if ($totalDebit !== $totalCredit) {
            throw new RuntimeException('Sales accounting debit and credit totals must be equal.');
        }
    }

    private function required(array $data, string $key): mixed
    {
        if (! array_key_exists($key, $data)) {
            throw new InvalidArgumentException("Missing normalized builder key: {$key}.");
        }

        return $data[$key];
    }

    private function requiredArray(array $data, string $key): array
    {
        $value = $this->required($data, $key);

        if (! is_array($value)) {
            throw new InvalidArgumentException("The normalized {$key} value must be an array.");
        }

        return $value;
    }

    private function requiredString(mixed $value, string $key): string
    {
        if (! is_string($value) || trim($value) === '') {
            throw new InvalidArgumentException("The normalized {$key} value is required.");
        }

        return trim($value);
    }

    private function positiveInteger(mixed $value, string $key): int
    {
        if (filter_var($value, FILTER_VALIDATE_INT) === false || (int) $value < 1) {
            throw new InvalidArgumentException("The normalized {$key} value must be a positive integer.");
        }

        return (int) $value;
    }

    private function nullablePositiveInteger(mixed $value, string $key): ?int
    {
        return $value === null ? null : $this->positiveInteger($value, $key);
    }

    private function normalizedAmount(mixed $value, string $key): string
    {
        if (! is_string($value) || ! preg_match('/^\d+\.\d{4}$/', $value)) {
            throw new InvalidArgumentException("The normalized {$key} value must be a four-decimal string.");
        }

        [$whole, $fraction] = explode('.', $value, 2);

        return (ltrim($whole, '0') ?: '0') . '.' . $fraction;
    }

    private function isZero(string $amount): bool
    {
        return $this->scaledAmount($amount) === '0';
    }

    private function addAmounts(string $left, string $right): string
    {
        $left = $this->scaledAmount($left);
        $right = $this->scaledAmount($right);
        $carry = 0;
        $result = '';

        while ($left !== '' || $right !== '' || $carry > 0) {
            $leftDigit = $left === '' ? 0 : (int) substr($left, -1);
            $rightDigit = $right === '' ? 0 : (int) substr($right, -1);
            $sum = $leftDigit + $rightDigit + $carry;

            $result = ($sum % 10) . $result;
            $carry = intdiv($sum, 10);
            $left = substr($left, 0, -1);
            $right = substr($right, 0, -1);
        }

        return $this->decimalAmount($result);
    }

    private function scaledAmount(string $amount): string
    {
        [$whole, $fraction] = explode('.', $amount, 2);

        return ltrim($whole . $fraction, '0') ?: '0';
    }

    private function decimalAmount(string $scaledAmount): string
    {
        $scaledAmount = str_pad(ltrim($scaledAmount, '0') ?: '0', 5, '0', STR_PAD_LEFT);

        return substr($scaledAmount, 0, -4) . '.' . substr($scaledAmount, -4);
    }
}
