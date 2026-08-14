<?php

namespace App\Services\Accounting\Profiles;

use App\Models\SalesInvoice;
use App\Services\Accounting\Builders\SalesCogsAccountingDataBuilder;
use InvalidArgumentException;

class SalesCogsPostingProfile
{
    public function __construct(
        private readonly SalesCogsAccountingDataBuilder $builder
    ) {
    }

    public function build(SalesInvoice $sale): array
    {
        $data = $this->builder->build($sale);
        $financialYearId = $data['financial_year_id'] ?? null;

        if (filter_var($financialYearId, FILTER_VALIDATE_INT) === false || (int) $financialYearId < 1) {
            throw new InvalidArgumentException('The financial_year_id value must be a positive integer.');
        }

        $data['financial_year_id'] = (int) $financialYearId;

        return $data;
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
