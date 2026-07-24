<?php

namespace Database\Seeders;

use App\Models\BillingCycle;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionPlanBillingOption;
use Illuminate\Database\Seeder;

class SubscriptionPlanBillingOptionSeeder extends Seeder
{
    public function run(): void
    {
        $monthly = BillingCycle::where('code', 'monthly')->first();
        $yearly = BillingCycle::where('code', 'yearly')->first();

        if (! $monthly || ! $yearly) {
            return;
        }

        $defaults = [
            'basic' => ['monthly' => 1000, 'yearly' => 10000],
            'basic_plus' => ['monthly' => 2500, 'yearly' => 25000],
            'pro' => ['monthly' => 5000, 'yearly' => 50000],
            'pro_plus' => ['monthly' => 10000, 'yearly' => 100000],
        ];

        foreach ($defaults as $planCode => $prices) {
            $plan = SubscriptionPlan::where('code', $planCode)->first();

            if (! $plan) {
                continue;
            }

            SubscriptionPlanBillingOption::updateOrCreate(
                ['subscription_plan_id' => $plan->id, 'billing_cycle_id' => $monthly->id],
                ['price' => $prices['monthly'], 'currency_code' => 'NPR', 'is_active' => true]
            );

            SubscriptionPlanBillingOption::updateOrCreate(
                ['subscription_plan_id' => $plan->id, 'billing_cycle_id' => $yearly->id],
                ['price' => $prices['yearly'], 'currency_code' => 'NPR', 'is_active' => true]
            );
        }
    }
}
