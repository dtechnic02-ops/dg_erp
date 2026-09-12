<?php

namespace App\Services\Cbms;

class CbmsResponseCodeExtractor
{
    public function extract(?string $body): ?string
    {
        $body = trim((string) $body);
        if ($body === '') return null;
        if (preg_match('/^\d+$/', $body) === 1) return $body;

        $decoded = json_decode($body, true);
        if (is_int($decoded) || (is_string($decoded) && preg_match('/^\d+$/', $decoded) === 1)) return (string) $decoded;
        if (! is_array($decoded)) return null;
        foreach (['response_code', 'responseCode', 'code'] as $key) {
            $value = $decoded[$key] ?? null;
            if (is_int($value) || (is_string($value) && preg_match('/^\d+$/', $value) === 1)) return (string) $value;
        }
        return null;
    }
}
