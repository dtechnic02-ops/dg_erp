<?php

namespace App\Services\Cbms;

class CbmsEvidenceSanitizer
{
    private const SECRET_KEYS = ['username', 'password', 'credential', 'credentials', 'token', 'authorization', 'secret', 'api_key'];

    public function array(array $value): array
    {
        foreach ($value as $key => $item) {
            if (in_array(strtolower((string) $key), self::SECRET_KEYS, true)) {
                unset($value[$key]);
            } elseif (is_array($item)) {
                $value[$key] = $this->array($item);
            }
        }

        return $value;
    }

    public function excerpt(?string $value): ?string
    {
        if ($value === null) return null;
        $value = preg_replace('/(?i)(password|username|credential|token|authorization|secret|api[_-]?key)\s*[=:]\s*["\']?[^\s,"\'}<]+/', '$1=[REDACTED]', $value) ?? '';
        return mb_strcut($value, 0, (int) config('cbms.max_response_excerpt_bytes', 4096), 'UTF-8');
    }

    public function payloadHash(array $payload): string
    {
        $evidence = $this->array($payload);
        unset($evidence['isrealtime'], $evidence['datetimeClient']);
        ksort($evidence);
        return hash('sha256', json_encode($evidence, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION));
    }
}
