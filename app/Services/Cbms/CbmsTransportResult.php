<?php

namespace App\Services\Cbms;

use DateTimeInterface;

final readonly class CbmsTransportResult
{
    public function __construct(
        public string $classification,
        public DateTimeInterface $attemptedAt,
        public ?int $httpStatus = null,
        public ?string $responseCode = null,
        public ?string $body = null,
    ) {}

    public static function response(DateTimeInterface $at, int $httpStatus, ?string $code, ?string $body): self
    {
        return new self('response', $at, $httpStatus, $code, $body);
    }

    public static function failure(string $classification, DateTimeInterface $at, ?string $body = null): self
    {
        return new self($classification, $at, null, null, $body);
    }
}
