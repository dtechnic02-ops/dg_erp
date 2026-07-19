<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesReturnRefundAdjustment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'sales_return_refund_id',
        'sales_invoice_id',
        'adjust_amount',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'adjust_amount' => 'decimal:2',
        'status'        => 'integer',
    ];

    public function refund()
    {
        return $this->belongsTo(
            SalesReturnRefund::class,
            'sales_return_refund_id'
        );
    }

    public function invoice()
    {
        return $this->belongsTo(
            SalesInvoice::class,
            'sales_invoice_id'
        );
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
