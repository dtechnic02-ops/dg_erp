<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $currentUser = $user?->newQuery()->find($user->getAuthIdentifier());

        if (!$user || $currentUser?->account_status === 'active') {
            return $next($request);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Authentication required.'], 401);
        }

        return redirect()->route('login')->with('error', 'Your account is not active. Please contact an administrator.');
    }
}
