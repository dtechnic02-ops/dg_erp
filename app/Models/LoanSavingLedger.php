<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanSavingLedger extends Model
{

public const STATUS_INACTIVE = 0;

public const STATUS_ACTIVE = 1;

public const TYPE_DEPOSIT = 'deposit';
public const TYPE_WITHDRAW = 'withdraw';
public const TYPE_LOAN_SETTLEMENT = 'loan_settlement';
public const TYPE_REVERSAL = 'reversal';
public const EVENT_WITHDRAWN = 'withdrawn';

protected $fillable = [

'company_id',

'financial_year_id',

'loan_account_id',

'loan_payment_id',

'request_key',

'account_id',

'type',

'amount',

'balance_after',

'date',

'attachment',

'note',

'created_by',

'updated_by',

'cancelled_by',

'cancelled_date',

'cancel_reason',

'status'

];

protected $casts = [
    'date' => 'date',
    'amount' => 'decimal:2',
    'balance_after' => 'decimal:2',
    'status' => 'integer',
    'cancelled_date' => 'date',
];

/**
* LOAN ACCOUNT
*/

public function loanAccount()
{

return $this->belongsTo(

LoanAccount::class

);

}

public function financialYear()
{
    return $this->belongsTo(FinancialYear::class, 'financial_year_id');
}

/**
* LOAN PAYMENT
*/

public function loanPayment()
{

return $this->belongsTo(

LoanPayment::class

);

}

/**
* ACCOUNT
*/

public function account()
{

return $this->belongsTo(

Account::class

);

}

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
* CREATOR
*/

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
}
