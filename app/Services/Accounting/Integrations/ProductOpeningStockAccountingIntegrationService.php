<?php

namespace App\Services\Accounting\Integrations;

use App\Models\Product;
use App\Services\Accounting\AccountingPostingService;
use App\Services\Accounting\Builders\ProductOpeningStockAccountingDataBuilder;
use App\Services\Accounting\Profiles\ProductOpeningStockPostingProfile;
use InvalidArgumentException;

class ProductOpeningStockAccountingIntegrationService
{
    public function __construct(
        private readonly ProductOpeningStockAccountingDataBuilder $builder,
        private readonly ProductOpeningStockPostingProfile $profile,
        private readonly AccountingPostingService $postingService
    ) {
    }

    public function postOpeningStock(Product $product): void
    {
        if (! $product->exists) {
            throw new InvalidArgumentException('The product must be saved before opening stock accounting can be posted.');
        }

        $this->postingService->post(
            $this->profile->build($this->builder->build($product))
        );
    }
}
