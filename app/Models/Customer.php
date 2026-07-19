<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
class Customer extends Model
{
    protected $fillable = [
        'company_id',
         'created_by',
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
        'credit_days',
        'current_balance',
        'bank_name',
        'bank_account_no',
        'note',
        'image_path',
        'status'
    ];
    /**
 * SALES RETURN REFUNDS
 */

public function salesReturnRefunds()
{
    return $this->hasMany(
        SalesReturnRefund::class
    );
}
/**
 * SALES PAYMENTS
 */

public function salesPayments()
{
    return $this->hasMany(
        SalesPayment::class
    );
} 


public function creator()
{
    return $this->belongsTo(

        User::class,

        'created_by'

    );
} 
}