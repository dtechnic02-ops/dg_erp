<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanPayment extends Model
{

protected $fillable = [

'company_id',

'loan_account_id',

'account_id',

'payment_date',

'principal_amount',

'interest_amount',

'fine_amount',

'saving_amount',

'total_amount',

'remaining_principal',

'reference_no',

'attachment',

'note',

'created_by',

'status'

];

/**
* Loan Account
*/

public function loanAccount()
{

return $this->belongsTo(

LoanAccount::class

);

}

/**
* Account
*/

public function account()
{

return $this->belongsTo(

Account::class

);

}

/**
* Company
*/

public function company()
{

return $this->belongsTo(

Company::class

);

}

/**
* Creator
*/

public function creator()
{

return $this->belongsTo(

User::class,

'created_by'

);

}
public function savingLedger()
{

return $this->hasOne(

LoanSavingLedger::class

);

}

}