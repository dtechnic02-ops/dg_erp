<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\Account;
use App\Models\FinancialYear;

class Income extends Model
{

protected $fillable=[

'company_id',

'financial_year_id',

'income_no',

'title',

'account_id',

'amount',

'income_date',

'category',

'attachment',

'note',

'created_by',

'status'

];

public function account()
{

return $this->belongsTo(

Account::class

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
