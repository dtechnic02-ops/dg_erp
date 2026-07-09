<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockTransaction extends Model
{
    protected $fillable = [
        'company_id',
        'product_id',
        'type',
        'quantity',
        'price',
        'reference',
        'note'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}