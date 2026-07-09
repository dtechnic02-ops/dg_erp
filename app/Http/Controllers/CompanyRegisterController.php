<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CompanyRegistration;
use Illuminate\Support\Facades\Hash;

class CompanyRegisterController extends Controller
{
    public function showForm()
    {
        return view('company.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'company_name' => 'required',
            'full_name' => 'required',

            'email' => 'required|email|unique:company_registrations,email|unique:companies,email',
            'mobile_no' => 'required|unique:companies,mobile',

            'username' => 'required|unique:company_registrations,username',
            'password' => 'required|min:6',

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
            'status' => 'pending'
        ]);

        return redirect()->route('login')->with('success', 'Registration Submitted');
    }
}