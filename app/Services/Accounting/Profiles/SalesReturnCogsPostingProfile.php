<?php

namespace App\Services\Accounting\Profiles;

use App\Models\SalesReturn;
use App\Services\Accounting\Builders\SalesReturnCogsAccountingDataBuilder;

class SalesReturnCogsPostingProfile
{
    public function __construct(private readonly SalesReturnCogsAccountingDataBuilder $builder) {}
    public function build(SalesReturn $return): array
    {
        $data = $this->builder->build($return);
        if (filter_var($data['financial_year_id'] ?? null, FILTER_VALIDATE_INT) === false || (int) $data['financial_year_id'] < 1) {
            throw new \InvalidArgumentException('The financial_year_id value must be a positive integer.');
        }

        $data['financial_year_id'] = (int) $data['financial_year_id'];

        return $data;
    }
    public function hasProductItems(SalesReturn $return): bool { return $this->builder->hasProductItems($return); }
}
