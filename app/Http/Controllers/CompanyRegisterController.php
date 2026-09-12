<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CompanyRegistration;
use App\Models\Country;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Services\PlatformAuthorizationService;

class CompanyRegisterController extends Controller
{
    public function showForm()
    {
        $this->authorizeCreator();
        return view('company.register', ['countries' => Country::query()->where('is_active', true)->orderBy('name')->get()]);
    }

    public function register(Request $request)
    {
        $this->authorizeCreator();
        $request->validate([
            'company_name' => 'required',
            'full_name' => 'required',

            'email' => 'required|email|unique:company_registrations,email|unique:companies,email',
            'mobile_no' => 'required|unique:companies,mobile',

            'username' => 'required|unique:company_registrations,username',
            'password' => 'required|min:6',
            'country_id' => ['required', Rule::exists('countries', 'id')->where(fn ($query) => $query->where('is_active', true))],

        ], [

            'email.required' => 'Email is required',
            'email.email' => 'Enter valid email',
            'email.unique' => 'Email already registered',

            'mobile_no.required' => 'Mobile number is required',
            'mobile_no.unique' => 'Mobile already registered',

            'username.unique' => 'Username already taken',
            'password.min' => 'Password must be at least 6 characters',

        ]);

        // 🔥 IMPORTANT (missing part)
        CompanyRegistration::create([
            'company_name' => $request->company_name,
            'full_name' => $request->full_name,
            'email' => $request->email,
            'mobile_no' => $request->mobile_no,
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'country_id' => $request->integer('country_id'),
            'registered_by_user_id' => auth()->id(),
            'status' => 'pending'
        ]);

        return redirect()->route('admin.registrations')->with('success', 'Registration Submitted');
    }

    private function authorizeCreator(): void
    {
        abort_unless(app(PlatformAuthorizationService::class)->can(auth()->user(), PlatformAuthorizationService::REGISTRATIONS_CREATE), 403);
    }
}
