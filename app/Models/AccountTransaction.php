<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Account;
use App\Models\Company;
use App\Models\FinancialYear;
use App\Models\User;

class AccountTransaction extends Model
{
    protected $fillable = [

        'company_id',

        'financial_year_id',

        'account_id',

        'transaction_date',

        'voucher_no',

        'reference_type',

        'reference_id',

        'description',

        'debit',

        'credit',

        'balance',

        'created_by',

        'status'

    ];

    public function account()
    {
        return $this->belongsTo(
            Account::class,
            'account_id'
        );
    }

    public function company()
    {
        return $this->belongsTo(
            Company::class,
            'company_id'
        );
    }

    public function financialYear()
    {
        return $this->belongsTo(
            FinancialYear::class,
            'financial_year_id'
        );
    }

    public function creator()
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }
}