<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeePayment extends Model
{
    protected $fillable = [

        'company_id',

        'financial_year_id',

        'employee_account_id',

        'voucher_no',

        'payment_date',

        'salary_year',

        'salary_month',

        'account_id',

        'amount',

        'attachment',

        'note',

        'created_by',

        'status'

    ];


    /*
    |--------------------------------------------------------------------------
    | Employee
    |--------------------------------------------------------------------------
    */
    public function employee()
    {
        return $this->belongsTo(
            EmployeeAccount::class,
            'employee_account_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Account
    |--------------------------------------------------------------------------
    */
    public function account()
    {
        return $this->belongsTo(
            Account::class,
            'account_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Creator
    |--------------------------------------------------------------------------
    */
    public function creator()
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Financial Year
    |--------------------------------------------------------------------------
    */
    public function financialYear()
    {
        return $this->belongsTo(
            FinancialYear::class,
            'financial_year_id'
        );
    }
}

