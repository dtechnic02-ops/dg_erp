<?php

namespace App\Services\Accounting\Builders;

use App\Models\FinancialYear;
use App\Models\Supplier;
use App\Models\SupplierTransaction;
use DateTimeImmutable;
use InvalidArgumentException;
use RuntimeException;

class SupplierOpeningBalanceAccountingDataBuilder
{
    public function build(Supplier $supplier): array
    {
        if (! $supplier->exists) {
            throw new InvalidArgumentException('The supplier must be saved before opening balance accounting can be posted.');
        }

        $companyId = $this->positiveInteger($supplier->company_id, 'company_id');
        $supplierId = $this->positiveInteger($supplier->id, 'supplier_id');
        $amount = $this->amount($supplier->opening_balance, 'opening_balance');

        if ($amount === '0.0000') {
            throw new RuntimeException('A zero supplier opening balance must not be posted to accounting.');
        }

        $transactions = SupplierTransaction::query()
            ->where('company_id', $companyId)
            ->where('supplier_id', $supplierId)
            ->where('reference_type', 'opening_balance')
            ->where('reference_id', $supplierId)
            ->where('status', 1)
            ->get();

        if ($transactions->count() !== 1) {
            throw new RuntimeException('The supplier must have exactly one active opening balance transaction before accounting can be posted.');
        }

        $transaction = $transactions->first();
        $transactionDate = $this->date($transaction->getRawOriginal('transaction_date') ?? $transaction->transaction_date, 'transaction_date');
        $financialYearId = $this->positiveInteger($transaction->financial_year_id, 'financial_year_id');

        $financialYear = FinancialYear::query()
            ->where('company_id', $companyId)
            ->where('is_active', 1)
            ->find($financialYearId);

        if (! $financialYear || $transactionDate < $financialYear->start_date || $transactionDate > $financialYear->end_date) {
            throw new RuntimeException('The supplier opening balance transaction does not belong to the active company financial year.');
        }

        if (! $this->isZero($this->amount($transaction->debit, 'supplier transaction debit')) || ! $this->same($this->amount($transaction->credit, 'supplier transaction credit'), $amount)) {
            throw new RuntimeException('The supplier opening balance transaction does not match the persisted supplier opening balance.');
        }

        return [
            'company_id' => $companyId,
            'supplier_id' => $supplierId,
            'transaction_date' => $transactionDate,
            'reference_number' => (string) ($transaction->reference_no ?? ('OPEN-' . $supplierId)),
            'amount' => $amount,
            'created_by' => $this->nullablePositiveInteger($transaction->created_by ?? $supplier->created_by ?? null, 'created_by'),
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

    private function isZero(string $amount): bool
    {
        return $this->scaled($amount) === '0';
    }

    private function same(string $left, string $right): bool
    {
        return $this->scaled($left) === $this->scaled($right);
    }

    private function scaled(string $amount): string
    {
        [$whole, $fraction] = explode('.', $amount, 2);

        return ltrim($whole . $fraction, '0') ?: '0';
    }
}
