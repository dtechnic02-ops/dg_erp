<?php

namespace App\Services\Accounting\Integrations;

use App\Models\Account;
use App\Models\LoanAccount;
use App\Models\LoanPayment;
use App\Models\LoanSavingLedger;
use App\Services\Accounting\AccountingPostingService;
use App\Services\Money;
use RuntimeException;

class LoanAccountingIntegrationService
{
    public function __construct(private readonly AccountingPostingService $postingService) {}

    public function postLoanCreation(LoanAccount $loan): void
    {
        $amount = Money::normalize($loan->principal_amount);
        $cash = $this->cashLine($loan->account, $amount, $loan->loan_type === LoanAccount::TYPE_TAKEN);
        $control = $this->line(
            $loan->loan_type === LoanAccount::TYPE_TAKEN ? 'LOAN_PAYABLE' : 'LOAN_RECEIVABLE',
            $amount,
            $loan->loan_type === LoanAccount::TYPE_GIVEN,
            'loan',
            $loan->id
        );

        $this->post($loan->company_id, $loan->financial_year_id, $loan->start_date->format('Y-m-d'), 'loan_account', $loan->id, LoanAccount::EVENT_CREATED, $loan->loan_no, [$cash, $control], $loan->created_by);
    }

    public function postPayment(LoanPayment $payment): void
    {
        $payment->loadMissing('loanAccount', 'account', 'savingLedgers');
        $loan = $payment->loanAccount;

        if (! $loan || ! $loan->isActive()) {
            throw new RuntimeException('Only an active company Loan can be posted.');
        }

        $lines = [];
        $fromSaving = $payment->isPaidFromSaving();

        if ($fromSaving && $loan->loan_type !== LoanAccount::TYPE_TAKEN) {
            throw new RuntimeException('Saving-funded settlement is available only for Loan Taken.');
        }
        if (! Money::isZero($payment->principal_amount)) {
            $lines[] = $this->line($loan->loan_type === LoanAccount::TYPE_TAKEN ? 'LOAN_PAYABLE' : 'LOAN_RECEIVABLE', $payment->principal_amount, $loan->loan_type === LoanAccount::TYPE_TAKEN, 'loan', $loan->id);
        }
        if (! Money::isZero($payment->interest_amount)) {
            $lines[] = $this->line($loan->loan_type === LoanAccount::TYPE_TAKEN ? 'LOAN_INTEREST_EXPENSE' : 'LOAN_INTEREST_INCOME', $payment->interest_amount, $loan->loan_type === LoanAccount::TYPE_TAKEN, 'loan', $loan->id);
        }
        if (! Money::isZero($payment->fine_amount)) {
            $lines[] = $this->line($loan->loan_type === LoanAccount::TYPE_TAKEN ? 'LOAN_FINE_EXPENSE' : 'LOAN_FINE_INCOME', $payment->fine_amount, $loan->loan_type === LoanAccount::TYPE_TAKEN, 'loan', $loan->id);
        }
        if (! Money::isZero($payment->saving_amount)) {
            if ($loan->loan_type !== LoanAccount::TYPE_TAKEN) {
                throw new RuntimeException('Saving is prohibited for Loan Given.');
            }
            $lines[] = $this->line('LOAN_COMPULSORY_SAVING_ASSET', $payment->saving_amount, true, 'loan', $loan->id);
        }

        $lines[] = $fromSaving
            ? $this->line('LOAN_COMPULSORY_SAVING_ASSET', $payment->total_amount, false, 'loan', $loan->id)
            : $this->cashLine($payment->account, $payment->total_amount, $loan->loan_type === LoanAccount::TYPE_GIVEN);

        $this->post($payment->company_id, $payment->financial_year_id, $payment->payment_date->format('Y-m-d'), 'loan_payment', $payment->id, LoanPayment::EVENT_CREATED, $payment->reference_no, $lines, $payment->created_by);
    }

    public function postSavingWithdrawal(LoanSavingLedger $ledger): void
    {
        $ledger->loadMissing('loanAccount', 'account');
        if (! $ledger->loanAccount || $ledger->loanAccount->loan_type !== LoanAccount::TYPE_TAKEN) {
            throw new RuntimeException('Compulsory saving withdrawal is available only for Loan Taken.');
        }
        $lines = [
            $this->cashLine($ledger->account, $ledger->amount, true),
            $this->line('LOAN_COMPULSORY_SAVING_ASSET', $ledger->amount, false, 'loan', $ledger->loan_account_id),
        ];
        $this->post($ledger->company_id, $ledger->financial_year_id, $ledger->date->format('Y-m-d'), 'loan_saving_withdrawal', $ledger->id, LoanSavingLedger::EVENT_WITHDRAWN, 'LSW-' . $ledger->id, $lines, $ledger->created_by);
    }

    public function reverse(string $sourceType, int $sourceId, int $companyId, int $financialYearId, string $date, string $originalEvent, string $reference, ?int $postedBy): void
    {
        $this->postingService->reverseBySource([
            'company_id' => $companyId,
            'financial_year_id' => $financialYearId,
            'entry_date' => $date,
            'original_source_key' => $sourceType . ':' . $sourceId . ':' . $originalEvent,
            'original_source_event' => $originalEvent,
            'reversal_source_key' => $sourceType . ':' . $sourceId . ':cancelled',
            'source_module' => 'loan',
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'source_event' => 'cancelled',
            'reference_number' => $reference,
            'description' => 'Loan reversal - ' . $reference,
            'posted_by' => $postedBy,
        ]);
    }

    private function post(int $companyId, int $financialYearId, string $date, string $type, int $id, string $event, string $reference, array $lines, ?int $postedBy): void
    {
        $this->postingService->post([
            'company_id' => $companyId,
            'financial_year_id' => $financialYearId,
            'entry_date' => $date,
            'reference_number' => $reference,
            'source_module' => 'loan',
            'source_type' => $type,
            'source_id' => $id,
            'source_event' => $event,
            'source_key' => $type . ':' . $id . ':' . $event,
            'description' => 'Loan transaction - ' . $reference,
            'posted_by' => $postedBy,
            'lines' => $lines,
        ]);
    }

    private function cashLine(?Account $account, mixed $amount, bool $debit): array
    {
        if (! $account) {
            throw new RuntimeException('A company Cash/Bank account is required.');
        }
        $code = $account->account_type === 'Cash' ? 'CASH_IN_HAND' : 'BANK_ACCOUNTS';
        return $this->line($code, $amount, $debit, null, null, $account->id);
    }

    private function line(string $code, mixed $amount, bool $debit, ?string $subledgerType = null, ?int $subledgerId = null, ?int $operationalAccountId = null): array
    {
        $amount = Money::normalize($amount) . '00';
        return [
            'chart_account_system_code' => $code,
            'operational_account_id' => $operationalAccountId,
            'description' => str_replace('_', ' ', $code),
            'debit' => $debit ? $amount : '0.0000',
            'credit' => $debit ? '0.0000' : $amount,
            'subledger_type' => $subledgerType,
            'subledger_id' => $subledgerId,
        ];
    }
}
