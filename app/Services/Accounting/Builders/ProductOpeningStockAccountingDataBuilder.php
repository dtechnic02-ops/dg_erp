<?php

namespace App\Services\Accounting\Builders;

use App\Models\FinancialYear;
use App\Models\Product;
use App\Models\StockMovement;
use DateTimeImmutable;
use InvalidArgumentException;
use RuntimeException;

class ProductOpeningStockAccountingDataBuilder
{
    public function build(Product $product): array
    {
        if (! $product->exists) {
            throw new InvalidArgumentException('The product must be saved before opening stock accounting can be posted.');
        }

        $companyId = $this->positiveInteger($product->company_id, 'company_id');
        $productId = $this->positiveInteger($product->id, 'product_id');

        $movements = StockMovement::query()
            ->where('company_id', $companyId)
            ->where('product_id', $productId)
            ->where('type', 'opening_stock')
            ->where('reference_no', 'OPENING')
            ->get();

        if ($movements->count() !== 1) {
            throw new RuntimeException('The product must have exactly one opening stock movement before accounting can be posted.');
        }

        $movement = $movements->first();
        $quantity = $this->positiveInteger($movement->quantity, 'opening stock quantity');
        $unitPrice = $this->amount($movement->unit_price, 'opening stock unit_price');
        $transactionDate = $this->date($movement->getRawOriginal('transaction_date') ?? $movement->transaction_date, 'transaction_date');
        $financialYearId = $this->positiveInteger($movement->financial_year_id, 'financial_year_id');

        if ($unitPrice === '0.0000') {
            throw new RuntimeException('A zero-value product opening stock movement must not be posted to accounting.');
        }

        $financialYear = FinancialYear::query()
            ->where('company_id', $companyId)
            ->where('is_active', 1)
            ->find($financialYearId);

        if (! $financialYear || $transactionDate < $financialYear->start_date || $transactionDate > $financialYear->end_date) {
            throw new RuntimeException('The product opening stock movement does not belong to the active company financial year.');
        }

        return [
            'company_id' => $companyId,
            'product_id' => $productId,
            'transaction_date' => $transactionDate,
            'reference_number' => 'OPENING-' . $productId,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'amount' => $this->multiplyByInteger($unitPrice, $quantity),
            'created_by' => $this->nullablePositiveInteger($movement->created_by, 'created_by'),
        ];
    }

    private function positiveInteger(mixed $value, string $key): int
    {
        if (filter_var($value, FILTER_VALIDATE_INT) === false || (int) $value < 1) {
            throw new InvalidArgumentException("The {$key} value must be a positive integer.");
        }

        return (int) $value;
    }

    private function nullablePositiveInteger(mixed $value, string $key): ?int
    {
        return $value === null ? null : $this->positiveInteger($value, $key);
    }

    private function date(mixed $value, string $key): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (! is_string($value) || trim($value) === '') {
            throw new InvalidArgumentException("The {$key} value is required.");
        }

        $value = trim($value);
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        $errors = DateTimeImmutable::getLastErrors();

        if (! $date || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) || $date->format('Y-m-d') !== $value) {
            throw new InvalidArgumentException("The {$key} value must be a valid Y-m-d date.");
        }

        return $date->format('Y-m-d');
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

    private function multiplyByInteger(string $amount, int $multiplier): string
    {
        [$whole, $fraction] = explode('.', $amount, 2);
        $scaled = ltrim($whole . $fraction, '0') ?: '0';
        $carry = 0;
        $result = '';

        for ($index = strlen($scaled) - 1; $index >= 0; $index--) {
            $product = ((int) $scaled[$index] * $multiplier) + $carry;
            $result = ($product % 10) . $result;
            $carry = intdiv($product, 10);
        }

        while ($carry > 0) {
            $result = ($carry % 10) . $result;
            $carry = intdiv($carry, 10);
        }

        $result = str_pad(ltrim($result, '0') ?: '0', 5, '0', STR_PAD_LEFT);

        return substr($result, 0, -4) . '.' . substr($result, -4);
    }
}
