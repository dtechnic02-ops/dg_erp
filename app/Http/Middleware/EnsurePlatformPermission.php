<?php

namespace App\Http\Middleware;

use App\Services\PlatformAuthorizationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlatformPermission
{
    public function __construct(private readonly PlatformAuthorizationService $authorization) {}

    public function handle(Request $request, Closure $next, string $permission): Response
    {
        abort_unless($this->authorization->can($request->user(), $permission), 403);
        return $next($request);
    }
}
