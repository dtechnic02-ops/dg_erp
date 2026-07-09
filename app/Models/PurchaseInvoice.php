<?php

namespace App\Models;


use App\Models\Company;
use App\Models\Supplier;
use App\Models\User;
use App\Models\FinancialYear;
use App\Models\PurchaseItem;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseInvoice extends Model
{
    use HasFactory;

    protected $fillable = [

'created_by',

'company_id',

'financial_year_id',

'supplier_id',

'invoice_no',

'purchase_date',

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

    // SUPPLIER

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    // PURCHASE ITEMS

    public function items()
    {
        return $this->hasMany(
            PurchaseItem::class,
            'purchase_invoice_id'
        );
    }

    // COMPANY

    public function company()
{
    return $this->belongsTo(Company::class, 'company_id');
}

    // CREATOR

    public function creator()
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }
    public function financialYear()
{

return $this->belongsTo(

FinancialYear::class,

'financial_year_id'

);

}
}