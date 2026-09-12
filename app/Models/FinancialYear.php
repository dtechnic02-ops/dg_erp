<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancialYear extends Model
{

    protected static function booted(): void
    {
        static::updating(function (FinancialYear $financialYear): void {
            if (! $financialYear->isDirty(['name', 'start_date', 'end_date'])) return;
            if (app(\App\Services\FiscalDocumentPolicyService::class)
                ->financialYearHasPermanentFiscalHistory($financialYear)) {
                throw new \RuntimeException('This fiscal year contains issued fiscal documents and cannot be materially changed.');
            }
        });
        static::deleting(function (FinancialYear $financialYear): void {
            if (app(\App\Services\FiscalDocumentPolicyService::class)
                ->financialYearHasPermanentFiscalHistory($financialYear)) {
                throw new \RuntimeException('This fiscal year contains issued fiscal documents and cannot be deleted.');
            }
        });
    }

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
