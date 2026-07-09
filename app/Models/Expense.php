<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{

protected $fillable = [

'company_id',
'financial_year_id',
'expense_no',

'expense_category_id',

'account_id',

'expense_date',

'amount',

'reference_no',

'note',

'attachment',

'created_by',

'status'

];

public function category()
{

return $this->belongsTo(
ExpenseCategory::class,
'expense_category_id'
);

}

public function account()
{

return $this->belongsTo(
Account::class
);

}

}