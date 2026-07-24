<?php

namespace Database\Seeders;

use App\Models\BillingCycle;
use Illuminate\Database\Seeder;

class BillingCycleSeeder extends Seeder
{
    public function run(): void
    {
        $cycles = [
            ['code' => 'monthly', 'name' => 'Monthly', 'duration_days' => 30, 'is_lifetime' => false, 'is_active' => true, 'sort_order' => 1],
            ['code' => 'yearly', 'name' => 'Yearly', 'duration_days' => 365, 'is_lifetime' => false, 'is_active' => true, 'sort_order' => 2],
            ['code' => 'quarterly', 'name' => 'Quarterly', 'duration_days' => 90, 'is_lifetime' => false, 'is_active' => false, 'sort_order' => 3],
            ['code' => 'half_yearly', 'name' => 'Half-Yearly', 'duration_days' => 182, 'is_lifetime' => false, 'is_active' => false, 'sort_order' => 4],
            ['code' => 'lifetime', 'name' => 'Lifetime', 'duration_days' => null, 'is_lifetime' => true, 'is_active' => false, 'sort_order' => 5],
        ];

        foreach ($cycles as $cycle) {
            BillingCycle::updateOrCreate(['code' => $cycle['code']], $cycle);
        }
    }
}
