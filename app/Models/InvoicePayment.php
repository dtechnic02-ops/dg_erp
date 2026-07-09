<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoicePayment extends Model
{
    protected $fillable = [

        'company_id',

        'sales_invoice_id',

        'customer_id',

        'account_id',

        'payment_date',

        'amount',

        'note',

        'created_by',
        
    ];


    public function invoice()
    {
        return $this->belongsTo(
            SalesInvoice::class,
            'sales_invoice_id'
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
}