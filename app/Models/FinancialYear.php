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
        'is_closed',
        'is_locked',
        'created_by'

    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'is_closed' => 'boolean', 'is_locked' => 'boolean'];
    }

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
