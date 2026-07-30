<?php

namespace App\Services\Accounting\Profiles;

use App\Models\PurchaseInvoice;
use App\Services\Accounting\Builders\PurchaseAccountingDataBuilder;
use InvalidArgumentException;
use RuntimeException;

class PurchasePostingProfile
{
    public function __construct(
        private readonly PurchaseAccountingDataBuilder $builder
    ) {
    }

    public function build(PurchaseInvoice $purchase): array
    {
        $data = $this->builder->build($purchase);

        $companyId = $this->positiveInteger($this->required($data, 'company_id'), 'company_id');
        $purchaseId = $this->positiveInteger($this->required($data, 'purchase_id'), 'purchase_id');
        $purchaseDate = $this->requiredString($this->required($data, 'purchase_date'), 'purchase_date');
        $purchaseNumber = $this->requiredString($this->required($data, 'purchase_number'), 'purchase_number');
        $supplierId = $this->nullablePositiveInteger($data['supplier_id'] ?? null, 'supplier_id');
        $postedBy = $this->nullablePositiveInteger($data['created_by'] ?? null, 'created_by');
        $this->required($data, 'status');
        $totals = $this->requiredArray($data, 'totals');
        $payments = $this->requiredArray($data, 'payments');

        $purchaseAmount = $this->amount($this->required($totals, 'purchase_amount'), 'purchase_amount');
        $productPurchaseAmount = $this->amount($this->required($totals, 'product_purchase_amount'), 'product_purchase_amount');
        $servicePurchaseAmount = $this->amount($this->required($totals, 'service_purchase_amount'), 'service_purchase_amount');
        $taxAmount = $this->amount($this->required($totals, 'tax_amount'), 'tax_amount');
        $dueAmount = $this->amount($this->required($totals, 'due_amount'), 'due_amount');

        foreach (['grand_total', 'paid_amount', 'discount_amount'] as $key) {
            $this->amount($this->required($totals, $key), $key);
        }

        $lines = [];

        if (! $this->equals($this->add($productPurchaseAmount, $servicePurchaseAmount), $purchaseAmount)) {
            throw new RuntimeException('Product and service purchase amounts must equal the purchase amount.');
        }

        if (! $this->isZero($productPurchaseAmount)) {
            $lines[] = $this->line('INVENTORY', null, 'Purchase inventory - ' . $purchaseNumber, $productPurchaseAmount, '0.0000');
        }

        if (! $this->isZero($servicePurchaseAmount)) {
            $lines[] = $this->line('SERVICE_PURCHASE_EXPENSE', null, 'Purchase services - ' . $purchaseNumber, $servicePurchaseAmount, '0.0000');
        }

        if (! $this->isZero($taxAmount)) {
            $lines[] = $this->line('INPUT_TAX_RECEIVABLE', null, 'Input tax - ' . $purchaseNumber, $taxAmount, '0.0000');
        }

        foreach ($payments as $payment) {
            if (! is_array($payment)) {
                throw new InvalidArgumentException('Each payment must be a normalized array.');
            }

            $accountType = $this->requiredString($this->required($payment, 'account_type'), 'payment account_type');
            $amount = $this->amount($this->required($payment, 'amount'), 'payment amount');
            $operationalAccountId = $this->positiveInteger($this->required($payment, 'operational_account_id'), 'operational_account_id');
            $this->positiveInteger($this->required($payment, 'payment_id'), 'payment_id');

            if ($this->isZero($amount)) {
                throw new InvalidArgumentException('A payment amount must be greater than zero.');
            }

            $systemCode = match ($accountType) {
                'Cash' => 'CASH_IN_HAND',
                'Bank', 'ATM', 'Wallet' => 'BANK_ACCOUNTS',
                default => throw new RuntimeException('Unsupported payment account type for purchase accounting.'),
            };

            $lines[] = $this->line($systemCode, $operationalAccountId, 'Purchase payment', '0.0000', $amount);
        }

        if (! $this->isZero($dueAmount)) {
            if ($supplierId === null) {
                throw new RuntimeException('Supplier information is required for a due purchase amount.');
            }

            $lines[] = $this->line(
                'ACCOUNTS_PAYABLE',
                null,
                'Purchase payable - ' . $purchaseNumber,
                '0.0000',
                $dueAmount,
                'supplier',
                $supplierId
            );
        }

        $this->validateLines($lines);

        return [
            'company_id' => $companyId,
            'entry_date' => $purchaseDate,
            'reference_number' => $purchaseNumber,
            'source_module' => 'purchase',
            'source_type' => PurchaseInvoice::class,
            'source_id' => $purchaseId,
            'source_event' => 'created',
            'source_key' => 'purchase:' . $purchaseId . ':created',
            'description' => 'Purchase invoice - ' . $purchaseNumber,
            'posted_by' => $postedBy,
            'lines' => $lines,
        ];
    }

    private function line(string $systemCode, ?int $operationalAccountId, string $description, string $debit, string $credit, ?string $subledgerType = null, ?int $subledgerId = null): array
    {
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
            throw new RuntimeException('Purchase accounting requires at least two non-zero lines.');
        }

        $debitTotal = '0.0000';
        $creditTotal = '0.0000';

        foreach ($lines as $line) {
            $debit = $this->amount($line['debit'], 'line debit');
            $credit = $this->amount($line['credit'], 'line credit');

            if ($this->isZero($debit) && $this->isZero($credit)) {
                throw new RuntimeException('A generated accounting line cannot have zero debit and credit.');
            }

            if (! $this->isZero($debit) && ! $this->isZero($credit)) {
                throw new RuntimeException('A generated accounting line cannot contain both debit and credit.');
            }

            $debitTotal = $this->add($debitTotal, $debit);
            $creditTotal = $this->add($creditTotal, $credit);
        }

        if (! $this->equals($debitTotal, $creditTotal)) {
            throw new RuntimeException('Purchase accounting debit and credit totals must be equal.');
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

    private function amount(mixed $value, string $key): string
    {
        if (! is_string($value) || ! preg_match('/^\d+\.\d{4}$/', $value)) {
            throw new InvalidArgumentException("The normalized {$key} value must be a four-decimal string.");
        }

        [$whole, $fraction] = explode('.', $value, 2);

        return (ltrim($whole, '0') ?: '0') . '.' . $fraction;
    }

    private function isZero(string $amount): bool
    {
        return $this->scaled($amount) === '0';
    }

    private function equals(string $left, string $right): bool
    {
        return $this->scaled($left) === $this->scaled($right);
    }

    private function add(string $left, string $right): string
    {
        $left = $this->scaled($left);
        $right = $this->scaled($right);
        $carry = 0;
        $result = '';

        while ($left !== '' || $right !== '' || $carry > 0) {
            $sum = ($left === '' ? 0 : (int) substr($left, -1))
                + ($right === '' ? 0 : (int) substr($right, -1))
                + $carry;
            $result = ($sum % 10) . $result;
            $carry = intdiv($sum, 10);
            $left = substr($left, 0, -1);
            $right = substr($right, 0, -1);
        }

        return $this->decimal($result);
    }

    private function scaled(string $amount): string
    {
        [$whole, $fraction] = explode('.', $amount, 2);

        return ltrim($whole . $fraction, '0') ?: '0';
    }

    private function decimal(string $scaled): string
    {
        $scaled = str_pad(ltrim($scaled, '0') ?: '0', 5, '0', STR_PAD_LEFT);

        return substr($scaled, 0, -4) . '.' . substr($scaled, -4);
    }
}
