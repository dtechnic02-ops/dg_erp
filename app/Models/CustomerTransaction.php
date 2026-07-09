<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerTransaction extends Model
{
    protected $fillable = [

        'company_id',

        'financial_year_id',

        'customer_id',

        'transaction_date',

        'voucher_no',

        'reference_type',

        'reference_id',

        'reference_no',

        'description',

        'debit',

        'credit',

        'balance',

        'remarks',

        'created_by',

        'status',

    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIP
    |--------------------------------------------------------------------------
    */

    public function customer()
    {
        return $this->belongsTo(
            Customer::class
        );
    }

    public function financialYear()
    {
        return $this->belongsTo(
            FinancialYear::class
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