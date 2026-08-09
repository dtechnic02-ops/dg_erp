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

        abort_unless($user->hasPermission($permission, $user->company_id), 403);
        abort_if($user->company?->status === 'blocked', 403, 'Company blocked');

        return $next($request);
    }
}
