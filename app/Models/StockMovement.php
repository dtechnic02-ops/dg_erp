<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class StockMovement extends Model

{
    use HasFactory;

   protected $fillable = [

    'company_id',
    'financial_year_id',
    'transaction_date',
    'product_id',
    'type',
    'quantity',
    'before_stock',
    'after_stock',
    'unit_price',
    'reference_no',
    'note',
    'created_by',

];
public function product()
{
    return $this->belongsTo(Product::class);
}


    //
}
