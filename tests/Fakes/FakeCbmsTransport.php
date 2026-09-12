<?php

namespace Tests\Fakes;

use App\Services\Cbms\CbmsHttpTransport;
use App\Services\Cbms\CbmsTransportResult;
use App\Services\Cbms\CbmsResponseCodeExtractor;
use RuntimeException;

class FakeCbmsTransport implements CbmsHttpTransport
{
    public array $calls = [];
    private array $results = [];

    public function push(CbmsTransportResult $result): self
    {
        $this->results[] = $result;
        return $this;
    }

    public function respond(int $httpStatus, ?string $body, ?\DateTimeInterface $at = null): self
    {
        $code = (new CbmsResponseCodeExtractor)->extract($body);
        return $this->push(CbmsTransportResult::response($at ?? now(), $httpStatus, $code, $body));
    }

    public function send(string $endpointType, array $payload): CbmsTransportResult
    {
        $this->calls[] = compact('endpointType', 'payload');
        if ($this->results === []) throw new RuntimeException('Fake CBMS transport has no scripted result.');
        return array_shift($this->results);
    }

    public function post(string $endpointType, array $payload): array
    {
        $result = $this->send($endpointType, $payload);
        return ['http_status' => $result->httpStatus, 'response_code' => $result->responseCode, 'body' => $result->body];
    }
}
