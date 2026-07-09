<?php

namespace App\Services;

use App\Models\Supplier;
use App\Models\SupplierTransaction;

class SupplierTransactionService
{
    /*
    |--------------------------------------------------------------------------
    | CREATE TRANSACTION
    |--------------------------------------------------------------------------
    */

    public static function createTransaction(array $data)
    {
        //
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE SUPPLIER BALANCE
    |--------------------------------------------------------------------------
    */

    public static function updateSupplierBalance(
        int $supplierId
    )
    {
        //
    }

    /*
    |--------------------------------------------------------------------------
    | RECALCULATE ONE SUPPLIER
    |--------------------------------------------------------------------------
    */

    public static function recalculateSupplier(
        int $supplierId
    )
    {
        //
    }

    /*
    |--------------------------------------------------------------------------
    | RECALCULATE ALL SUPPLIERS
    |--------------------------------------------------------------------------
    */

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
}