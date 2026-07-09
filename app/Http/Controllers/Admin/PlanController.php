<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    // 🔥 LIST
    public function index()
    {
        abort_unless(auth()->check() && auth()->user()->role_id == 1, 403);

        $plans = Plan::latest()->get();
        return view('admin.plans', compact('plans'));
    }

    // 🔥 STORE
    public function store(Request $request)
{
    abort_unless(auth()->user()->role_id == 1, 403);

    $request->validate([
        'name' => 'required|string|max:100',
        'user_limit' => 'required|integer|min:1',
        'customer_limit' => 'required|integer|min:0',
        'price' => 'nullable|numeric|min:0',
        'type' => 'required|in:trial,monthly,yearly'
    ]);

    $duration = match ($request->type) {
        'trial' => 7,
        'monthly' => 30,
        'yearly' => 365,
    };

    $price = $request->type == 'trial' ? 0 : ($request->price ?? 0);

    Plan::create([
        'name' => $request->name, // ✅ MANUAL
        'user_limit' => $request->user_limit,
        'customer_limit' => $request->customer_limit,
        'price' => $price,
        'duration_days' => $duration,
        'type' => $request->type,
    ]);

    return back()->with('success', 'Plan Created Successfully');
}
    // 🔥 EDIT
    public function edit($id)
    {
        abort_unless(auth()->user()->role_id == 1, 403);

        $plan = Plan::findOrFail($id);
        return view('admin.plan_edit', compact('plan'));
    }

    // 🔥 UPDATE
    public function update(Request $request, $id)
{
    abort_unless(auth()->user()->role_id == 1, 403);

    $request->validate([
        'name' => 'required|string|max:100',
        'user_limit' => 'required|integer|min:1',
        'customer_limit' => 'required|integer|min:0',
        'price' => 'nullable|numeric|min:0',
        'type' => 'required|in:trial,monthly,yearly'
    ]);

    $plan = Plan::findOrFail($id);

    $duration = match ($request->type) {
        'trial' => 7,
        'monthly' => 30,
        'yearly' => 365,
    };

    $price = $request->type == 'trial' ? 0 : ($request->price ?? 0);

    $plan->update([
        'name' => $request->name,
        'user_limit' => $request->user_limit,
        'customer_limit' => $request->customer_limit,
        'price' => $price,
        'duration_days' => $duration,
        'type' => $request->type,
    ]);

    return redirect()->route('admin.plans')->with('success', 'Plan Updated');
}

    // 🔥 DELETE
    public function delete($id)
    {
        abort_unless(auth()->user()->role_id == 1, 403);

        Plan::findOrFail($id)->delete();

        return back()->with('success', 'Plan Deleted');
    }
}