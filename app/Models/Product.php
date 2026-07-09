<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class Product extends Model
{
   protected $fillable = [
    'company_id',
    'category_id',
    'unit_id',
    'name',
    'barcode',
    'cost_price',
    'retail_price',
    'wholesale_price',
    'stock_alert',
    
    'current_stock',
    'description',
    'image',
    'status'
];

    public function stock()
{
    $in = $this->stockTransactions()->where('type', 'in')->sum('quantity');
    $out = $this->stockTransactions()->where('type', 'out')->sum('quantity');

    return $in - $out;
}

public function stockTransactions()
{
    return $this->hasMany(StockTransaction::class);
}
public function vat()
{
    return $this->belongsTo(Vat::class);
}
public function unit()
{
    return $this->belongsTo(Unit::class);
}
public function stockMovements()
{
    return $this->hasMany(StockMovement::class);
}
}
