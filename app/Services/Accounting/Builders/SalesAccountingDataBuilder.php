<?php

namespace App\Services\Accounting\Builders;

use App\Models\SalesInvoice;
use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;
use RuntimeException;

class SalesAccountingDataBuilder
{
    public function build(SalesInvoice $sale): array
    {
        if (! $sale->exists) {
            throw new InvalidArgumentException('The sales invoice must be saved before accounting can be posted.');
        }

        if ((int) $sale->status !== 1) {
            throw new RuntimeException('Only active sales invoices can be posted to accounting.');
        }

        $companyId = $this->positiveInteger($sale->company_id, 'company_id');
        $saleId = $this->positiveInteger($sale->id, 'sale_id');
        $saleDate = $this->date($sale->getRawOriginal('sale_date') ?? $sale->sale_date, 'sale_date');
        $invoiceNumber = $this->requiredString($sale->invoice_no, 'invoice_no');
        $customerId = $this->nullablePositiveInteger($sale->customer_id, 'customer_id');
        $createdBy = $this->nullablePositiveInteger($sale->created_by, 'created_by');

        $grandTotal = $this->amount($sale->grand_total, 'grand_total');
        $taxAmount = $this->amount($sale->total_vat, 'total_vat');
        $discountAmount = $this->amount($sale->discount, 'discount');
        $paidAmount = $this->amount($sale->paid_amount, 'paid_amount');
        $dueAmount = $this->amount($sale->due_amount, 'due_amount');

        if ($this->isZero($grandTotal)) {
            throw new RuntimeException('The sales invoice grand total must be greater than zero.');
        }

        if ($this->compare($paidAmount, $grandTotal) > 0) {
            throw new RuntimeException('The persisted paid amount cannot exceed the sales invoice grand total.');
        }

        if (! $this->equals($this->add($paidAmount, $dueAmount), $grandTotal)) {
            throw new RuntimeException('The persisted paid and due amounts must equal the sales invoice grand total.');
        }

        if ($this->compare($taxAmount, $grandTotal) > 0) {
            throw new RuntimeException('The persisted tax amount cannot exceed the sales invoice grand total.');
        }

        $revenueBeforeTax = $this->subtract($grandTotal, $taxAmount);
        [$rawProductRevenue, $rawServiceRevenue] = $this->itemRevenue($sale);
        $rawRevenue = $this->add($rawProductRevenue, $rawServiceRevenue);

        [$productRevenue, $serviceRevenue] = $this->reconcileDiscount(
            $rawProductRevenue,
            $rawServiceRevenue,
            $revenueBeforeTax,
            $discountAmount
        );

        $payments = $this->payments($sale, $companyId);
        $paymentTotal = '0.0000';

        foreach ($payments as $payment) {
            $paymentTotal = $this->add($paymentTotal, $payment['amount']);
        }

        if (! $this->equals($paymentTotal, $paidAmount)) {
            throw new RuntimeException('Active sales payments must equal the persisted paid amount.');
        }

        return [
            'company_id' => $companyId,
            'sale_id' => $saleId,
            'sale_date' => $saleDate,
            'invoice_number' => $invoiceNumber,
            'customer_id' => $customerId,
            'created_by' => $createdBy,
            'status' => $sale->status,
            'totals' => [
                'product_revenue' => $productRevenue,
                'service_revenue' => $serviceRevenue,
                'revenue_before_tax' => $revenueBeforeTax,
                'tax_amount' => $taxAmount,
                'grand_total' => $grandTotal,
                'paid_amount' => $paidAmount,
                'due_amount' => $dueAmount,
                'discount_amount' => $discountAmount,
            ],
            'payments' => $payments,
        ];
    }

    private function itemRevenue(SalesInvoice $sale): array
    {
        $productRevenue = '0.0000';
        $serviceRevenue = '0.0000';

        foreach ($sale->items()->get() as $item) {
            $total = $this->amount($item->total_price, 'sales item total_price');
            $vat = $this->amount($item->vat_amount, 'sales item vat_amount');

            if ($this->compare($vat, $total) > 0) {
                throw new RuntimeException('A sales item tax amount cannot exceed its total price.');
            }

            $revenue = $this->subtract($total, $vat);

            if ($item->item_type === 'product') {
                $productRevenue = $this->add($productRevenue, $revenue);
                continue;
            }

            if ($item->item_type === 'service') {
                $serviceRevenue = $this->add($serviceRevenue, $revenue);
                continue;
            }

            throw new RuntimeException('A persisted sales item has an unsupported item type.');
        }

        return [$productRevenue, $serviceRevenue];
    }

    private function reconcileDiscount(
        string $rawProductRevenue,
        string $rawServiceRevenue,
        string $revenueBeforeTax,
        string $discountAmount
    ): array {
        $rawRevenue = $this->add($rawProductRevenue, $rawServiceRevenue);

        if ($this->equals($rawRevenue, $revenueBeforeTax)) {
            if (! $this->isZero($discountAmount)) {
                throw new RuntimeException('The persisted sales item totals do not reconcile with the invoice discount.');
            }

            return [$rawProductRevenue, $rawServiceRevenue];
        }

        if ($this->compare($rawRevenue, $discountAmount) < 0 || ! $this->equals(
            $this->subtract($rawRevenue, $discountAmount),
            $revenueBeforeTax
        )) {
            throw new RuntimeException('The persisted sales item totals do not reconcile with the sales invoice revenue and discount.');
        }

        $productDiscount = $this->proportionalAmount(
            $discountAmount,
            $rawProductRevenue,
            $rawRevenue
        );
        $serviceDiscount = $this->subtract($discountAmount, $productDiscount);

        return [
            $this->subtract($rawProductRevenue, $productDiscount),
            $this->subtract($rawServiceRevenue, $serviceDiscount),
        ];
    }

    private function payments(SalesInvoice $sale, int $companyId): array
    {
        $payments = [];

        foreach ($sale->activePayments()->with('account')->get() as $payment) {
            if ((int) $payment->company_id !== $companyId) {
                throw new RuntimeException('An active sales payment belongs to another company.');
            }

            $account = $payment->account;

            if ($account === null || (int) $account->company_id !== $companyId) {
                throw new RuntimeException('A sales payment account could not be resolved for this company.');
            }

            $accountType = $this->requiredString($account->account_type, 'payment account_type');

            if (! in_array($accountType, ['Cash', 'Bank', 'ATM', 'Wallet'], true)) {
                throw new RuntimeException('A sales payment uses an unsupported operational account type.');
            }

            $amount = $this->amount($payment->paid_amount, 'sales payment amount');

            if ($this->isZero($amount)) {
                throw new RuntimeException('An active sales payment amount must be greater than zero.');
            }

            $payments[] = [
                'payment_id' => $this->positiveInteger($payment->id, 'payment_id'),
                'operational_account_id' => $this->positiveInteger($account->id, 'operational_account_id'),
                'account_type' => $accountType,
                'amount' => $amount,
            ];
        }

        return $payments;
    }

    private function date(mixed $value, string $field): string
    {
        if ($value instanceof DateTimeInterface) {
            $value = $value->format('Y-m-d');
        }

        if (! is_string($value) || trim($value) === '') {
            throw new InvalidArgumentException("The {$field} value must be a valid Y-m-d date.");
        }

        $value = trim($value);
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        $errors = DateTimeImmutable::getLastErrors();

        if (! $date || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) || $date->format('Y-m-d') !== $value) {
            throw new InvalidArgumentException("The {$field} value must be a valid Y-m-d date.");
        }

        return $date->format('Y-m-d');
    }

    private function requiredString(mixed $value, string $field): string
    {
        if (! is_string($value) || trim($value) === '') {
            throw new InvalidArgumentException("The {$field} value is required.");
        }

        return trim($value);
    }

    private function positiveInteger(mixed $value, string $field): int
    {
        if (filter_var($value, FILTER_VALIDATE_INT) === false || (int) $value < 1) {
            throw new InvalidArgumentException("The {$field} value must be a positive integer.");
        }

        return (int) $value;
    }

    private function nullablePositiveInteger(mixed $value, string $field): ?int
    {
        return $value === null ? null : $this->positiveInteger($value, $field);
    }

    private function amount(mixed $value, string $field): string
    {
        if (is_int($value)) {
            $value = (string) $value;
        }

        if (! is_string($value) || ! preg_match('/^\d+(?:\.\d{1,4})?$/', trim($value))) {
            throw new InvalidArgumentException("The {$field} value must be a non-negative decimal amount.");
        }

        [$whole, $fraction] = array_pad(explode('.', trim($value), 2), 2, '');

        return (ltrim($whole, '0') ?: '0') . '.' . str_pad($fraction, 4, '0');
    }

    private function isZero(string $amount): bool
    {
        return $this->scaled($amount) === '0';
    }

    private function equals(string $left, string $right): bool
    {
        return $this->scaled($left) === $this->scaled($right);
    }

    private function compare(string $left, string $right): int
    {
        return $this->compareUnsigned($this->scaled($left), $this->scaled($right));
    }

    private function add(string $left, string $right): string
    {
        return $this->decimal($this->addUnsigned($this->scaled($left), $this->scaled($right)));
    }

    private function subtract(string $left, string $right): string
    {
        $left = $this->scaled($left);
        $right = $this->scaled($right);

        if ($this->compareUnsigned($left, $right) < 0) {
            throw new RuntimeException('A sales accounting amount cannot become negative.');
        }

        return $this->decimal($this->subtractUnsigned($left, $right));
    }

    private function proportionalAmount(string $amount, string $portion, string $total): string
    {
        if ($this->isZero($amount) || $this->isZero($portion)) {
            return '0.0000';
        }

        return $this->decimal($this->divideUnsigned(
            $this->multiplyUnsigned($this->scaled($amount), $this->scaled($portion)),
            $this->scaled($total)
        ));
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

    private function compareUnsigned(string $left, string $right): int
    {
        $left = ltrim($left, '0') ?: '0';
        $right = ltrim($right, '0') ?: '0';

        return strlen($left) === strlen($right) ? $left <=> $right : strlen($left) <=> strlen($right);
    }

    private function addUnsigned(string $left, string $right): string
    {
        $carry = 0;
        $result = '';
        $leftIndex = strlen($left) - 1;
        $rightIndex = strlen($right) - 1;

        while ($leftIndex >= 0 || $rightIndex >= 0 || $carry > 0) {
            $sum = ($leftIndex >= 0 ? (int) $left[$leftIndex--] : 0)
                + ($rightIndex >= 0 ? (int) $right[$rightIndex--] : 0)
                + $carry;
            $result = ($sum % 10) . $result;
            $carry = intdiv($sum, 10);
        }

        return ltrim($result, '0') ?: '0';
    }

    private function subtractUnsigned(string $left, string $right): string
    {
        $borrow = 0;
        $result = '';
        $leftIndex = strlen($left) - 1;
        $rightIndex = strlen($right) - 1;

        while ($leftIndex >= 0) {
            $difference = (int) $left[$leftIndex--] - ($rightIndex >= 0 ? (int) $right[$rightIndex--] : 0) - $borrow;

            if ($difference < 0) {
                $difference += 10;
                $borrow = 1;
            } else {
                $borrow = 0;
            }

            $result = $difference . $result;
        }

        return ltrim($result, '0') ?: '0';
    }

    private function multiplyUnsigned(string $left, string $right): string
    {
        $result = '0';

        for ($index = strlen($right) - 1, $zeros = 0; $index >= 0; $index--, $zeros++) {
            $digit = (int) $right[$index];
            $carry = 0;
            $partial = '';

            for ($leftIndex = strlen($left) - 1; $leftIndex >= 0; $leftIndex--) {
                $value = ((int) $left[$leftIndex] * $digit) + $carry;
                $partial = ($value % 10) . $partial;
                $carry = intdiv($value, 10);
            }

            $partial = (string) $carry . $partial;
            $result = $this->addUnsigned($result, (ltrim($partial, '0') ?: '0') . str_repeat('0', $zeros));
        }

        return ltrim($result, '0') ?: '0';
    }

    private function divideUnsigned(string $dividend, string $divisor): string
    {
        if ($this->compareUnsigned($divisor, '0') === 0) {
            throw new RuntimeException('A sales accounting amount cannot be divided by zero.');
        }

        $quotient = '';
        $remainder = '0';

        foreach (str_split($dividend) as $digit) {
            $remainder = ltrim($remainder . $digit, '0') ?: '0';
            $digitQuotient = 0;

            while ($this->compareUnsigned($remainder, $divisor) >= 0) {
                $remainder = $this->subtractUnsigned($remainder, $divisor);
                $digitQuotient++;
            }

            $quotient .= (string) $digitQuotient;
        }

        return ltrim($quotient, '0') ?: '0';
    }
}
