<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancialYear extends Model
{

    protected $fillable = [

        'company_id',
        'name',
        'start_date',
        'end_date',
        'is_active',
        'created_by'

    ];

    public function company()
    {

        return $this->belongsTo(
            Company::class
        );

    }
    public function supplierTransactions()
{
    return $this->hasMany(
        SupplierTransaction::class
    );
}

}
