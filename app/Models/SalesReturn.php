<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesReturn extends Model
{
    protected $fillable = [
        'company_id',
        'financial_year_id',
        'sales_invoice_id',
        'customer_id',
        'return_no',
        'return_date',
        'subtotal',
        'total_vat',
        'grand_total',
        'adjust_amount',
        'refund_amount',
        'note',
        'damage_photo',
        'created_by',
        'status',
    ];

    protected $casts = [
        'return_date'   => 'date',
        'subtotal'      => 'decimal:2',
        'total_vat'     => 'decimal:2',
        'grand_total'   => 'decimal:2',
        'adjust_amount' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'status'        => 'integer',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoice()
    {
        return $this->belongsTo(
            SalesInvoice::class,
            'sales_invoice_id'
        );
    }

    public function refunds()
    {
        return $this->hasMany(
            SalesReturnRefund::class,
            'sales_return_id'
        );
    }

    public function items()
    {
        return $this->hasMany(
            SalesReturnItem::class,
            'sales_return_id'
        );
    }

    public function financialYear()
    {
        return $this->belongsTo(FinancialYear::class);
    }

    public function getRefundedAmountAttribute(): float
    {
        return (float) ($this->attributes['adjust_amount'] ?? 0);
    }

    public function getRemainingAmountAttribute(): float
    {
        return (float) ($this->attributes['refund_amount'] ?? 0);
    }

    public function getRefundStatusAttribute(): string
    {
        $refunded = (float) $this->refunded_amount;
        $remaining = (float) $this->remaining_amount;

        if ($refunded <= 0) {
            return 'Unpaid';
        }

        if ($remaining <= 0) {
            return 'Paid';
        }

        return 'Partial';
    }
}
