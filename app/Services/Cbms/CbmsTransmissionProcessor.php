<?php

namespace App\Services\Cbms;

use App\Models\CbmsTransmission;
use App\Models\CbmsTransmissionAttempt;
use App\Models\SalesInvoice;
use App\Models\SalesReturn;
use Illuminate\Support\Facades\DB;
use Throwable;

class CbmsTransmissionProcessor
{
    public function __construct(
        private readonly CbmsHttpTransport $transport,
        private readonly CbmsSalesBillPayloadBuilder $sales,
        private readonly CbmsBillReturnPayloadBuilder $returns,
        private readonly CbmsSalesResponseParser $salesParser,
        private readonly CbmsBillReturnResponseParser $returnParser,
        private readonly CbmsTransmissionStateMachine $states,
        private readonly CbmsEvidenceSanitizer $evidence,
        private readonly CbmsTransmissionProvenanceService $provenance,
    ) {}

    public function process(int $transmissionId): CbmsProcessingResult
    {
        $transmission = DB::transaction(function () use ($transmissionId) {
            $locked = CbmsTransmission::query()->lockForUpdate()->findOrFail($transmissionId);
            if ($locked->status !== CbmsTransmission::STATUS_QUEUED) return null;
            return $this->states->move($locked, CbmsTransmission::STATUS_PROCESSING);
        });
        if (! $transmission) return new CbmsProcessingResult(CbmsTransmission::findOrFail($transmissionId));

        $attemptedAt = now();
        $document = $transmission->transmittable;
        if (! $document || (int) $document->company_id !== (int) $transmission->company_id) {
            return $this->finishWithoutTransport($transmission, 'document_scope_invalid', CbmsTransmission::STATUS_PERMANENT_FAILURE, $attemptedAt);
        }
        if (! $this->provenance->matches($transmission)) {
            return $this->finishWithoutTransport($transmission, 'transmission_provenance_mismatch', CbmsTransmission::STATUS_NOT_READY, $attemptedAt);
        }
        $built = $this->build($transmission, $document, $attemptedAt);
        if (($built['readiness']['status'] ?? null) !== 'READY' || ! is_array($built['payload'])) {
            return $this->finishWithoutTransport($transmission, 'not_ready', CbmsTransmission::STATUS_NOT_READY, $attemptedAt, $built['readiness'] ?? []);
        }

        $payload = $built['payload'];
        $hash = $this->evidence->payloadHash($payload);
        try {
            $result = $this->transport->send($transmission->endpoint_type, $payload);
        } catch (Throwable $exception) {
            $classification = str_contains(strtolower($exception->getMessage()), 'disabled') ? 'network_submission_disabled' : 'unexpected_exception';
            $result = CbmsTransportResult::failure($classification, $attemptedAt, $classification);
        }

        [$status, $category, $code] = $this->classify($transmission, $result);
        $isRealtime = (bool) ($payload['isrealtime'] ?? false);
        return DB::transaction(function () use ($transmission, $result, $status, $category, $code, $hash, $isRealtime) {
            $locked = CbmsTransmission::query()->lockForUpdate()->findOrFail($transmission->id);
            if ($locked->status !== CbmsTransmission::STATUS_PROCESSING) return new CbmsProcessingResult($locked);
            $number = (int) $locked->attempt_count + 1;
            CbmsTransmissionAttempt::create([
                'company_id' => $locked->company_id, 'cbms_transmission_id' => $locked->id,
                'attempt_number' => $number,
                'environment' => $locked->environment, 'transport_kind' => $locked->transport_kind,
                'attempted_at' => $result->attemptedAt, 'finished_at' => now(),
                'transport_classification' => $result->classification, 'http_status' => $result->httpStatus,
                'response_code' => $code, 'parser_classification' => $category,
                'response_excerpt_redacted' => $this->evidence->excerpt($result->body),
                'payload_hash' => $hash, 'is_realtime' => $isRealtime, 'result_status' => $status,
            ]);
            $updated = $this->states->move($locked, $status, [
                'attempt_count' => $number, 'last_attempted_at' => $result->attemptedAt,
                'submitted_at' => $status === CbmsTransmission::STATUS_SUBMITTED ? now() : null,
                'response_code' => $code, 'response_category' => $category,
                'response_body_redacted' => ['excerpt' => $this->evidence->excerpt($result->body)],
                'payload_hash' => $hash,
            ]);
            return new CbmsProcessingResult($updated, $status === CbmsTransmission::STATUS_RETRYABLE_FAILURE);
        });
    }

    private function build(CbmsTransmission $transmission, object $document, $attemptedAt): array
    {
        if ($transmission->endpoint_type === CbmsTransmission::ENDPOINT_BILL && $document instanceof SalesInvoice) {
            return $this->sales->build($document, (int) $transmission->company_id, $attemptedAt);
        }
        if ($transmission->endpoint_type === CbmsTransmission::ENDPOINT_BILL_RETURN && $document instanceof SalesReturn) {
            return $this->returns->build($document, (int) $transmission->company_id, $attemptedAt);
        }
        return ['readiness' => ['status' => 'NOT_READY', 'reason_codes' => ['INVALID_DOCUMENT_TYPE']], 'payload' => null];
    }

    private function classify(CbmsTransmission $transmission, CbmsTransportResult $result): array
    {
        if ($result->classification !== 'response') {
            return [CbmsTransmission::STATUS_RETRYABLE_FAILURE, $result->classification, null];
        }
        $http = $result->httpStatus ?? 0;
        if (in_array($http, [408, 429], true) || $http >= 500) return [CbmsTransmission::STATUS_RETRYABLE_FAILURE, 'http_'.$http, $result->responseCode];
        if ($http < 200 || $http >= 300) return [CbmsTransmission::STATUS_PERMANENT_FAILURE, 'http_'.$http, $result->responseCode];
        if ($result->responseCode === null || $result->responseCode === '') return [CbmsTransmission::STATUS_RETRYABLE_FAILURE, 'malformed_or_empty_response', null];
        $parsed = $transmission->endpoint_type === CbmsTransmission::ENDPOINT_BILL_RETURN
            ? $this->returnParser->parse($result->responseCode) : $this->salesParser->parse($result->responseCode);
        return [$parsed['status'], $parsed['category'], $parsed['code']];
    }

    private function finishWithoutTransport(CbmsTransmission $transmission, string $category, string $status, $attemptedAt, array $body = []): CbmsProcessingResult
    {
        return DB::transaction(function () use ($transmission, $category, $status, $attemptedAt, $body) {
            $locked = CbmsTransmission::query()->lockForUpdate()->findOrFail($transmission->id);
            if ($locked->status !== CbmsTransmission::STATUS_PROCESSING) return new CbmsProcessingResult($locked);
            $updated = $this->states->move($locked, $status, [
                'last_attempted_at' => $attemptedAt, 'response_category' => $category,
                'response_body_redacted' => $this->evidence->array($body),
            ]);
            return new CbmsProcessingResult($updated);
        });
    }
}
