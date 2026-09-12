<?php

namespace App\Services\Cbms;

use App\Models\CbmsTransmission;

final readonly class CbmsProcessingResult
{
    public function __construct(public CbmsTransmission $transmission, public bool $retryable = false) {}
}
