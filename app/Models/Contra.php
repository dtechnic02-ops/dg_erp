<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contra extends Model
{

protected $fillable = [

    'company_id',

    'financial_year_id',

    'contra_no',

    'contra_date',

    'from_account_id',

    'to_account_id',

    'amount',

    'transfer_type',

    'reference_no',

    'note',

    'attachment',

    'created_by',

    'status'

];
public function fromAccount()
{

    return $this->belongsTo(

        Account::class,

        'from_account_id'

    );

}

public function toAccount()
{

    return $this->belongsTo(

        Account::class,

        'to_account_id'

    );

}

public function financialYear()
{

    return $this->belongsTo(

        FinancialYear::class

    );

}

}