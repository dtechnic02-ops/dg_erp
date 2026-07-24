<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class LoginRedirectService
{
    public function __construct(private SubscriptionService $subscriptionService)
    {
    }

    public function redirectAfterLogin(User $user): RedirectResponse
    {
        $user->loadMissing('role');

        $role = $user->role;

        if (! $role) {
            return $this->denyAccess('No role assigned');
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

    private function denyAccess(string $message): RedirectResponse
    {
        Auth::logout();

        return redirect()
            ->route('login')
            ->with('error', $message);
    }
}
