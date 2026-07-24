<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = auth()->user();

        abort_unless($user, 403);

        abort_unless(
            $user->hasPermission($permission),
            403,
            'You do not have permission to perform this action.'
        );

        return $next($request);
    }
}
