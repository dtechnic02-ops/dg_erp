<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountTransaction;

class AccountBalanceService
{
    /*
    |--------------------------------------------------------------------------
    | CREATE TRANSACTION
    |--------------------------------------------------------------------------
    */
    public static function createTransaction(
    array $data,
    bool $checkBalance = true
)
    {
        $companyId = (int) ($data['company_id'] ?? 0);
        if ($companyId < 1) {
            throw new \InvalidArgumentException('company_id is required for an account transaction.');
        }

        $account = Account::where('company_id', $companyId)
            ->lockForUpdate()
            ->findOrFail($data['account_id']);

        if (! empty($data['reference_type']) && ! empty($data['reference_id']) && AccountTransaction::where('company_id', $companyId)
            ->where('reference_type', $data['reference_type'])
            ->where('reference_id', $data['reference_id'])
            ->where('account_id', $account->id)
            ->when(array_key_exists('journal_item_id', $data), fn ($query) => $query->where('journal_item_id', $data['journal_item_id']))
            ->where('status', 1)
            ->lockForUpdate()
            ->exists()) {
            throw new \RuntimeException('An active account transaction already exists for this source.');
        }
 
if ($checkBalance)
{
    $availableBalance =
        $account->current_balance;

    if (
        $availableBalance
        +
        $data['debit']
        -
        $data['credit']
        < 0
    )
    {
        throw new \Exception(
            'Insufficient account balance.'
        );
    }
}

        $transaction =
            AccountTransaction::create([

                'company_id' =>
                $data['company_id'],

                'financial_year_id' =>
                $data['financial_year_id'],

                'account_id' =>
                $data['account_id'],

                'transaction_date' =>
                $data['transaction_date'],

                'voucher_no' =>
                $data['voucher_no'],

                'reference_type' =>
                $data['reference_type'],

                'reference_id' =>
                $data['reference_id'],

                'journal_item_id' =>
                $data['journal_item_id'] ?? null,

                'reversed_transaction_id' =>
                $data['reversed_transaction_id'] ?? null,

                'description' =>
                $data['description'] ?? null,

                'debit' =>
                $data['debit'],

                'credit' =>
                $data['credit'],

               'balance' => 0,

             'created_by' =>
$data['created_by']
?? auth()->id(),

                'status' => 1

            ]);

       self::recalculateLedger(
    $account->id
);

return $transaction;
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE TRANSACTION
    |--------------------------------------------------------------------------
    */
    public static function deleteTransaction(
        AccountTransaction $transaction
    )
    {
        $transaction->update([

    'status' => 0

]);

self::recalculateLedger(

    $transaction->account_id

);
    }
/*
|--------------------------------------------------------------------------
| UPDATE TRANSACTION
|--------------------------------------------------------------------------
*/
public static function updateTransaction(
    AccountTransaction $transaction,
    array $data
)
{
    $oldAccountId =
        $transaction->account_id;

    $transaction->update([

        'company_id' =>
            $data['company_id'],

        'financial_year_id' =>
            $data['financial_year_id'],

        'account_id' =>
            $data['account_id'],

        'transaction_date' =>
            $data['transaction_date'],

        'voucher_no' =>
            $data['voucher_no'],

        'reference_type' =>
            $data['reference_type'],

        'reference_id' =>
            $data['reference_id'],

        'description' =>
            $data['description'] ?? null,

        'debit' =>
            $data['debit'],

        'credit' =>
            $data['credit'],

    ]);

    // पुरानो Account पनि Recalculate
    if (
        $oldAccountId !=
        $data['account_id']
    )
    {
        self::recalculateLedger(
            $oldAccountId
        );
    }

    // नयाँ Account पनि Recalculate
    self::recalculateLedger(
        $data['account_id']
    );
}
  /* 
|--------------------------------------------------------------------------
| REVERSE TRANSACTION
|--------------------------------------------------------------------------
*/
public static function reverseTransaction(
    AccountTransaction $transaction,
    string $referenceType,
    string $description,
    ?string $transactionDate = null,
    ?int $financialYearId = null
)
{
    $transaction = AccountTransaction::where('company_id', $transaction->company_id)
        ->whereKey($transaction->id)
        ->lockForUpdate()
        ->firstOrFail();

    if ((int) $transaction->status !== 1 || $transaction->reversed_transaction_id !== null) {
        throw new \RuntimeException('Only an active original account transaction may be reversed.');
    }

    if (AccountTransaction::where('company_id', $transaction->company_id)
        ->where('reversed_transaction_id', $transaction->id)
        ->where('status', 1)
        ->lockForUpdate()
        ->exists()) {
        throw new \RuntimeException('This account transaction has already been reversed.');
    }

    return self::createTransaction([

        'company_id' =>
            $transaction->company_id,

        'financial_year_id' =>
            $financialYearId ?? $transaction->financial_year_id,

        'account_id' =>
            $transaction->account_id,

        'transaction_date' =>
            $transactionDate ?? now()->toDateString(),

        'voucher_no' =>
            'REV-' .
            $transaction->voucher_no,

        'reference_type' =>
            $referenceType,

        'reference_id' =>
            $transaction->reference_id,

        'reversed_transaction_id' =>
            $transaction->id,

        'description' =>
            $description,

        'debit' =>
            $transaction->credit,

        'credit' =>
            $transaction->debit,

        'created_by' =>
            auth()->id(),

    ], false);
}
public static function recalculateLedger(
    int $accountId
)
{  
    $account = Account::lockForUpdate()->findOrFail($accountId);

    $transactions = AccountTransaction::where(
        'company_id',
        $account->company_id
    )
    ->where(
        'account_id',
        $accountId
    )

    ->where(
        'status',
        1
    )
    ->orderBy(
        'transaction_date'
    )
    ->orderBy(
        'id'
    )
    ->get();


    $balance = 0;

    foreach ($transactions as $transaction)
    {
        $balance +=
            $transaction->debit;

        $balance -=
            $transaction->credit;

$transaction->update([

    'balance' => $balance

]);
    }

    $account->update([

        'current_balance' => $balance

    ]);
}

/*
|--------------------------------------------------------------------------
| RECALCULATE ALL LEDGERS
|--------------------------------------------------------------------------
*/
public static function recalculateAllLedger(
    int $companyId
)
{
    Account::where(
        'company_id',
        $companyId
    )
    ->chunk(100, function ($accounts) {

        foreach ($accounts as $account)
        {

            self::recalculateLedger(
                $account->id
            );
        }

    });
}



}
