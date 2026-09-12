<?php

namespace App\Http\Middleware;

use App\Services\UserFacingErrorSanitizerService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanitizeUserFacingErrors
{
    public function __construct(private UserFacingErrorSanitizerService $sanitizer) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->hasSession() && $request->session()->has('error')) {
            $error = $request->session()->get('error');
            $sanitized = $this->sanitizer->sanitize($error, 'redirect_flash');

            if ($sanitized !== $error) {
                $request->session()->put('error', $sanitized);
            }
        }

        if ($response instanceof JsonResponse) {
            $data = $response->getData(true);
            foreach (['message', 'error'] as $key) {
                if (array_key_exists($key, $data)) {
                    $data[$key] = $this->sanitizer->sanitize($data[$key], 'json_'.$key);
                }
            }
            $response->setData($data);
        }

        return $response;
    }
}
