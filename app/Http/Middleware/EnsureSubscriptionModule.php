<?php

namespace App\Http\Middleware;

use App\Services\SubscriptionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscriptionModule
{
    public function __construct(private SubscriptionService $subscriptionService)
    {
    }

    public function handle(Request $request, Closure $next, string $module): Response
    {
        $user = auth()->user();

        if ($user && (int) $user->role_id === 2) {
            $company = $user->company;

            if (! $company) {
                abort(403, 'Company not found.');
            }

            $this->subscriptionService->assertModuleAccess($company, $module);
        }

        return $next($request);
    }
}
