<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesItem extends Model
{
    use HasFactory;

    protected $fillable = [

        'created_by',

        'company_id',

        'financial_year_id',

        'sales_invoice_id',

        'item_type',

        'product_id',

        'service_id',

        'quantity',

        'returned_qty',

        'unit_price',

        'vat_rate',

        'vat_amount',

        'total_price',

    ];

    protected $casts = [
        'quantity'     => 'decimal:2',
        'returned_qty' => 'decimal:2',
        'unit_price'   => 'decimal:2',
        'vat_rate'     => 'decimal:2',
        'vat_amount'   => 'decimal:2',
        'total_price'  => 'decimal:2',
    ];

    public function invoice()
    {
        return $this->belongsTo(
            SalesInvoice::class,
            'sales_invoice_id'
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
}
