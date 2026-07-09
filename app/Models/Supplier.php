<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturn;
class Supplier extends Model
{
    protected $fillable = [

    'company_id',

    'name',
    'authority_name',

    'mobile',
    'telephone',
    'fax_no',

    'email',
    'website',

    'address',

    'tax_no',

    'opening_balance',

    'current_balance',

    'bank_name',
    'bank_account_no',

    'note',

    'image_path',

    'status',

];
public function purchaseInvoices()
{
    return $this->hasMany(
        PurchaseInvoice::class,
        'supplier_id'
    );
}

public function purchaseReturns()
{
    return $this->hasMany(
        PurchaseReturn::class,
        'supplier_id'
    );
}
public function transactions()
{
    return $this->hasMany(
        SupplierTransaction::class
    )
    ->orderBy(
        'transaction_date'
    )
    ->orderBy(
        'id'
    );
}


}