<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

class LoginRedirectService
{
    private const SUPER_STAFF_DESTINATIONS = [
        'platform_registrations_view' => 'admin.registrations',
        'platform_companies_view' => 'admin.companies',
        'platform_subscriptions_view' => 'admin.subscriptions.index',
        'platform_subscription_payments_view' => 'admin.subscription-payments.index',
        'platform_subscription_reports_view' => 'admin.subscription-reports.index',
    ];

    public function __construct(
        private SubscriptionService $subscriptionService,
        private PlatformAuthorizationService $platformAuthorization
    )
    {
    }

    public function redirectAfterLogin(User $user): RedirectResponse
    {
        $user->loadMissing('role');

        $role = $user->role;

        if (! $role) {
            return $this->denyAccess('No role assigned');
        }

        if ((int) $user->role_id === Role::SUPER_STAFF_ID) {
            return $this->redirectSuperStaffUser($user);
        }

        if ($role->resolvesToAdminDashboard()) {
            return redirect()->route('admin.dashboard');
        }

        if ($role->resolvesToCompanyDashboard()) {
            return $this->redirectCompanyUser($user);
        }

        return $this->denyAccess('Unauthorized access.');
    }

    private function redirectCompanyUser(User $user): RedirectResponse
    {
        $company = $user->company;

        if ($company) {
            if ($company->status === 'blocked') {
                return $this->denyAccess('Company blocked');
            }

            if (! $this->subscriptionService->isSubscriptionOperational($company)) {
                return redirect()
                    ->route('company.subscription.index')
                    ->with('error', 'Subscription expired. Please renew your plan.');
            }
        }

        return redirect()->route('company.dashboard');
    }

    private function redirectSuperStaffUser(User $user): RedirectResponse
    {
        foreach (self::SUPER_STAFF_DESTINATIONS as $permission => $route) {
            if (Route::has($route) && $this->platformAuthorization->can($user, $permission)) {
                return redirect()->route($route);
            }
        }

        return redirect()
            ->route('admin.no-access')
            ->with('error', 'No platform permission has been assigned. Please contact the Super Admin.');
    }

    private function denyAccess(string $message): RedirectResponse
    {
        Auth::logout();

        return redirect()
            ->route('login')
            ->with('error', $message);
    }
}
