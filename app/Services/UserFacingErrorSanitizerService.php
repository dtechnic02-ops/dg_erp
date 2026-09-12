<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class UserFacingErrorSanitizerService
{
    public const GENERIC_MESSAGE = 'An unexpected error occurred. Please try again or contact support.';

    public function sanitize(mixed $value, string $context): mixed
    {
        if (!is_string($value) || !$this->looksInternal($value)) {
            return $value;
        }

        Log::error('Suppressed internal error detail from user response.', [
            'context' => $context,
            'route' => request()->route()?->getName(),
            'company_id' => auth()->user()->company_id ?? null,
            'user_id' => auth()->id(),
            'fingerprint' => hash('sha256', $value),
        ]);

        return self::GENERIC_MESSAGE;
    }

    public function looksInternal(string $message): bool
    {
        return preg_match(
            '/(?:SQLSTATE\[|PDOException|QueryException|Integrity constraint|Stack trace:|\bin \/?[^\r\n]+\.php(?:\s+on line|:\d+)|[A-Za-z]:\\\\[^\r\n]+|vendor[\\\\\/]laravel[\\\\\/]|Undefined (?:variable|array key)|Call to (?:undefined|a member function)|syntax error)/i',
            $message
        ) === 1;
    }
}
