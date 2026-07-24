<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'code' => 'basic',
                'name' => 'Basic',
                'staff_limit' => 1,
                'hidden_modules' => ['crm', 'loan', 'hr', 'delivery'],
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'code' => 'basic_plus',
                'name' => 'Basic Plus',
                'staff_limit' => 5,
                'hidden_modules' => ['crm', 'delivery'],
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'code' => 'pro',
                'name' => 'Pro',
                'staff_limit' => 20,
                'hidden_modules' => ['crm'],
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'code' => 'pro_plus',
                'name' => 'Pro Plus',
                'staff_limit' => 100,
                'hidden_modules' => [],
                'is_active' => true,
                'sort_order' => 4,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(['code' => $plan['code']], $plan);
        }
    }
}
