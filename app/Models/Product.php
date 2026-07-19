<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class Product extends Model
{
   protected $fillable = [
    'company_id',
    'category_id',
    'brand_id',
    'vat_id',
    'unit_id',
    'name',
    'barcode',
    'batch_no',
    'manufacture_date',
    'expiry_date',
    'allow_online',
    'cost_price',
    'retail_price',
    'wholesale_price',
    'stock_alert',

    'current_stock',
    'description',
    'image',
    'status'
];

    protected $casts = [
        'manufacture_date' => 'date',
        'expiry_date' => 'date',
        'allow_online' => 'boolean',
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
public function category()
{
    return $this->belongsTo(ProductCategory::class, 'category_id');
}
public function brand()
{
    return $this->belongsTo(Brand::class, 'brand_id');
}
public function stockMovements()
{
    return $this->hasMany(StockMovement::class);
}
}
