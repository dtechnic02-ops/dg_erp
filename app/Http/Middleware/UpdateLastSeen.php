<?php

namespace App\Http\Middleware;

use Closure;

class UpdateLastSeen
{
    public function handle($request, Closure $next)
    {
        if (auth()->check()) {

            auth()->user()->update([

                'last_seen' => now()
            ]);
        }

        return $next($request);
    }
}
