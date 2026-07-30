<?php



namespace App\Http\Controllers\Admin;



use App\Http\Controllers\Concerns\AuthorizesAdminSubscription;

use App\Http\Controllers\Controller;

use App\Models\BillingCycle;

use App\Models\Company;

use App\Models\CompanySubscription;

use App\Models\SubscriptionPlan;

use App\Services\SubscriptionService;
use App\Services\PlatformAuthorizationService;

use Illuminate\Http\Request;

use RuntimeException;



class SubscriptionController extends Controller

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

        $this->authorizePlatform('platform_subscriptions_view');



        $subscriptions = CompanySubscription::with(['company', 'plan', 'billingCycle'])

            ->when($request->filled('search'), function ($q) use ($request) {

                $search = $request->search;

                $q->whereHas('company', fn ($company) => $company->where('company_name', 'like', "%{$search}%"));

            })

            ->when($request->filled('status') && $request->status !== 'all', fn ($q) => $q->where('status', $request->status))

            ->latest('id')

            ->paginate(20)

            ->withQueryString();



        $plans = SubscriptionPlan::active()->orderBy('sort_order')->get();

        $billingCycles = BillingCycle::active()->orderBy('sort_order')->get();



        return view('admin.subscriptions.index', compact('subscriptions', 'plans', 'billingCycles'));

    }



    public function assignFreeTrial(int $companyId)

    {

        $this->authorizeAdminSubscriptionManage();



        $company = Company::findOrFail($companyId);

        $this->subscriptionService->assignFreeTrial($company, auth()->user());



        return back()->with('success', 'Free trial assigned successfully.');

    }



    public function renew(Request $request, int $companyId)

    {

        $this->authorizeAdminSubscriptionManage();



        $request->validate(['billing_cycle_id' => 'required|exists:billing_cycles,id']);



        $company = Company::findOrFail($companyId);

        $current = $this->subscriptionService->getCurrentSubscription($company);

        $cycle = BillingCycle::findOrFail($request->billing_cycle_id);



        if (! $current) {

            return back()->with('error', 'No subscription found to renew.');

        }



        try {

            $this->subscriptionService->renewSubscription($company, $current, $cycle, null, auth()->user());

        } catch (RuntimeException $e) {

            return back()->with('error', $e->getMessage());

        }



        return back()->with('success', 'Subscription renewed.');

    }



    public function upgrade(Request $request, int $companyId)

    {

        $this->authorizeAdminSubscriptionManage();



        $request->validate([

            'subscription_plan_id' => 'required|exists:subscription_plans,id',

            'billing_cycle_id' => 'required|exists:billing_cycles,id',

        ]);



        $company = Company::findOrFail($companyId);

        $current = $this->subscriptionService->getCurrentSubscription($company);

        $plan = SubscriptionPlan::findOrFail($request->subscription_plan_id);

        $cycle = BillingCycle::findOrFail($request->billing_cycle_id);



        if (! $current) {

            return back()->with('error', 'No subscription found to upgrade.');

        }



        try {

            $this->subscriptionService->upgradePlan($company, $current, $plan, $cycle, null, auth()->user());

        } catch (RuntimeException $e) {

            return back()->with('error', $e->getMessage());

        }



        return back()->with('success', 'Subscription upgraded.');

    }



    public function downgrade(Request $request, int $companyId)

    {

        $this->authorizeAdminSubscriptionManage();



        $request->validate([

            'subscription_plan_id' => 'required|exists:subscription_plans,id',

            'billing_cycle_id' => 'required|exists:billing_cycles,id',

        ]);



        $company = Company::findOrFail($companyId);

        $current = $this->subscriptionService->getCurrentSubscription($company);

        $plan = SubscriptionPlan::findOrFail($request->subscription_plan_id);

        $cycle = BillingCycle::findOrFail($request->billing_cycle_id);



        if (! $current) {

            return back()->with('error', 'No subscription found to downgrade.');

        }



        try {

            $this->subscriptionService->downgradePlan($company, $current, $plan, $cycle, null, auth()->user());

        } catch (RuntimeException $e) {

            return back()->with('error', $e->getMessage());

        }



        return back()->with('success', 'Subscription downgraded.');

    }



    public function expire(int $companyId)

    {

        $this->authorizeAdminSubscriptionManage();



        $company = Company::findOrFail($companyId);

        $this->subscriptionService->expireSubscription($company, auth()->user());



        return back()->with('success', 'Subscription expired.');

    }



    public function cancel(Request $request, int $subscriptionId)

    {

        $this->authorizeAdminSubscriptionManage();



        $request->validate(['cancel_reason' => 'required|string|max:500']);



        $subscription = CompanySubscription::findOrFail($subscriptionId);

        $this->subscriptionService->cancelSubscription($subscription, auth()->user(), $request->cancel_reason);



        return back()->with('success', 'Subscription cancelled.');

    }

    private function authorizePlatform(string $permission): void
    {
        abort_unless($this->platformAuthorization->can(auth()->user(), $permission), 403);
    }

}

