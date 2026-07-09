<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JournalItem extends Model
{

protected $fillable=[

'company_id',

'journal_id',

'account_id',

'type',

'amount',

'note',

'status'

];

public function journal()
{

return $this->belongsTo(

Journal::class

);

}

public function account()
{

return $this->belongsTo(

Account::class

);

}

/*
Company Relation
*/

public function company()
{

return $this->belongsTo(

Company::class

);

}

}