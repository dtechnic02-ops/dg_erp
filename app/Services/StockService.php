<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;


class StockService
{
    // STOCK INCREASE

public static function increase(
    Product $product,
    $qty,
    $type,
    $reference = null,
    $financialYearId = null,
    $transactionDate = null,
    $unitPrice = 0,
    $note = null
)
{
    $qty = (float) $qty;

    if (!$financialYearId)
    {
        throw new \Exception(
    'Financial Year is required for stock transaction.'
);
    }

    $freshProduct = Product::where(
        'company_id',
        $product->company_id
    )->findOrFail(
        $product->id
    );

  

  

       $before =

$freshProduct->current_stock;

if($qty <= 0){

throw new \Exception(
'Invalid quantity'
);

}

$freshProduct->increment(

'current_stock',

$qty

);

        $freshProduct->refresh();

        $after =

            $freshProduct->current_stock;

        StockMovement::create([

            'company_id'   =>

                $freshProduct->company_id,

    'financial_year_id' => $financialYearId,

'transaction_date' => $transactionDate,

'product_id' => $freshProduct->id,

            'type'         =>

                $type,

            'quantity'     =>

                $qty,

            'before_stock' =>

                $before,

            'after_stock'  =>

                $after,

                'unit_price' =>
    $unitPrice,

    'note' =>
    $note,

            'reference_no' =>

                $reference,

            'created_by' =>
auth()->id() ?? 1,
                

        ]);




}

    // STOCK DECREASE

public static function decrease(
    Product $product,
    $qty,
    $type,
    $reference = null,
    $financialYearId = null,
     $transactionDate = null,
    $unitPrice = 0,
    $note = null
)
{
    $qty = (float) $qty;

    if (!$financialYearId)
    {
throw new \Exception(
    'Financial Year is required for stock transaction.'
);
    }

    $freshProduct = Product::where(
        'company_id',
        $product->company_id
    )->findOrFail(
        $product->id
    );




$before =
    $freshProduct->current_stock;

if (
    $qty <= 0
)
{
    throw new \Exception(
        'Invalid quantity'
    );
}

$after =
    $before - $qty;

if (
    $after < 0
)
{
    throw new \Exception(
        'Insufficient stock.'
    );
}




$freshProduct->decrement(

    'current_stock',

    $qty

);

$freshProduct->refresh();
StockMovement::create([

    'company_id' => $freshProduct->company_id,

   'financial_year_id' => $financialYearId,

'transaction_date' => $transactionDate,

'product_id' => $freshProduct->id,

    'type' =>

        $type,

    'quantity' =>

        -$qty,

    'before_stock' =>

        $before,

    'after_stock' =>

        $freshProduct->current_stock,

        'unit_price' =>

    $unitPrice,

'note' =>

    $note,

    'reference_no' =>

        $reference,

    'created_by' =>

        auth()->id() ?? 1,

]);




    }
public static function recalculateStock(
    int $productId
)
{
    $product = Product::findOrFail(
        $productId
    );

    $stock = StockMovement::where(
        'company_id',
        $product->company_id
    )
    ->where(
        'product_id',
        $productId
    )
    ->sum(
        'quantity'
    );

    $product->update([

        'current_stock' => $stock

    ]);
}
public static function recalculateAllStock(
    int $companyId
)
{
    Product::where(
        'company_id',
        $companyId
    )
    ->chunk(100, function ($products) {

        foreach ($products as $product)
        {
            self::recalculateStock(
                $product->id
            );
        }

    });
}

}


