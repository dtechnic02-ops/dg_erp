<?php

namespace App\Services;

use InvalidArgumentException;

final class Money
{
    public static function normalize(mixed $value): string
    {
        if (is_int($value)) {
            $value = (string) $value;
        }

        if (! is_string($value) && ! is_float($value)) {
            throw new InvalidArgumentException('Money must be a decimal value.');
        }

        $value = trim((string) $value);

        if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $value)) {
            throw new InvalidArgumentException('Money must be non-negative with no more than two decimal places.');
        }

        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');

        return (ltrim($whole, '0') ?: '0') . '.' . str_pad($fraction, 2, '0');
    }

    public static function add(mixed ...$values): string
    {
        $cents = 0;
        foreach ($values as $value) {
            $cents += self::cents($value);
        }

        return self::fromCents($cents);
    }

    public static function subtract(mixed $left, mixed $right): string
    {
        $result = self::cents($left) - self::cents($right);
        if ($result < 0) {
            throw new InvalidArgumentException('Money result cannot be negative.');
        }

        return self::fromCents($result);
    }

    public static function compare(mixed $left, mixed $right): int
    {
        return self::cents($left) <=> self::cents($right);
    }

    public static function isZero(mixed $value): bool
    {
        return self::cents($value) === 0;
    }

    private static function cents(mixed $value): int
    {
        [$whole, $fraction] = explode('.', self::normalize($value), 2);

        return ((int) $whole * 100) + (int) $fraction;
    }

    private static function fromCents(int $cents): string
    {
        return intdiv($cents, 100) . '.' . str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}
