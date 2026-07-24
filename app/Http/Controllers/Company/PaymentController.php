<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\BillingCycle;
use App\Models\SubscriptionPlan;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use RuntimeException;

class PaymentController extends Controller
{
    public function __construct(private SubscriptionService $subscriptionService)
    {
    }

    public function store(Request $request)
    {
        $request->validate([
            'subscription_plan_id' => 'required|exists:subscription_plans,id',
            'billing_cycle_id' => 'required|exists:billing_cycles,id',
            'screenshot' => 'required|image',
        ]);

        try {
            $this->subscriptionService->submitPayment([
                'subscription_plan_id' => $request->subscription_plan_id,
                'billing_cycle_id' => $request->billing_cycle_id,
                'payment_method' => 'manual',
                'payment_date' => now()->toDateString(),
                'proof_path' => $request->file('screenshot')->store('subscription-payments', 'public'),
            ], auth()->user());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Payment submitted. Waiting for approval.');
    }
}
