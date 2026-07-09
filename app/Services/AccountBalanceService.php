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
        $account = Account::findOrFail(
            $data['account_id']
           
        );
 
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
    string $description
)
{

    return self::createTransaction([

        'company_id' =>
            $transaction->company_id,

        'financial_year_id' =>
            $transaction->financial_year_id,

        'account_id' =>
            $transaction->account_id,

        'transaction_date' =>
            now()->toDateString(),

        'voucher_no' =>
            'REV-' .
            $transaction->voucher_no,

        'reference_type' =>
            $referenceType,

        'reference_id' =>
            $transaction->reference_id,

        'description' =>
            $description,

        'debit' =>
            $transaction->credit,

        'credit' =>
            $transaction->debit,

        'created_by' =>
            auth()->id(),

    ]);
}
public static function recalculateLedger(
    int $accountId
)
{  
    $account = Account::findOrFail(
        $accountId
    );

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


