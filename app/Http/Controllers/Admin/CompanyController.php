<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    // 🔥 LIST
    public function index()
    {
        abort_unless(auth()->check() && auth()->user()->role_id == 1, 403);

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

    // 🔐 DELETE
    public function delete(Request $request, $id)
    {
        abort_unless(auth()->user()->role_id == 1, 403);

        $admin = auth()->user();

        if (!Hash::check($request->admin_password, $admin->password)) {
            return back()->with('error', 'Wrong Admin Password');
        }

        $company = Company::findOrFail($id);

        User::where('company_id', $company->id)->delete();
        $company->delete();

        return back()->with('success', 'Company Deleted');
    }

    // 🔥 UPDATE USER LIMIT
    public function updateLimit(Request $request, $id)
    {
        abort_unless(auth()->user()->role_id == 1, 403);

        $request->validate([
            'limit' => 'required|integer|min:0'
        ]);

        $company = Company::findOrFail($id);

        // ✅ FIXED COLUMN
        $company->selected_user_limit = $request->limit;

        $company->save();

        return back()->with('success', 'User limit updated');
    }

    // 🔥 UPDATE CUSTOMER LIMIT
    public function updateCustomerLimit(Request $request, $id)
    {
        abort_unless(auth()->user()->role_id == 1, 403);

        $request->validate([
            'customer_limit' => 'required|integer|min:0'
        ]);

        $company = Company::findOrFail($id);

        $company->selected_customer_limit = $request->customer_limit;
        $company->save();

        return back()->with('success', 'Customer limit updated');
    }

    // 🔥 BLOCK
    public function block($id)
    {
        abort_unless(auth()->user()->role_id == 1, 403);

        $company = Company::findOrFail($id);

        $company->status = 'blocked';
        $company->save();

        return back()->with('success', 'Company Blocked');
    }

    // 🔥 UNBLOCK
    public function unblock($id)
    {
        abort_unless(auth()->user()->role_id == 1, 403);

        $company = Company::findOrFail($id);

        $company->status = 'active';
        $company->save();

        return back()->with('success', 'Company Activated');
    }

    // 🔥 RESET PASSWORD
    public function resetPassword($id)
    {
        abort_unless(auth()->user()->role_id == 1, 403);

        $company = Company::findOrFail($id);

        $user = User::where('company_id', $company->id)
            ->where('role_id', 2)
            ->first();

        if (!$user) {
            return back()->with('error', 'Company admin not found');
        }

        $user->password = Hash::make('123456');
        $user->save();

        return back()->with('success', 'Password reset to 123456');
    }
}