<?php



namespace App\Http\Controllers\Admin;



use App\Http\Controllers\Concerns\AuthorizesAdminCompany;

use App\Http\Controllers\Controller;

use App\Models\Company;

use App\Models\User;

use App\Services\SubscriptionService;

use Carbon\Carbon;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Hash;



class CompanyController extends Controller

{

    use AuthorizesAdminCompany;



    public function __construct(private SubscriptionService $subscriptionService)

    {

    }



    public function index()

    {

        $this->authorizeViewCompany();



        $search = request('search');

        $status = request('status');



        $companies = Company::when($search, function ($q) use ($search) {

                $q->where('company_name', 'like', "%$search%")

                  ->orWhere('email', 'like', "%$search%");

            })

            ->when($status, function ($q) use ($status) {

                $q->where('status', $status);

            })

            ->paginate(10);



        foreach ($companies as $c) {

            $c->expiry = $c->expiry_date;



            $c->days = $c->expiry

                ? now()->diffInDays(Carbon::parse($c->expiry), false)

                : null;

        }



        return view('admin.companies', compact('companies', 'search', 'status'));

    }



    public function delete(Request $request, $id)

    {

        $this->authorizeDeleteCompany();



        $admin = auth()->user();



        if (! Hash::check($request->admin_password, $admin->password)) {

            return back()->with('error', 'Wrong Admin Password');

        }



        $company = Company::findOrFail($id);



        User::where('company_id', $company->id)->delete();

        $company->delete();



        return back()->with('success', 'Company Deleted');

    }



    public function updateLimit(Request $request, $id)

    {

        $this->authorizeEditCompany();



        $request->validate([

            'limit' => 'required|integer|min:0',

        ]);



        $company = Company::findOrFail($id);



        $company->selected_user_limit = $request->limit;



        $company->save();



        return back()->with('success', 'User limit updated');

    }



    public function updateCustomerLimit(Request $request, $id)

    {

        $this->authorizeEditCompany();



        $request->validate([

            'customer_limit' => 'required|integer|min:0',

        ]);



        $company = Company::findOrFail($id);



        $company->selected_customer_limit = $request->customer_limit;

        $company->save();



        return back()->with('success', 'Customer limit updated');

    }



    public function block(Request $request, $id)

    {

        $this->authorizeBlockCompany();



        $company = Company::findOrFail($id);



        $company->status = 'blocked';

        $company->save();



        return back()->with('success', 'Company Blocked');

    }



    public function unblock(Request $request, $id)

    {

        $this->authorizeUnblockCompany();



        $company = Company::findOrFail($id);

        $subscription = $this->subscriptionService->getActiveSubscription($company);



        $hasValidSubscription = $subscription !== null

            && (

                $subscription->expiry_date === null

                || ! Carbon::parse($subscription->expiry_date)->lt(now()->startOfDay())

            );



        $company->status = $hasValidSubscription ? 'active' : 'expired';

        $company->save();



        return back()->with(

            'success',

            $hasValidSubscription

                ? 'Company Activated'

                : 'Company unblocked. Subscription expired — status set to expired.'

        );

    }



    public function resetPassword(Request $request, $id)

    {

        $this->authorizeResetCompanyPassword();



        $company = Company::findOrFail($id);



        $user = User::where('company_id', $company->id)

            ->where('role_id', 2)

            ->first();



        if (! $user) {

            return back()->with('error', 'Company admin not found');

        }



        $user->password = Hash::make('123456');

        $user->save();



        return back()->with('success', 'Password reset to 123456');

    }

}

