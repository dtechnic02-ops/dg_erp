<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $fillable = [

        'company_id',

        'account_type',

        'bank_name',

        'account_name',

        'branch',

        'account_no',

        'iban',

        'swift_code',

        'currency',

        'opening_balance',

        'current_balance',

        'image_path',

        'note',

        'status',
    ];


    /**
     * COMPANY
     */
    public function company()
    {
        return $this->belongsTo(
            Company::class
        );
    }


    /**
     * PAYMENTS
     */
    public function payments()
    {
        return $this->hasMany(
            InvoicePayment::class
        );
    }
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
public function journalItems()
{

return $this->hasMany(

JournalItem::class

);

}
public function transactions()
{
    return $this->hasMany(
        AccountTransaction::class,
        'account_id'
    );
}

}
