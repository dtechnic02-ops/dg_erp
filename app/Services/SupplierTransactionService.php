<?php

namespace App\Services;

use App\Models\Supplier;
use App\Models\SupplierTransaction;

class SupplierTransactionService
{
   public static function createTransaction(
    array $data
)
{
    $data['balance'] = $data['balance'] ?? 0;

    $transaction = SupplierTransaction::create(
        $data
    );

    self::recalculateSupplier(
        $data['supplier_id']
    );

    return $transaction;
}

public static function recalculateSupplier(
    int $supplierId
)
{
    $supplier = Supplier::findOrFail(
        $supplierId
    );

    $balance = 0;

    $transactions = SupplierTransaction::where(
        'company_id',
        $supplier->company_id
    )
    ->where(
        'supplier_id',
        $supplierId
    )
    ->orderBy('transaction_date')
    ->orderBy('id')
    ->where(
    'status',
    1
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

    $supplier->update([
        'current_balance' => $balance
    ]);
}


   
  


    public static function recalculateAllSuppliers(
        int $companyId
    )
    {
        Supplier::where(
            'company_id',
            $companyId
        )
        ->chunk(100, function ($suppliers) {

            foreach ($suppliers as $supplier)
            {
                self::recalculateSupplier(
                    $supplier->id
                );
            }

        });
    }

public static function reverseTransaction(
    SupplierTransaction $transaction,
    string $referenceType,
    string $description
)
{
    return self::createTransaction([

        'company_id' =>

            $transaction->company_id,

        'supplier_id' =>

            $transaction->supplier_id,

        'financial_year_id' =>

            $transaction->financial_year_id,

        'transaction_date' =>

            now()->toDateString(),

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

            'Reverse Entry',

        'created_by' =>

            auth()->id(),

        'status' => 1,

    ]);
}


}