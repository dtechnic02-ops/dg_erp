<?php

namespace App\Services\Cbms;

use App\Models\FinancialYear;
use App\Services\NepaliDateService;
use InvalidArgumentException;

class CbmsFiscalYearFormatter
{
    public function __construct(private readonly NepaliDateService $dates) {}

    public function format(FinancialYear $financialYear): string
    {
        if (!$financialYear->start_date || !$financialYear->end_date) {
            throw new InvalidArgumentException('The financial year has no authoritative date range.');
        }

        $startYear = (int) substr($this->dates->adToBs((string) $financialYear->start_date), 0, 4);
        $endYear = (int) substr($this->dates->adToBs((string) $financialYear->end_date), 0, 4);
        if ($endYear !== $startYear + 1) {
            throw new InvalidArgumentException('The financial-year date range does not resolve to consecutive BS years.');
        }

        return sprintf('%04d.%03d', $startYear, $endYear % 1000);
    }
}
