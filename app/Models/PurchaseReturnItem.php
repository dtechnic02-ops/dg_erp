<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Company;

class PurchaseReturnItem extends Model
{
    protected $fillable = [

    'created_by',

    'company_id',

    'financial_year_id',
    
     'purchase_return_id',
     'purchase_item_id',

    'purchase_invoice_id',

     'product_id',

    'service_id',

    'vat_id',

    'quantity',

   

    'unit_price',

    'total',

    'total_price',

    'vat_rate',

    'vat_amount',

    'status',

];

    /**
     * 🔥 PRODUCT
     */

    public function product()
    {
        return $this->belongsTo(
            Product::class
        );
    }

    public function service()
    {
        return $this->belongsTo(
            Service::class,
            'service_id'
        );
    }

    /**
     * 🔥 PURCHASE RETURN
     */

    public function purchaseReturn()
    {
        return $this->belongsTo(
            PurchaseReturn::class
        );
    }

    /**
     * 🔥 COMPANY
     */

    public function company()
    {
        return $this->belongsTo(
            Company::class
        );
    }
    public function financialYear()
{
    return $this->belongsTo(
        FinancialYear::class
    );
    
}
  
public function purchaseItem()
{
    return $this->belongsTo(
        PurchaseItem::class
    );
}

}