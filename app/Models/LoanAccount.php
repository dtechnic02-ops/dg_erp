<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanAccount extends Model
{

protected $fillable = [

'company_id',

'loan_no',

'loan_name',

'loan_type',

'party_account_id',

'account_id',

'principal_amount',

'interest_rate',

'remaining_principal',

'start_date',

'end_date',

'next_payment_date',

'attachment',

'note',

'created_by',

'status'

];

/**
* PARTY ACCOUNT
*/

public function partyAccount()
{

return $this->belongsTo(

PartyAccount::class,

'party_account_id'

);

}

/**
* RECEIVE / PAYMENT ACCOUNT
*/

public function account()
{

return $this->belongsTo(

Account::class

);

}

/**
* PAYMENTS
*/

public function payments()
{

return $this->hasMany(

LoanPayment::class

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
public function savingLedgers()
{

return $this->hasMany(

LoanSavingLedger::class

);

}

}