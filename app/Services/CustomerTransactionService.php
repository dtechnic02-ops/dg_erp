<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerTransaction;

class CustomerTransactionService
{
    public static function createTransaction(
        array $data
    )
    {
        $data['balance'] = $data['balance'] ?? 0;

        $transaction = CustomerTransaction::create(
            $data
        );

        self::recalculateCustomer(
            $data['customer_id']
        );

        return $transaction;
    }

    public static function recalculateCustomer(
        int $customerId
    )
    {
        $customer = Customer::findOrFail(
            $customerId
        );

        $balance = 0;
$transactions = CustomerTransaction::where(
    'company_id',
    $customer->company_id
)
->where(
    'customer_id',
    $customerId
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

        foreach ($transactions as $transaction)
        {
            $balance += $transaction->debit;

            $balance -= $transaction->credit;

            $transaction->update([

    'balance' => $balance,

]);
        }

        $customer->update([

            'current_balance' => $balance

        ]);
    }

    public static function recalculateAllCustomers(
        int $companyId
    )
    {
        Customer::where(
            'company_id',
            $companyId
        )
        ->chunk(100, function ($customers) {

            foreach ($customers as $customer)
            {
                self::recalculateCustomer(
                    $customer->id
                );
            }

        });
    }

    
    
    public static function reverseTransaction(
    CustomerTransaction $transaction,
    string $referenceType,
    string $description,
    ?string $transactionDate = null,
    ?int $financialYearId = null,
    ?string $remarks = null
)
{
    return self::createTransaction([

        'company_id' =>

            $transaction->company_id,

        'customer_id' =>

            $transaction->customer_id,

        'financial_year_id' =>

            $financialYearId ?? $transaction->financial_year_id,

        'transaction_date' =>

            $transactionDate ?? now()->toDateString(),

        'voucher_no' =>

            'REV-' .
            $transaction->voucher_no,

        'reference_type' =>

            $referenceType,

        'reference_id' =>

            $transaction->reference_id,

        'reference_no' =>

            $transaction->reference_no,

        'description' =>

            $description,

        'debit' =>

            $transaction->credit,

        'credit' =>

            $transaction->debit,

        'remarks' =>

            $remarks ?? 'Reverse Entry',

        'created_by' =>

            auth()->id(),

        'status' => 1,

    ]);
}

    /*
    |--------------------------------------------------------------------------
    | REVERSE BY REFERENCE (cancellation)
    |--------------------------------------------------------------------------
    */

    public static function deleteByReference(
        int $companyId,
        string $referenceType,
        int $referenceId,
        ?string $transactionDate = null,
        ?int $financialYearId = null,
        ?string $cancelReason = null
    ): void
    {
        $transactions = CustomerTransaction::where(
            'company_id',
            $companyId
        )
        ->where(
            'reference_type',
            $referenceType
        )
        ->where(
            'reference_id',
            $referenceId
        )
        ->where(
            'status',
            1
        )
        ->get();

        if ($transactions->isEmpty()) {

            return;

        }

        $cancelReferenceType = $referenceType . '_cancel';

        $description = match ($referenceType) {

            'sales_invoice' => 'Sales Invoice Cancel' . ($cancelReason ? ': ' . $cancelReason : ''),

            'sales_payment' => 'Sales Payment Cancel' . ($cancelReason ? ': ' . $cancelReason : ''),

            default => 'Transaction Cancel' . ($cancelReason ? ': ' . $cancelReason : ''),

        };

        foreach ($transactions as $transaction) {

            self::reverseTransaction(
                $transaction,
                $cancelReferenceType,
                $description,
                $transactionDate,
                $financialYearId,
                $cancelReason
            );

        }
    }
}