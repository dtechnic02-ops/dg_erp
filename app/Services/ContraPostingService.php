<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\Contra;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ContraPostingService
{
    public function post(Contra $contra, int $companyId, int $userId): Contra
    {
        return DB::transaction(function () use ($contra, $companyId, $userId): Contra {
            $contra = $this->lockedContra($contra->id, $companyId);
            [$fromAccount, $toAccount] = $this->eligibleAccounts($contra, $companyId);

            if ($this->currentPostingQuery($contra, $companyId)->lockForUpdate()->exists()) {
                throw new RuntimeException('This Contra has already been posted to the account ledger.');
            }

            AccountBalanceService::createTransaction($this->transactionData($contra, $fromAccount->id, $userId, '0.00', $contra->amount));
            AccountBalanceService::createTransaction($this->transactionData($contra, $toAccount->id, $userId, $contra->amount, '0.00'));

            $contra->update(['transfer_type' => $this->transferType($fromAccount, $toAccount)]);

            return $contra->fresh();
        });
    }

    public function updatePosting(Contra $contra, int $companyId, int $userId, array $data): Contra
    {
        return DB::transaction(function () use ($contra, $companyId, $userId, $data): Contra {
            $contra = $this->lockedContra($contra->id, $companyId);
            if ((int) $contra->status !== 1) throw new RuntimeException('Cancelled Contra cannot be edited.');

            $transactions = $this->originalTransactions($contra, $companyId);
            $contra->fill($data);
            [$fromAccount, $toAccount] = $this->eligibleAccounts($contra, $companyId);
            $this->assertAvailableAfterRemovingOldEffects($fromAccount, $transactions, (float) $contra->amount);

            foreach ($transactions as $transaction) {
                AccountBalanceService::reverseTransaction(
                    $transaction,
                    'contra_update_reversal',
                    'Contra Update Reversal - '.$contra->contra_no,
                    $contra->contra_date,
                    $contra->financial_year_id
                );
            }

            $contra->transfer_type = $this->transferType($fromAccount, $toAccount);
            $contra->save();

            AccountBalanceService::createTransaction($this->transactionData($contra, $fromAccount->id, $userId, '0.00', $contra->amount));
            AccountBalanceService::createTransaction($this->transactionData($contra, $toAccount->id, $userId, $contra->amount, '0.00'));

            foreach ($transactions->pluck('account_id')->push($fromAccount->id)->push($toAccount->id)->unique() as $accountId) {
                AccountBalanceService::recalculateLedger((int) $accountId);
            }

            return $contra->fresh();
        });
    }

    public function reverse(Contra $contra, int $companyId, int $userId): Contra
    {
        return DB::transaction(function () use ($contra, $companyId, $userId): Contra {
            $contra = $this->lockedContra($contra->id, $companyId);
            if ((int) $contra->status !== 1) throw new RuntimeException('Contra is already cancelled.');

            $transactions = $this->originalTransactions($contra, $companyId);
            foreach ($transactions as $transaction) {
                AccountBalanceService::reverseTransaction(
                    $transaction,
                    'contra_cancel',
                    'Contra Cancel - '.$contra->contra_no,
                    $contra->contra_date,
                    $contra->financial_year_id
                );
            }

            $contra->update(['status' => 0]);

            return $contra->fresh();
        });
    }

    public function deriveTransferType(Account $fromAccount, Account $toAccount): string
    {
        return $this->transferType($fromAccount, $toAccount);
    }

    private function originalTransactions(Contra $contra, int $companyId)
    {
        $transactions = $this->currentPostingQuery($contra, $companyId)
            ->lockForUpdate()
            ->get();

        if ($transactions->count() !== 2) throw new RuntimeException('Contra must have exactly two active account ledger transactions.');

        return $transactions;
    }

    private function eligibleAccounts(Contra $contra, int $companyId): array
    {
        if ((int) $contra->from_account_id === (int) $contra->to_account_id) {
            throw new RuntimeException('From account and To account cannot be same.');
        }
        if ((float) $contra->amount <= 0) throw new RuntimeException('Contra amount must be greater than zero.');

        $accounts = Account::where('company_id', $companyId)
            ->whereIn('id', [$contra->from_account_id, $contra->to_account_id])
            ->whereIn('status', [1, 'active'])
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
        $fromAccount = $accounts->get((int) $contra->from_account_id);
        $toAccount = $accounts->get((int) $contra->to_account_id);
        if (! $fromAccount || ! $toAccount) throw new RuntimeException('Both Contra accounts must be active accounts belonging to this company.');

        $this->accountKind($fromAccount);
        $this->accountKind($toAccount);

        return [$fromAccount, $toAccount];
    }

    private function transferType(Account $fromAccount, Account $toAccount): string
    {
        return $this->accountKind($fromAccount).'_to_'.$this->accountKind($toAccount);
    }

    private function accountKind(Account $account): string
    {
        return match ($account->account_type) {
            'Cash' => 'cash',
            'Bank', 'ATM', 'Wallet' => 'bank',
            default => throw new RuntimeException('Contra supports only Cash, Bank, ATM, or Wallet accounts.'),
        };
    }

    private function transactionData(Contra $contra, int $accountId, int $userId, mixed $debit, mixed $credit): array
    {
        return [
            'company_id' => $contra->company_id,
            'financial_year_id' => $contra->financial_year_id,
            'account_id' => $accountId,
            'transaction_date' => $contra->contra_date,
            'voucher_no' => $contra->contra_no,
            'reference_type' => 'contra',
            'reference_id' => $contra->id,
            'description' => 'Contra Transfer - '.$contra->contra_no,
            'debit' => $debit,
            'credit' => $credit,
            'created_by' => $userId,
            'status' => 1,
            'allow_repost_after_reversal' => true,
        ];
    }

    private function currentPostingQuery(Contra $contra, int $companyId)
    {
        return AccountTransaction::where('company_id', $companyId)
            ->where('reference_type', 'contra')
            ->where('reference_id', $contra->id)
            ->where('status', 1)
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('account_transactions as reversal_rows')
                    ->whereColumn('reversal_rows.reversed_transaction_id', 'account_transactions.id')
                    ->where('reversal_rows.status', 1);
            });
    }

    private function assertAvailableAfterRemovingOldEffects(Account $account, $transactions, float $amount): void
    {
        $balanceWithoutOldContra = (float) $account->current_balance;
        foreach ($transactions->where('account_id', $account->id) as $transaction) {
            $balanceWithoutOldContra -= (float) $transaction->debit;
            $balanceWithoutOldContra += (float) $transaction->credit;
        }
        if ($balanceWithoutOldContra < $amount) throw new RuntimeException('Insufficient account balance.');
    }

    private function lockedContra(int $contraId, int $companyId): Contra
    {
        return Contra::where('company_id', $companyId)->lockForUpdate()->findOrFail($contraId);
    }
}
