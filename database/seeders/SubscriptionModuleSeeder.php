<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class SubscriptionModuleSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            BillingCycleSeeder::class,
            SubscriptionPlanSeeder::class,
            SubscriptionPlanBillingOptionSeeder::class,
        ]);
    }
}
