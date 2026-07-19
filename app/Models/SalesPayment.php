<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesPayment extends Model
{
    public const STATUS_CANCELLED = 0;

    public const STATUS_ACTIVE = 1;

    protected $fillable = [

        'company_id',
        'financial_year_id',
        'sales_invoice_id',
        'customer_id',
        'account_id',
        'payment_no',
        'payment_date',
        'paid_amount',
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
        'paid_amount' => 'decimal:2',
        'status' => 'integer',

    ];

    public function salesInvoice()
    {
        return $this->belongsTo(
            SalesInvoice::class
        );
    }

    public function customer()
    {
        return $this->belongsTo(
            Customer::class
        );
    }

    public function account()
    {
        return $this->belongsTo(
            Account::class
        );
    }

    public function financialYear()
    {
        return $this->belongsTo(
            FinancialYear::class
        );
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
