<?php



namespace App\Http\Controllers\Admin;



use App\Http\Controllers\Concerns\AuthorizesAdminSubscription;

use App\Http\Controllers\Controller;

use App\Models\BillingCycle;

use App\Models\SubscriptionPlan;

use App\Services\SubscriptionService;

use Illuminate\Http\Request;

use Illuminate\Support\Str;



class SubscriptionPlanController extends Controller

{

    use AuthorizesAdminSubscription;



    public function __construct(private SubscriptionService $subscriptionService)

    {

    }



    public function index()

    {

        $this->authorizeAdminSubscriptionManage();



        $plans = SubscriptionPlan::with(['billingOptions.billingCycle'])

            ->orderBy('sort_order')

            ->get();

        $billingCycles = BillingCycle::active()->orderBy('sort_order')->get();



        return view('admin.subscription-plans.index', compact('plans', 'billingCycles'));

    }



    public function store(Request $request)

    {

        $this->authorizeAdminSubscriptionManage();



        $data = $this->validatedPlan($request);

        $data['code'] = $this->uniqueCode($data['name']);

        $data['created_by'] = auth()->id();

        $data['updated_by'] = auth()->id();



        $plan = SubscriptionPlan::create($data);

        $this->subscriptionService->syncBillingOptions($plan, $request->input('billing_options', []), auth()->user());



        return back()->with('success', 'Subscription plan created.');

    }



    public function update(Request $request, int $id)

    {

        $this->authorizeAdminSubscriptionManage();



        $plan = SubscriptionPlan::findOrFail($id);

        $data = $this->validatedPlan($request);

        $data['updated_by'] = auth()->id();



        $plan->update($data);

        $this->subscriptionService->syncBillingOptions($plan, $request->input('billing_options', []), auth()->user());



        return redirect()->route('admin.subscription-plans.index')->with('success', 'Subscription plan updated.');

    }



    public function activate(int $id)

    {

        $this->authorizeAdminSubscriptionManage();



        SubscriptionPlan::findOrFail($id)->update([

            'is_active' => true,

            'updated_by' => auth()->id(),

        ]);



        return back()->with('success', 'Plan activated.');

    }



    public function deactivate(int $id)

    {

        $this->authorizeAdminSubscriptionManage();



        SubscriptionPlan::findOrFail($id)->update([

            'is_active' => false,

            'updated_by' => auth()->id(),

        ]);



        return back()->with('success', 'Plan deactivated.');

    }



    protected function validatedPlan(Request $request): array

    {

        $validated = $request->validate([

            'name' => 'required|string|max:100',

            'description' => 'nullable|string',

            'staff_limit' => 'required|integer|min:1',

            'hidden_modules' => 'nullable|array',

            'hidden_modules.*' => 'in:' . implode(',', config('subscription.hidden_module_codes')),

            'sort_order' => 'nullable|integer|min:0',

        ]);



        $validated['hidden_modules'] = array_values($validated['hidden_modules'] ?? []);

        $validated['sort_order'] = $validated['sort_order'] ?? 0;



        return $validated;

    }



    protected function uniqueCode(string $name): string

    {

        $code = Str::slug($name, '_');

        $base = $code ?: 'plan';

        $suffix = 1;



        while (SubscriptionPlan::where('code', $code)->exists()) {

            $code = $base . '_' . $suffix;

            $suffix++;

        }



        return $code;

    }

}

