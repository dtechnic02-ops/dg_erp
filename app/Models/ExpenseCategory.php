<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpenseCategory extends Model
{

protected $fillable = [

'company_id',

'name',

'description',

'status'

];

public function expenses()
{

return $this->hasMany(
Expense::class
);

}

}