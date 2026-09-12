<?php

namespace App\Services\Cbms;

class CbmsReadinessResult
{
    public function __construct(public readonly array $reasonCodes = []) {}

    public function isReady(): bool { return $this->reasonCodes === []; }
    public function status(): string { return $this->isReady() ? 'READY' : 'NOT_READY'; }
    public function toArray(): array { return ['status' => $this->status(), 'reason_codes' => $this->reasonCodes]; }
}
