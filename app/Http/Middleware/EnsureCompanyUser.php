<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        abort_unless($user, 403);

        $user->loadMissing('role');

        abort_unless(
            $user->role?->resolvesToCompanyDashboard(),
            403,
            'You do not have access to the company panel.'
        );

        abort_unless($user->company_id && $user->company, 403, 'Company not found.');

        return $next($request);
    }
}
