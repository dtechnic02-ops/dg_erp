<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchasePayment extends Model
{
    public const STATUS_CANCELLED = 0;

    public const STATUS_ACTIVE = 1;

    protected $fillable = [
        'company_id',
        'financial_year_id',
        'purchase_invoice_id',
        'supplier_id',
        'account_id',
        'payment_no',
        'payment_date',
        'amount',
        'payment_method',
        'reference_no',
        'receipt_file',
        'note',
        'created_by',
        'updated_by',
        'deleted_by',
        'status',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount'       => 'decimal:2',
        'status'       => 'integer',
    ];

    public function invoice()
    {
        return $this->belongsTo(
            PurchaseInvoice::class,
            'purchase_invoice_id'
        );
    }

    public function purchaseInvoice()
    {
        return $this->invoice();
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function financialYear()
    {
        return $this->belongsTo(FinancialYear::class);
    }

    public function creator()
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
}
