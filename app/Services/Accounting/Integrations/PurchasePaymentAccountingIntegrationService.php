<?php

namespace App\Services\Accounting\Integrations;

use App\Models\AccountingEntry;
use App\Models\PurchaseInvoice;
use App\Models\PurchasePayment;
use App\Services\Accounting\AccountingPostingService;
use App\Services\Accounting\Builders\PurchasePaymentAccountingDataBuilder;
use App\Services\Accounting\Profiles\PurchasePaymentPostingProfile;
use InvalidArgumentException;
use RuntimeException;

class PurchasePaymentAccountingIntegrationService
{
    public function __construct(
        private readonly PurchasePaymentAccountingDataBuilder $builder,
        private readonly PurchasePaymentPostingProfile $profile,
        private readonly AccountingPostingService $postingService
    ) {
    }

    public function postPayment(PurchasePayment $payment): void
    {
        if (! $payment->exists) {
            throw new InvalidArgumentException('The purchase payment must be saved before accounting can be posted.');
        }

        $this->postingService->post(
            $this->profile->build($this->builder->build($payment))
        );
    }

    public function reversePayment(PurchasePayment $payment, string $date, ?int $postedBy = null): void
    {
        if (! $payment->exists) {
            throw new InvalidArgumentException('The purchase payment must be saved before accounting can be reversed.');
        }

        $data = $this->builder->buildForReversal($payment);
        $original = AccountingEntry::query()
            ->forCompany((int) $payment->company_id)
            ->where('source_key', 'purchase_payment:' . $payment->id . ':created')
            ->whereIn('source_type', ['purchase_payment', PurchasePayment::class])
            ->where('source_id', $payment->id)
            ->where('source_event', 'created')
            ->with('lines.chartAccount')
            ->lockForUpdate()
            ->first();

        if ($original !== null) {
            $this->validateIndependentPaymentEntry($original, $data);

            $this->postingService->reverseBySource([
                'company_id' => $payment->company_id,
                'financial_year_id' => $payment->financial_year_id,
                'entry_date' => $date,
                'original_source_key' => 'purchase_payment:' . $payment->id . ':created',
                'original_source_event' => 'created',
                'original_source_types' => [PurchasePayment::class],
                'reversal_source_key' => 'purchase_payment_cancel:' . $payment->id . ':cancelled',
                'source_module' => 'purchase_payment',
                'source_type' => 'purchase_payment',
                'source_id' => $payment->id,
                'source_event' => 'cancelled',
                'reference_number' => $payment->payment_no,
                'description' => 'Purchase payment cancellation - ' . $payment->payment_no,
                'posted_by' => $postedBy,
            ]);

            return;
        }

        $this->postLegacyEmbeddedCancellation($payment, $data, $date, $postedBy);
    }

    private function validateIndependentPaymentEntry(AccountingEntry $entry, array $data): void
    {
        if ($entry->status !== 'posted') {
            throw new RuntimeException('The original posted accounting entry could not be resolved for reversal.');
        }

        if (($entry->financial_year_id !== null && (int) $entry->financial_year_id !== (int) $data['financial_year_id'])
            || $entry->lines->count() !== 2) {
            throw new RuntimeException('The original purchase payment accounting entry does not match the persisted payment.');
        }

        $this->assertSettlementLines($entry, $data, false);
    }

    private function postLegacyEmbeddedCancellation(PurchasePayment $payment, array $data, string $date, ?int $postedBy): void
    {
        if ($payment->payment_method !== 'invoice') {
            throw new RuntimeException('The original posted accounting entry could not be resolved for reversal.');
        }

        $purchaseEntry = AccountingEntry::query()
            ->forCompany((int) $data['company_id'])
            ->where('financial_year_id', $data['financial_year_id'])
            ->where('source_key', 'purchase:' . $payment->purchase_invoice_id . ':created')
            ->whereIn('source_type', ['purchase', PurchaseInvoice::class])
            ->where('source_id', $payment->purchase_invoice_id)
            ->where('source_event', 'created')
            ->where('status', 'posted')
            ->with('lines.chartAccount')
            ->lockForUpdate()
            ->first();

        if ($purchaseEntry === null
            || $purchaseEntry->reference_number !== $payment->invoice->invoice_no) {
            throw new RuntimeException('The legacy embedded purchase payment could not be verified safely.');
        }

        $debitTotal = '0.0000';
        $creditTotal = '0.0000';

        foreach ($purchaseEntry->lines as $line) {
            $debitTotal = $this->addAmounts($debitTotal, $line->debit);
            $creditTotal = $this->addAmounts($creditTotal, $line->credit);
        }

        if (! $this->sameAmount($debitTotal, $creditTotal)
            || ! $this->sameAmount($debitTotal, $payment->invoice->grand_total)) {
            throw new RuntimeException('The legacy embedded purchase payment could not be verified safely.');
        }

        $this->assertSettlementLines($purchaseEntry, $data, true);

        $posting = $this->profile->build($data);
        $posting['entry_date'] = $date;
        $posting['source_event'] = 'cancelled';
        $posting['source_key'] = 'purchase_payment_cancel:' . $payment->id . ':cancelled';
        $posting['description'] = 'Legacy embedded purchase payment cancellation - ' . $payment->payment_no;
        $posting['posted_by'] = $postedBy;
        $posting['lines'] = array_map(static fn (array $line): array => array_merge($line, [
            'debit' => $line['credit'],
            'credit' => $line['debit'],
            'description' => 'Cancellation: ' . $line['description'],
        ]), $posting['lines']);

        $this->postingService->post($posting);
    }

    private function assertSettlementLines(AccountingEntry $entry, array $data, bool $embedded): void
    {
        $operationalCode = $data['account_type'] === 'Cash' ? 'CASH_IN_HAND' : 'BANK_ACCOUNTS';
        $amount = $data['amount'];

        $cashLines = $entry->lines->filter(fn ($line): bool =>
            $line->chartAccount?->system_code === $operationalCode
            && (int) $line->operational_account_id === (int) $data['account_id']
            && $this->sameAmount($line->debit, '0.0000')
            && $this->sameAmount($line->credit, $amount)
        );

        if ($cashLines->count() !== 1) {
            throw new RuntimeException($embedded
                ? 'The legacy embedded purchase payment could not be verified safely.'
                : 'The original purchase payment accounting entry does not match the persisted payment.');
        }

        if ($embedded) {
            return;
        }

        $payableLines = $entry->lines->filter(fn ($line): bool =>
            $line->chartAccount?->system_code === 'ACCOUNTS_PAYABLE'
            && $line->operational_account_id === null
            && $line->subledger_type === 'supplier'
            && (int) $line->subledger_id === (int) $data['supplier_id']
            && $this->sameAmount($line->debit, $amount)
            && $this->sameAmount($line->credit, '0.0000')
        );

        if ($payableLines->count() !== 1) {
            throw new RuntimeException('The original purchase payment accounting entry does not match the persisted payment.');
        }
    }

    private function sameAmount(mixed $left, mixed $right): bool
    {
        return $this->normalizedAmount($left) === $this->normalizedAmount($right);
    }

    private function normalizedAmount(mixed $value): string
    {
        if (is_int($value) || is_float($value)) {
            $value = number_format($value, 4, '.', '');
        }

        if (! is_string($value) || ! preg_match('/^-?\d+(?:\.\d{1,4})?$/', trim($value))) {
            throw new RuntimeException('An accounting amount could not be validated exactly.');
        }

        $negative = str_starts_with($value = trim($value), '-');
        $value = ltrim($value, '-');
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');
        $normalized = (ltrim($whole, '0') ?: '0') . '.' . str_pad($fraction, 4, '0');

        return $negative && $normalized !== '0.0000' ? '-' . $normalized : $normalized;
    }

    private function addAmounts(mixed $left, mixed $right): string
    {
        $left = str_replace('.', '', $this->normalizedAmount($left));
        $right = str_replace('.', '', $this->normalizedAmount($right));
        $carry = 0;
        $sum = '';

        while ($left !== '' || $right !== '' || $carry > 0) {
            $digitSum = ($left === '' ? 0 : (int) substr($left, -1))
                + ($right === '' ? 0 : (int) substr($right, -1))
                + $carry;
            $sum = ($digitSum % 10) . $sum;
            $carry = intdiv($digitSum, 10);
            $left = substr($left, 0, -1);
            $right = substr($right, 0, -1);
        }

        $sum = str_pad(ltrim($sum, '0') ?: '0', 5, '0', STR_PAD_LEFT);

        return substr($sum, 0, -4) . '.' . substr($sum, -4);
    }
}
