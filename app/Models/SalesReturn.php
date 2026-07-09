<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Customer;
use App\Models\SalesInvoice;
use App\Models\SalesReturnItem;

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

    'note',

    'damage_photo',

    'created_by',

    'status',

];
    /**
 * 🔥 CUSTOMER
 */

             public function customer()
     {
    return $this->belongsTo(
        Customer::class
    );
       }
    /**
 * 🔥 SALES INVOICE
 */

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



/**
 * RETURN ITEMS
 */

public function items()
{

return $this->hasMany(

SalesReturnItem::class,

'sales_return_id'

);

}
public function financialYear()
{
    return $this->belongsTo(
        FinancialYear::class
    );
}




}