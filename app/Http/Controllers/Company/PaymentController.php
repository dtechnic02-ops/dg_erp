<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Plan;

class PaymentController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'plan_id' => 'required',
            'screenshot' => 'required|image'
        ]);

        // 🔥 plan find
        $plan = Plan::find($request->plan_id);

        if (!$plan) {
            return back()->with('error', 'Plan not found');
        }

        // 🔥 amount auto
        $amount = $plan->price_monthly;

        $path = $request->file('screenshot')->store('payments', 'public');

        Payment::create([
            'company_id' => auth()->user()->company_id,
            'plan_id' => $plan->id,
            'amount' => $amount,
            'method' => 'manual',
            'screenshot' => $path,
            'status' => 'pending'
        ]);

        return back()->with('success', 'Payment submitted. Waiting for approval.');
    }
}