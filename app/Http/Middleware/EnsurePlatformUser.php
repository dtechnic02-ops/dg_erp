<?php

namespace App\Http\Middleware;

use App\Models\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlatformUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        abort_unless($user && $user->company_id === null && in_array((int) $user->role_id, [Role::SUPER_ADMIN_ID, Role::SUPER_STAFF_ID], true), 403);
        return $next($request);
    }
}
