<?php

namespace App\Services\Accounting\Profiles;

use App\Models\SalesInvoice;
use App\Services\Accounting\Builders\SalesCogsAccountingDataBuilder;

class SalesCogsPostingProfile
{
    public function __construct(
        private readonly SalesCogsAccountingDataBuilder $builder
    ) {
    }

    public function build(SalesInvoice $sale): array
    {
        return $this->builder->build($sale);
    }

    public function hasSnapshots(SalesInvoice $sale): bool
    {
        return $this->builder->hasSnapshots($sale);
    }

    public function hasProductItems(SalesInvoice $sale): bool
    {
        return $this->builder->hasProductItems($sale);
    }
}
