<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashAccount extends Model
{
    protected $fillable = [

        'company_id',

        'account_name',

        'account_number',

        'opening_balance',

        'note',

        'status',
    ];
}

