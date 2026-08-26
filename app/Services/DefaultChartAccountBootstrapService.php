<?php

namespace App\Services;

use Database\Seeders\DefaultChartAccountSeeder;

class DefaultChartAccountBootstrapService
{
    public function __construct(private DefaultChartAccountSeeder $seeder)
    {
    }

    public function seedForCompany(int $companyId): void
    {
        $this->seeder->seedForCompany($companyId);
    }
}
