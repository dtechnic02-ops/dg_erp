<?php

namespace App\Services;

use App\Models\Company;
use App\Models\SalesInvoice;
use Illuminate\Support\Carbon;

class SalesFiscalIssueDateTimeService
{
    public const NEPAL_TIMEZONE = 'Asia/Kathmandu';

    public function __construct(private readonly NepalIrdCbmsModeService $mode)
    {
    }

    public function issuanceAttributes(Company $company): array
    {
        if (! $this->mode->isActiveForCompany($company)) {
            return [];
        }

        return ['fiscal_issued_at' => now()];
    }

    public function isComplete(SalesInvoice $invoice): bool
    {
        return $invoice->fiscal_issued_at !== null;
    }

    public function nepalLocal(SalesInvoice $invoice): ?Carbon
    {
        if ($invoice->fiscal_issued_at === null
            || ! $invoice->company) {
            return null;
        }

        return $invoice->fiscal_issued_at->copy()->setTimezone(self::NEPAL_TIMEZONE);
    }
}
