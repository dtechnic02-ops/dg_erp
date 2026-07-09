<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartyAccount extends Model
{

protected $fillable = [

'company_id',

'account_no',

'name',

'phone',

'address',

'opening_balance',

'current_balance',

'type',

'photo',

'id_card',

'document',

'note',

'created_by',

'status'

];

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

/**
* Loan Accounts
*/

public function loanAccounts()
{

return $this->hasMany(

LoanAccount::class,

'party_account_id'

);

}

}