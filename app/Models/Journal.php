<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\FinancialYear;
class Journal extends Model
{

protected $fillable = [

'company_id',

'financial_year_id',

'journal_no',

'journal_date',

'total_amount',

'attachment',

'note',

'created_by',

'status'

];


public function items()
{

return $this->hasMany(

JournalItem::class

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