<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalarySheet extends Model
{
    protected $fillable = [

        'company_id',

        'financial_year_id',

        'employee_id',

        'salary_month',

        'basic_salary',

        'working_days',

        'present_days',

        'absent_days',

        'allowance',

        'bonus',

        'overtime_amount',

        'deduction',

        'net_salary',

        'status',

        'note',

        'created_by'

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
            'employee_id'
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
    public function payments()
{
    return $this->hasMany(
        SalaryPayment::class,
        'salary_sheet_id'
    );
}
}