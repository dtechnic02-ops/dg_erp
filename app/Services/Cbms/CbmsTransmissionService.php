<?php

namespace App\Services\Cbms;

use App\Models\CbmsTransmission;
use Illuminate\Database\Eloquent\Model;

class CbmsTransmissionService
{
    public function __construct(
        private readonly CbmsEvidenceSanitizer $evidence,
        private readonly CbmsTransmissionProvenanceService $provenance,
    ) {}

    public function record(Model $document, string $endpointType, array $payload, CbmsReadinessResult $readiness): CbmsTransmission
    {
        $identity = [
            'company_id' => $document->company_id,
            'transmittable_type' => $document->getMorphClass(),
            'transmittable_id' => $document->getKey(),
            'endpoint_type' => $endpointType,
        ] + $this->provenance->forCompany((int) $document->company_id);

        return CbmsTransmission::firstOrCreate($identity, [
            'status' => $readiness->isReady() ? CbmsTransmission::STATUS_PENDING : CbmsTransmission::STATUS_NOT_READY,
            'payload_hash' => $payload === [] ? null : $this->evidence->payloadHash($payload),
            'response_body_redacted' => $readiness->isReady() ? null : $readiness->toArray(),
        ]);
    }
}
