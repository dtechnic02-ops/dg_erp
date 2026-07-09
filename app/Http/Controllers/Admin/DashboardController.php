<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Company;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\CompanyRegistration;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // 🔥 ONLINE TRACK (IMPORTANT FIX)
      if (auth()->check()) {
    $user = auth()->user();
    $user->last_seen = now();
    $user->save();
    }

        // 👤 USERS
        $totalUsers = User::count();

        $activeUsers = User::where('account_status', 'active')->count();
        $blockedUsers = User::where('account_status', 'blocked')->count();
        $pendingUsers = User::where('account_status', 'pending')->count();

        $admins = User::where('role_id', 1)->count();
        $staff = User::where('role_id', 3)->count();

        // 🟢 ONLINE USERS (last 2 minutes)
        $onlineUsers = User::whereNotNull('last_seen')
            ->where('last_seen', '>=', now()->subMinutes(2))
            ->count();

        // ⚫ OFFLINE USERS
        $offlineUsers = $totalUsers - $onlineUsers;

        // 🏢 COMPANIES
        $totalCompanies = Company::count();

        $activeCompanies = Company::where('status', 'active')->count();
        $blockedCompanies = Company::where('status', 'blocked')->count();

        $expiredCompanies = Company::whereNotNull('expiry_date')
            ->where('expiry_date', '<', now())
            ->count();

        // 📝 REGISTRATION
        $totalRegistrations = CompanyRegistration::count();

        $approved = CompanyRegistration::where('status', 'approved')->count();
        $rejected = CompanyRegistration::where('status', 'rejected')->count();
        $pending = CompanyRegistration::where('status', 'pending')->count();

        // 💳 PAYMENTS
        $totalPayments = Payment::count();

        $approvedPayments = Payment::where('status', 'approved')->count();
        $rejectedPayments = Payment::where('status', 'rejected')->count();
        $pendingPayments = Payment::where('status', 'pending')->count();

        // 🎁 TRIAL
        $trial = Company::whereNull('expiry_date')->count();

        // 📦 SYSTEM
        $plans = Plan::count();

        return view('admin.dashboard', compact(
            'totalUsers',
            'activeUsers',
            'blockedUsers',
            'pendingUsers',
            'admins',
            'staff',
            'onlineUsers',
            'offlineUsers',

            'totalCompanies',
            'activeCompanies',
            'blockedCompanies',
            'expiredCompanies',

            'totalRegistrations',
            'approved',
            'rejected',
            'pending',

            'totalPayments',
            'approvedPayments',
            'rejectedPayments',
            'pendingPayments',

            'trial',
            'plans'
        ));
    }
}