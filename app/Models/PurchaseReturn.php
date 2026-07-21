<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseReturn extends Model
{
    protected $fillable = [
        'company_id',
        'financial_year_id',
        'purchase_invoice_id',
        'supplier_id',
        'return_no',
        'return_date',
        'subtotal',
        'total_vat',
        'grand_total',
        'refund_amount',
        'adjust_amount',
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

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseInvoice()
    {
        return $this->belongsTo(
            PurchaseInvoice::class,
            'purchase_invoice_id'
        );
    }

    public function invoice()
    {
        return $this->purchaseInvoice();
    }

    public function items()
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }

    public function refunds()
    {
        return $this->hasMany(
            PurchaseReturnRefund::class,
            'purchase_return_id'
        );
    }

    public function financialYear()
    {
        return $this->belongsTo(
            FinancialYear::class,
            'financial_year_id'
        );
    }

    public function createdBy()
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
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
