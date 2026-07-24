<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Company;

class CompanyDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $company = Company::find($user->company_id);

        return view('company.dashboard', compact('company'));
    }
}