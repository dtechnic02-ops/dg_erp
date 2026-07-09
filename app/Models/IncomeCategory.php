<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncomeCategory extends Model
{

protected $fillable=[

'company_id',

'name',

'code',

'note',

'created_by',

'status'

];

}