<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierTransaction extends Model
{
    protected $fillable = [

        'company_id',

        'financial_year_id',

        'supplier_id',

        'transaction_date',

        'voucher_no',

        'reference_type',

        'reference_id',

        'reference_no',

        'description',

        'debit',

        'credit',

        'balance',

        'created_by',

        'status',

    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIP
    |--------------------------------------------------------------------------
    */

    public function supplier()
    {
        return $this->belongsTo(
            Supplier::class
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