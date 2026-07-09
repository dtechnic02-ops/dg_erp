<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesPayment extends Model
{
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

        'note',

        'created_by',

        'status',

    ];

    /**
     * SALES INVOICE
     */

    public function salesInvoice()
    {
        return $this->belongsTo(
            SalesInvoice::class
        );
    }

    /**
     * CUSTOMER
     */

    public function customer()
    {
        return $this->belongsTo(
            Customer::class
        );
    }

    /**
     * ACCOUNT
     */

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
}