<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    use HasFactory;

   protected $fillable = [

    'created_by',

    'company_id',

    'financial_year_id',

    'purchase_invoice_id',

    'product_id',

    'vat_id',

    'quantity',

    'price',

    'unit_price',

    'total',

    'total_price',

    'vat_rate',

    'vat_amount',

    'status',

];
    public function invoice()
    {
        return $this->belongsTo(PurchaseInvoice::class, 'purchase_invoice_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function vat()
    {
        return $this->belongsTo(Vat::class);
    }

}