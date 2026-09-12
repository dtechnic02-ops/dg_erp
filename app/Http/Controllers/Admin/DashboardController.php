<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Company;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\CompanyRegistration;
use App\Models\Role;
use App\Services\PlatformCountryScopeService;

class DashboardController extends Controller
{
    public function index(PlatformCountryScopeService $countryScope)
    {
        // 🔥 ONLINE TRACK (IMPORTANT FIX)
      if (auth()->check()) {
    $user = auth()->user();
    $user->last_seen = now();
    $user->save();
    }

        $users = $countryScope->scopeUsers(User::query(), $user);
        $companies = $countryScope->scopeCompanies(Company::query(), $user);
        $registrations = $countryScope->scopeRegistrations(CompanyRegistration::query(), $user);
        $payments = $countryScope->scopeSubscriptionPayments(SubscriptionPayment::query(), $user);

        // 👤 USERS
        $totalUsers = (clone $users)->count();

        $activeUsers = (clone $users)->where('account_status', 'active')->count();
        $blockedUsers = (clone $users)->where('account_status', 'blocked')->count();
        $pendingUsers = (clone $users)->where('account_status', 'pending')->count();

        $admins = (clone $users)->where('role_id', Role::SUPER_ADMIN_ID)->count();
        $staff = (clone $users)->where('role_id', Role::COMPANY_STAFF_ID)->count();

        // 🟢 ONLINE USERS (last 2 minutes)
        $onlineUsers = (clone $users)->whereNotNull('last_seen')
            ->where('last_seen', '>=', now()->subMinutes(2))
            ->count();

        // ⚫ OFFLINE USERS
        $offlineUsers = $totalUsers - $onlineUsers;

        // 🏢 COMPANIES
        $totalCompanies = (clone $companies)->count();

        $activeCompanies = (clone $companies)->where('status', 'active')->count();
        $blockedCompanies = (clone $companies)->where('status', 'blocked')->count();

        $expiredCompanies = (clone $companies)->whereNotNull('expiry_date')
            ->where('expiry_date', '<', now())
            ->count();

        // 📝 REGISTRATION
        $totalRegistrations = (clone $registrations)->count();

        $approved = (clone $registrations)->where('status', 'approved')->count();
        $rejected = (clone $registrations)->where('status', 'rejected')->count();
        $pending = (clone $registrations)->where('status', 'pending')->count();

        // 💳 PAYMENTS
        $totalPayments = (clone $payments)->count();

        $approvedPayments = (clone $payments)->where('status', 'approved')->count();
        $rejectedPayments = (clone $payments)->where('status', 'rejected')->count();
        $pendingPayments = (clone $payments)->where('status', 'pending')->count();

        // 🎁 TRIAL
        $trial = (clone $companies)->whereNull('expiry_date')->count();

        // 📦 SYSTEM
        $plans = SubscriptionPlan::count();

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
