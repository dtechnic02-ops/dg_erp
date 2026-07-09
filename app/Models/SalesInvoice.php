<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesInvoice extends Model
{
    use HasFactory;

    protected $fillable = [

        'created_by',
        'company_id',
        'financial_year_id',
        'customer_id',

        'invoice_no',

        'sale_date',

        'subtotal',
        'discount',
        'total_vat',
        'grand_total',

        'paid_amount',
        'due_amount',

        'payment_status',

        'note',

        'status',

    ];
    

    // CUSTOMER

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    // ITEMS

    public function items()
    {
        return $this->hasMany(
            SalesItem::class,
            'sales_invoice_id'
        );
    }

    // COMPANY

    public function company()
    {
        return $this->belongsTo(
            Company::class,
            'company_id'
        );
    }

    // CREATOR

    public function creator()
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }
    // PAYMENTS
public function salesInvoice()
{
    return $this->belongsTo(
        SalesInvoice::class
    );
}
    public function financialYear()
{
    return $this->belongsTo(
        FinancialYear::class
    );
    
}



public function payments()
{
    return $this->hasMany(

        SalesPayment::class,

        'sales_invoice_id'

    );
}

   

}
