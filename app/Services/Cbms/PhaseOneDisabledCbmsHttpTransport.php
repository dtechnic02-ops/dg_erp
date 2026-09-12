<?php

namespace App\Services\Cbms;

use App\Models\CbmsTransmission;
use LogicException;

class PhaseOneDisabledCbmsHttpTransport implements CbmsHttpTransport
{
    public function kind(): string
    {
        return CbmsTransmission::TRANSPORT_DISABLED;
    }

    public function send(string $endpointType, array $payload): CbmsTransportResult
    {
        throw new LogicException('Real CBMS network submission is disabled by configuration.');
    }

    public function post(string $endpointType, array $payload): array
    {
        $this->send($endpointType, $payload);
    }
}
