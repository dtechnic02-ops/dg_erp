<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\BillingCycle;
use App\Models\SubscriptionPlan;
use App\Services\SubscriptionService;

class SubscriptionController extends Controller
{
    public function __construct(private SubscriptionService $subscriptionService)
    {
    }

    public function index()
    {
        $company = auth()->user()->company;
        $plans = SubscriptionPlan::with(['billingOptions' => fn ($q) => $q->where('is_active', true)->with('billingCycle')])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
        $billingCycles = BillingCycle::where('is_active', true)->orderBy('sort_order')->get();
        $activeSubscription = $this->subscriptionService->getActiveSubscription($company);

        return view('company.subscription.index', compact('plans', 'billingCycles', 'activeSubscription', 'company'));
    }
}
