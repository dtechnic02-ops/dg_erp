<?php

namespace App\Services\Cbms;

final readonly class CbmsReconciliationResult
{
    public function __construct(public bool $verified, public array $evidence = [], public string $reason = 'external_verification_unavailable') {}
}
