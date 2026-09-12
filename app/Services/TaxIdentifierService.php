<?php

namespace App\Services;

class TaxIdentifierService
{
    public function normalize(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $normalized = strtoupper(trim((string) $value));
        if ($normalized === '') {
            return null;
        }

        return preg_replace('/[\s-]+/', '', $normalized);
    }

    public function isValid(?string $value): bool
    {
        return $value !== null && preg_match('/^[A-Z0-9]{5,20}$/', $value) === 1;
    }

    public function validationRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if ($value !== null && ! $this->isValid((string) $value)) {
                $fail('The :attribute must contain 5 to 20 letters or digits after spaces and hyphens are removed.');
            }
        };
    }
}
