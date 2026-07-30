<?php

namespace App\Services\Accounting\Builders;

use App\Models\PurchaseInvoice;
use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;
use RuntimeException;

class PurchaseAccountingDataBuilder
{
    public function build(PurchaseInvoice $purchase): array
    {
        if (! $purchase->exists) {
            throw new InvalidArgumentException('The purchase invoice must be saved before accounting can be posted.');
        }

        if ((int) $purchase->status !== 1) {
            throw new RuntimeException('Only active purchase invoices can be posted to accounting.');
        }

        $companyId = $this->positiveInteger($purchase->company_id, 'company_id');
        $purchaseId = $this->positiveInteger($purchase->id, 'purchase_id');
        $purchaseDate = $this->date($purchase->purchase_date, 'purchase_date');
        $purchaseNumber = $this->requiredString($purchase->invoice_no, 'invoice_no');
        $supplierId = $this->nullablePositiveInteger($purchase->supplier_id, 'supplier_id');
        $createdBy = $this->nullablePositiveInteger($purchase->created_by, 'created_by');

        $grandTotal = $this->amount($purchase->grand_total, 'grand_total');
        $taxAmount = $this->amount($purchase->total_vat, 'total_vat');
        $discountAmount = $this->amount($purchase->discount, 'discount');
        $paidAmount = $this->amount($purchase->paid_amount, 'paid_amount');
        $dueAmount = $this->amount($purchase->due_amount, 'due_amount');

        if ($this->isZero($grandTotal)) {
            throw new RuntimeException('The purchase invoice grand total must be greater than zero.');
        }

        if (! $this->equals($this->add($paidAmount, $dueAmount), $grandTotal)) {
            throw new RuntimeException('The persisted paid and due amounts must equal the purchase invoice grand total.');
        }

        if ($this->compare($taxAmount, $grandTotal) > 0) {
            throw new RuntimeException('The persisted tax amount cannot exceed the purchase invoice grand total.');
        }

        $purchaseAmount = $this->subtract($grandTotal, $taxAmount);
        [$rawProductPurchaseAmount, $rawServicePurchaseAmount] = $this->itemAmounts($purchase);
        $itemAmount = $this->add($rawProductPurchaseAmount, $rawServicePurchaseAmount);

        if ($this->compare($itemAmount, $discountAmount) < 0 || ! $this->equals(
            $this->subtract($itemAmount, $discountAmount),
            $purchaseAmount
        )) {
            throw new RuntimeException('The persisted purchase item totals do not reconcile with the purchase amount and discount.');
        }

        if (! $this->isZero($dueAmount) && $supplierId === null) {
            throw new RuntimeException('Supplier information is required for a due purchase amount.');
        }

        [$productPurchaseAmount, $servicePurchaseAmount] = $this->reconcileDiscount(
            $rawProductPurchaseAmount,
            $rawServicePurchaseAmount,
            $purchaseAmount,
            $discountAmount
        );

        $payments = $this->payments($purchase, $companyId);
        $paymentTotal = '0.0000';

        foreach ($payments as $payment) {
            $paymentTotal = $this->add($paymentTotal, $payment['amount']);
        }

        if (! $this->equals($paymentTotal, $paidAmount)) {
            throw new RuntimeException('Active purchase payments must equal the persisted paid amount.');
        }

        return [
            'company_id' => $companyId,
            'purchase_id' => $purchaseId,
            'purchase_date' => $purchaseDate,
            'purchase_number' => $purchaseNumber,
            'supplier_id' => $supplierId,
            'created_by' => $createdBy,
            'status' => $purchase->status,
            'totals' => [
                'purchase_amount' => $purchaseAmount,
                'product_purchase_amount' => $productPurchaseAmount,
                'service_purchase_amount' => $servicePurchaseAmount,
                'tax_amount' => $taxAmount,
                'grand_total' => $grandTotal,
                'paid_amount' => $paidAmount,
                'due_amount' => $dueAmount,
                'discount_amount' => $discountAmount,
            ],
            'payments' => $payments,
        ];
    }

    private function itemAmounts(PurchaseInvoice $purchase): array
    {
        $productPurchaseAmount = '0.0000';
        $servicePurchaseAmount = '0.0000';

        foreach ($purchase->items()->get() as $item) {
            $total = $this->amount($item->total_price, 'purchase item total_price');
            $vat = $this->amount($item->vat_amount, 'purchase item vat_amount');

            if ($this->compare($vat, $total) > 0) {
                throw new RuntimeException('A purchase item tax amount cannot exceed its total price.');
            }

            $purchaseAmount = $this->subtract($total, $vat);

            if ($item->item_type === 'product') {
                $productPurchaseAmount = $this->add($productPurchaseAmount, $purchaseAmount);
                continue;
            }

            if ($item->item_type === 'service') {
                $servicePurchaseAmount = $this->add($servicePurchaseAmount, $purchaseAmount);
                continue;
            }

            throw new RuntimeException('A persisted purchase item has an unsupported item type.');
        }

        return [$productPurchaseAmount, $servicePurchaseAmount];
    }

    private function reconcileDiscount(
        string $rawProductPurchaseAmount,
        string $rawServicePurchaseAmount,
        string $purchaseAmount,
        string $discountAmount
    ): array {
        $rawPurchaseAmount = $this->add($rawProductPurchaseAmount, $rawServicePurchaseAmount);

        if ($this->equals($rawPurchaseAmount, $purchaseAmount)) {
            if (! $this->isZero($discountAmount)) {
                throw new RuntimeException('The persisted purchase item totals do not reconcile with the purchase invoice discount.');
            }

            return [$rawProductPurchaseAmount, $rawServicePurchaseAmount];
        }

        if ($this->compare($rawPurchaseAmount, $discountAmount) < 0 || ! $this->equals(
            $this->subtract($rawPurchaseAmount, $discountAmount),
            $purchaseAmount
        )) {
            throw new RuntimeException('The persisted purchase item totals do not reconcile with the purchase amount and discount.');
        }

        $productDiscount = $this->proportionalAmount(
            $discountAmount,
            $rawProductPurchaseAmount,
            $rawPurchaseAmount
        );
        $serviceDiscount = $this->subtract($discountAmount, $productDiscount);

        return [
            $this->subtract($rawProductPurchaseAmount, $productDiscount),
            $this->subtract($rawServicePurchaseAmount, $serviceDiscount),
        ];
    }

    private function payments(PurchaseInvoice $purchase, int $companyId): array
    {
        $payments = [];

        foreach ($purchase->activePayments()->with('account')->get() as $payment) {
            if ((int) $payment->company_id !== $companyId) {
                throw new RuntimeException('An active purchase payment belongs to another company.');
            }

            $account = $payment->account;

            if ($account === null || (int) $account->company_id !== $companyId) {
                throw new RuntimeException('A purchase payment account could not be resolved for this company.');
            }

            $accountType = $this->requiredString($account->account_type, 'payment account_type');

            if (! in_array($accountType, ['Cash', 'Bank', 'ATM', 'Wallet'], true)) {
                throw new RuntimeException('A purchase payment uses an unsupported operational account type.');
            }

            $amount = $this->amount($payment->amount, 'purchase payment amount');

            if ($this->isZero($amount)) {
                throw new RuntimeException('An active purchase payment amount must be greater than zero.');
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
            throw new RuntimeException('A purchase accounting amount cannot become negative.');
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
            throw new RuntimeException('A purchase accounting amount cannot be divided by zero.');
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
