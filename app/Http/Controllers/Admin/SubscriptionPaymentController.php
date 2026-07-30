<?php



namespace App\Http\Controllers\Admin;



use App\Http\Controllers\Concerns\AuthorizesAdminSubscription;

use App\Http\Controllers\Controller;

use App\Models\BillingCycle;

use App\Models\Company;

use App\Models\SubscriptionPayment;

use App\Models\SubscriptionPlan;

use App\Services\SubscriptionService;
use App\Services\PlatformAuthorizationService;

use Illuminate\Http\Request;

use RuntimeException;



class SubscriptionPaymentController extends Controller

{

    use AuthorizesAdminSubscription;



    public function __construct(
        private SubscriptionService $subscriptionService,
        private PlatformAuthorizationService $platformAuthorization
    )

    {

    }



    public function index(Request $request)

    {

        $this->authorizePlatform('platform_subscription_payments_view');



        $payments = SubscriptionPayment::with(['company', 'plan', 'billingCycle'])

            ->when($request->filled('status') && $request->status !== 'all', fn ($q) => $q->where('status', $request->status))

            ->when($request->filled('search'), function ($q) use ($request) {

                $search = $request->search;

                $q->whereHas('company', fn ($company) => $company->where('company_name', 'like', "%{$search}%"));

            })

            ->latest('id')

            ->paginate(20)

            ->withQueryString();



        return view('admin.subscription-payments.index', compact('payments'));

    }



    public function manualForm()

    {

        $this->authorizeAdminSubscriptionManage();



        $companies = Company::orderBy('company_name')->get();

        $plans = SubscriptionPlan::active()->orderBy('sort_order')->get();

        $billingCycles = BillingCycle::active()->orderBy('sort_order')->get();



        return view('admin.subscription-payments.manual', compact('companies', 'plans', 'billingCycles'));

    }



    public function manualStore(Request $request)

    {

        $this->authorizeAdminSubscriptionManage();



        $validated = $request->validate([

            'company_id' => 'required|exists:companies,id',

            'subscription_plan_id' => 'required|exists:subscription_plans,id',

            'billing_cycle_id' => 'required|exists:billing_cycles,id',

            'action_type' => 'nullable|in:assign,renew,upgrade,downgrade',

            'amount' => 'required|numeric|min:0',

            'payment_method' => 'required|string|max:50',

            'proof' => 'nullable|image',

            'notes' => 'nullable|string|max:1000',

        ]);



        try {

            $this->subscriptionService->createManualPayment([

                'company_id' => $validated['company_id'],

                'subscription_plan_id' => $validated['subscription_plan_id'],

                'billing_cycle_id' => $validated['billing_cycle_id'],

                'action_type' => $validated['action_type'] ?? 'assign',

                'amount' => $validated['amount'],

                'payment_method' => $validated['payment_method'],

                'proof_path' => $request->file('proof')?->store('subscription-payments', 'public'),

                'notes' => $validated['notes'] ?? null,

            ], auth()->user());

        } catch (RuntimeException $e) {

            return back()->withInput()->with('error', $e->getMessage());

        }



        return back()->with('success', 'Manual subscription payment saved.');

    }



    public function verify(int $id)

    {

        $this->authorizeAdminSubscriptionManage();



        try {

            $this->subscriptionService->verifyPayment(SubscriptionPayment::findOrFail($id), auth()->user());

        } catch (RuntimeException $e) {

            return back()->with('error', $e->getMessage());

        }



        return back()->with('success', 'Payment verified.');

    }



    public function approve(int $id)

    {

        $this->authorizeAdminSubscriptionManage();



        try {

            $this->subscriptionService->approvePayment(SubscriptionPayment::findOrFail($id), auth()->user());

        } catch (RuntimeException $e) {

            return back()->with('error', $e->getMessage());

        }



        return back()->with('success', 'Payment approved and subscription updated.');

    }



    public function reject(Request $request, int $id)

    {

        $this->authorizeAdminSubscriptionManage();



        $request->validate(['rejection_reason' => 'required|string|max:500']);



        try {

            $this->subscriptionService->rejectPayment(

                SubscriptionPayment::findOrFail($id),

                auth()->user(),

                $request->rejection_reason

            );

        } catch (RuntimeException $e) {

            return back()->with('error', $e->getMessage());

        }



        return back()->with('success', 'Payment rejected.');

    }



    public function invoice(int $id)

    {

        $this->authorizePlatform('platform_subscription_payments_invoice_view');



        $payment = SubscriptionPayment::with(['company', 'plan', 'billingCycle'])->findOrFail($id);



        return view('admin.invoice', [

            'company' => $payment->company,

            'plan' => $payment->plan,

            'amount' => $payment->amount,

            'date' => $payment->payment_date ?? now(),

            'expiry' => $payment->company->expiry_date ?? null,

            'logo' => null,

            'signature' => null,

        ]);

    }

    private function authorizePlatform(string $permission): void
    {
        abort_unless($this->platformAuthorization->can(auth()->user(), $permission), 403);
    }

}

