<?php

namespace App\Services\Accounting\Integrations;

use App\Models\Supplier;
use App\Services\Accounting\AccountingPostingService;
use App\Services\Accounting\Builders\SupplierOpeningBalanceAccountingDataBuilder;
use App\Services\Accounting\Profiles\SupplierOpeningBalancePostingProfile;
use InvalidArgumentException;

class SupplierOpeningBalanceAccountingIntegrationService
{
    public function __construct(
        private readonly SupplierOpeningBalanceAccountingDataBuilder $builder,
        private readonly SupplierOpeningBalancePostingProfile $profile,
        private readonly AccountingPostingService $postingService
    ) {
    }

    public function postOpeningBalance(Supplier $supplier): void
    {
        if (! $supplier->exists) {
            throw new InvalidArgumentException('The supplier must be saved before opening balance accounting can be posted.');
        }

        $this->postingService->post(
            $this->profile->build($this->builder->build($supplier))
        );
    }
}
