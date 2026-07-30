<?php

namespace App\Services\Accounting\Builders;

use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\FinancialYear;
use App\Models\PurchaseInvoice;
use App\Models\PurchasePayment;
use App\Models\SupplierTransaction;
use DateTimeImmutable;
use InvalidArgumentException;
use RuntimeException;

class PurchasePaymentAccountingDataBuilder
{
    public function build(PurchasePayment $payment): array
    {
        if (! $payment->exists || (int) $payment->status !== PurchasePayment::STATUS_ACTIVE) {
            throw new RuntimeException('Only an active persisted purchase payment can be posted to accounting.');
        }

        $companyId = $this->positiveInteger($payment->company_id, 'company_id');
        $paymentId = $this->positiveInteger($payment->id, 'payment_id');
        $financialYearId = $this->positiveInteger($payment->financial_year_id, 'financial_year_id');
        $supplierId = $this->positiveInteger($payment->supplier_id, 'supplier_id');
        $paymentDate = $this->date($payment->getRawOriginal('payment_date') ?? $payment->payment_date, 'payment_date');
        $amount = $this->amount($payment->amount, 'amount');

        if ($this->isZero($amount)) {
            throw new RuntimeException('The persisted purchase payment amount must be greater than zero.');
        }

        $financialYear = FinancialYear::query()
            ->where('company_id', $companyId)
            ->find($financialYearId);

        if (! $financialYear || $paymentDate < $financialYear->start_date || $paymentDate > $financialYear->end_date) {
            throw new RuntimeException('The purchase payment date does not belong to its company financial year.');
        }

        $invoice = PurchaseInvoice::query()
            ->where('company_id', $companyId)
            ->find($this->positiveInteger($payment->purchase_invoice_id, 'purchase_invoice_id'));

        if (! $invoice || (int) $invoice->status !== 1 || (int) $invoice->financial_year_id !== $financialYearId || (int) $invoice->supplier_id !== $supplierId) {
            throw new RuntimeException('The purchase payment, invoice, supplier, company, and financial year must belong together.');
        }

        $accountTransactions = AccountTransaction::query()
            ->where('company_id', $companyId)
            ->where('reference_type', 'purchase_payment')
            ->where('reference_id', $paymentId)
            ->where('status', 1)
            ->get();

        if ($accountTransactions->count() !== 1) {
            throw new RuntimeException('The purchase payment must have exactly one active account transaction.');
        }

        $accountTransaction = $accountTransactions->first();
        $accountId = $this->positiveInteger($accountTransaction->account_id, 'account transaction account_id');

        if ((int) $payment->account_id !== $accountId || (int) $accountTransaction->financial_year_id !== $financialYearId || ! $this->isZero($this->amount($accountTransaction->debit, 'account transaction debit')) || ! $this->same($this->amount($accountTransaction->credit, 'account transaction credit'), $amount)) {
            throw new RuntimeException('The purchase payment account transaction does not match the persisted payment.');
        }

        $account = Account::query()
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->find($accountId);

        if (! $account || ! in_array($account->account_type, ['Cash', 'Bank', 'ATM', 'Wallet'], true)) {
            throw new RuntimeException('The purchase payment operational account is invalid.');
        }

        $supplierTransactions = SupplierTransaction::query()
            ->where('company_id', $companyId)
            ->where('reference_type', 'purchase_payment')
            ->where('reference_id', $paymentId)
            ->where('status', 1)
            ->get();

        if ($supplierTransactions->count() !== 1) {
            throw new RuntimeException('The purchase payment must have exactly one active supplier transaction.');
        }

        $supplierTransaction = $supplierTransactions->first();

        if ((int) $supplierTransaction->supplier_id !== $supplierId || (int) $supplierTransaction->financial_year_id !== $financialYearId || ! $this->same($this->amount($supplierTransaction->debit, 'supplier transaction debit'), $amount) || ! $this->isZero($this->amount($supplierTransaction->credit, 'supplier transaction credit'))) {
            throw new RuntimeException('The purchase payment supplier transaction does not match the persisted payment.');
        }

        return [
            'company_id' => $companyId,
            'payment_id' => $paymentId,
            'payment_date' => $paymentDate,
            'payment_number' => $this->requiredString($payment->payment_no, 'payment_no'),
            'supplier_id' => $supplierId,
            'account_id' => $accountId,
            'account_type' => $account->account_type,
            'amount' => $amount,
            'created_by' => $this->nullablePositiveInteger($payment->created_by, 'created_by'),
        ];
    }

    private function requiredString(mixed $value, string $key): string
    {
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

        $value = trim($value);
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');

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
