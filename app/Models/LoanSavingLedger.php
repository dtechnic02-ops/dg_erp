<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanSavingLedger extends Model
{

protected $fillable = [

'company_id',

'loan_account_id',

'loan_payment_id',

'account_id',

'type',

'amount',

'balance_after',

'date',

'attachment',

'note',

'created_by',

'status'

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

return $this->belongsTo(

User::class,

'created_by'

);

}

}