<?php

namespace App\Services\Accounting\Profiles;

use App\Models\SalesReturn;
use App\Services\Accounting\Builders\SalesReturnCogsAccountingDataBuilder;

class SalesReturnCogsPostingProfile
{
    public function __construct(private readonly SalesReturnCogsAccountingDataBuilder $builder) {}
    public function build(SalesReturn $return): array { return $this->builder->build($return); }
    public function hasProductItems(SalesReturn $return): bool { return $this->builder->hasProductItems($return); }
}
