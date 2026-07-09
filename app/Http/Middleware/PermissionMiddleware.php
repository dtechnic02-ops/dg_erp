<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, $permission)
    {
        $user = auth()->user();

        if (!$user) {
            return redirect('/login');
        }

        if (!$user->userRole) {
            abort(403, 'No Role Assigned');
        }

        if (!$user->userRole->permissions->contains('name', $permission)) {
            abort(403, 'No Permission');
        }

        return $next($request);
    }
}