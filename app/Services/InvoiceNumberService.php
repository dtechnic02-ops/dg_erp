<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;

class InvoiceNumberService
{
    public static function generate(
    string $prefix,
    int $companyId,
    int $financialYearId,
    string $modelClass,
    string $column = 'invoice_no'
): string
{
    $lastRecord = $modelClass::where(
            'company_id',
            $companyId
        )
        ->where(
            'financial_year_id',
            $financialYearId
        )
        ->whereNotNull($column)
        ->latest('id')
        ->lockForUpdate()
        ->first();

    $runningNumber = 1;

    if ($lastRecord)
    {
        $parts = explode(
            '-',
            $lastRecord->{$column}
        );

        $runningNumber =
            (int) end($parts) + 1;
    }

    return sprintf(
        '%s-%d-%d-%05d',
        $prefix,
        $companyId,
        $financialYearId,
        $runningNumber
    );

    }
}

