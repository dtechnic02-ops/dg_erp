<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseReturnRefundAdjustment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'purchase_return_refund_id',
        'purchase_invoice_id',
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
            PurchaseReturnRefund::class,
            'purchase_return_refund_id'
        );
    }

    public function invoice()
    {
        return $this->belongsTo(
            PurchaseInvoice::class,
            'purchase_invoice_id'
        );
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
