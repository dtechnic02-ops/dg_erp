<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Company;
use App\Models\Plan;
use App\Models\Subscription;
use Carbon\Carbon;

class PaymentApprovalController extends Controller
{
    // 🔥 LIST
    public function index()
    {
        $payments = Payment::with(['company','plan'])->latest()->get();
        return view('admin.payments', compact('payments'));
    }

    // 🔥 APPROVE
    public function approve($id)
    {
        $payment = Payment::findOrFail($id);

        if ($payment->status !== 'pending') {
            return back()->with('error', 'Already processed');
        }

        $payment->status = 'approved';
        $payment->save();

        $plan = Plan::findOrFail($payment->plan_id);
        $company = Company::findOrFail($payment->company_id);

        // expiry
        if ($company->expiry_date && Carbon::parse($company->expiry_date)->gt(now())) {
            $expiryDate = Carbon::parse($company->expiry_date)->addDays($plan->duration_days);
        } else {
            $expiryDate = now()->addDays($plan->duration_days);
        }

        Subscription::where('company_id', $company->id)->update(['status' => 'expired']);

        $company->update([
            'selected_user_limit' => $plan->user_limit,
            'status' => 'active',
            'expiry_date' => $expiryDate
        ]);

        Subscription::create([
            'company_id' => $company->id,
            'plan_id' => $plan->id,
            'start_date' => now(),
            'expiry_date' => $expiryDate,
            'status' => 'active'
        ]);

        return back()->with('success', 'Payment Approved');
    }

    // 🔥 MANUAL FORM (IMPORTANT)
    public function manualForm()
    {
        $companies = Company::all();
        $plans = Plan::all();

        return view('admin.manual_payment', compact('companies','plans'));
    }

    // 🔥 MANUAL STORE
    public function manualStore(Request $request)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
            'plan_id' => 'required|exists:plans,id',
            'amount' => 'required|numeric|min:1',
        ]);

        Payment::create([
            'company_id' => $request->company_id,
            'plan_id' => $request->plan_id,
            'amount' => $request->amount,
            'method' => 'manual',
            'status' => 'pending',
            'screenshot' => $request->file('screenshot')
                ? $request->file('screenshot')->store('payments','public')
                : null
        ]);

        return back()->with('success', 'Manual payment saved');
    }

    // 🔥 INVOICE
    public function invoice($id)
    {
        $payment = Payment::with(['company','plan'])->findOrFail($id);

        return view('admin.invoice', [
            'company' => $payment->company,
            'plan' => $payment->plan,
            'amount' => $payment->amount,
            'date' => now(),
            'expiry' => $payment->company->expiry_date ?? null,
            'logo' => null,
            'signature' => null
        ]);
    }
}