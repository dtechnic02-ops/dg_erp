<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchasePayment extends Model
{
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

    'status',

];

    /**
     * 🔥 PURCHASE INVOICE
     */

    public function invoice()
    {
        return $this->belongsTo(
            PurchaseInvoice::class,
            'purchase_invoice_id'
        );
    }

    /**
     * 🔥 SUPPLIER
     */

    public function supplier()
    {
        return $this->belongsTo(
            Supplier::class
        );
    }
     /**
     * 🔥Account
     */
    public function account()
{
    return $this->belongsTo(
        Account::class
    );
}
}