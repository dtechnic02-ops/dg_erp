<?php

namespace App\Services;

class SupplierBalanceService
{
    public static function recalculateAllSuppliers(int $companyId): void
    {
        SupplierTransactionService::recalculateAllSuppliers($companyId);
    }
}
