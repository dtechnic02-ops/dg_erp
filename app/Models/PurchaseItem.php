<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    use HasFactory;

    public const ITEM_TYPE_PRODUCT = 'product';

    public const ITEM_TYPE_SERVICE = 'service';

    protected $fillable = [
        'created_by',
        'company_id',
        'financial_year_id',
        'purchase_invoice_id',
        'item_type',
        'product_id',
        'service_id',
        'quantity',
        'returned_qty',
        'price',
        'unit_price',
        'total',
        'total_price',
        'vat_id',
        'vat_rate',
        'vat_amount',
        'status',
    ];

    protected $casts = [
        'quantity'     => 'decimal:2',
        'returned_qty' => 'decimal:2',
        'unit_price'   => 'decimal:2',
        'price'        => 'decimal:2',
        'total'        => 'decimal:2',
        'vat_rate'     => 'decimal:2',
        'vat_amount'   => 'decimal:2',
        'total_price'  => 'decimal:2',
    ];

    public function invoice()
    {
        return $this->belongsTo(
            PurchaseInvoice::class,
            'purchase_invoice_id'
        );
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function vat()
    {
        return $this->belongsTo(Vat::class);
    }
}
