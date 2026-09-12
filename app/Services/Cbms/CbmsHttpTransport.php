<?php

namespace App\Services\Cbms;

interface CbmsHttpTransport
{
    public function kind(): string;

    public function send(string $endpointType, array $payload): CbmsTransportResult;

    public function post(string $endpointType, array $payload): array;
}
