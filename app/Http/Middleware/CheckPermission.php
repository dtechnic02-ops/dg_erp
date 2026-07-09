<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPermission
{
    public function handle(Request $request, Closure $next, $permission)
    {
        $user = auth()->user();

        if (!$user) {
            return redirect('/login');
        }

        // super admin bypass
        if ($user->role_id == 1) {
            return $next($request);
        }

        // check permission
        if (!$user->role || !$user->role->permissions->contains('name', $permission)) {
            return "Access Denied";
        }
        if (auth()->user()->company->status === 'blocked') {
           abort(403, 'Company blocked');
        }

        return $next($request);
    }
}