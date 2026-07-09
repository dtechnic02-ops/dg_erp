<?php

namespace App\Models;
use App\Models\PurchaseReturnRefund;
use Illuminate\Database\Eloquent\Model;
use App\Models\FinancialYear;
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

    'created_by',

    'status',

];
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
     * 🔥 PURCHASE INVOICE
     */

    public function purchaseInvoice()
    {
        return $this->belongsTo(
            PurchaseInvoice::class,
            'purchase_invoice_id'
        );
    }

    /**
     * 🔥 RETURN ITEMS
     */

    public function items()
    {
        return $this->hasMany(
            PurchaseReturnItem::class
        );
    }
    /**
 * 🔥 REFUNDS
 */

     /**
 * 🔥 REFUNDS
 */

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
/**
 * 🔥 CREATED BY
 */

public function createdBy()
{
    return $this->belongsTo(
        User::class,
        'created_by'
    );
}
}