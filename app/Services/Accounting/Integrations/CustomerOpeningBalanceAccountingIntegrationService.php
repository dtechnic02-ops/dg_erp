<?php

namespace App\Services\Accounting\Integrations;

use App\Models\Customer;
use App\Services\Accounting\AccountingPostingService;
use App\Services\Accounting\Builders\CustomerOpeningBalanceAccountingDataBuilder;
use App\Services\Accounting\Profiles\CustomerOpeningBalancePostingProfile;
use InvalidArgumentException;

class CustomerOpeningBalanceAccountingIntegrationService
{
    public function __construct(
        private readonly CustomerOpeningBalanceAccountingDataBuilder $builder,
        private readonly CustomerOpeningBalancePostingProfile $profile,
        private readonly AccountingPostingService $postingService
    ) {
    }

    public function postOpeningBalance(Customer $customer): void
    {
        if (! $customer->exists) {
            throw new InvalidArgumentException('The customer must be saved before opening balance accounting can be posted.');
        }

        $this->postingService->post(
            $this->profile->build($this->builder->build($customer))
        );
    }
}
