<?php

namespace App\Services\Cbms;

use App\Jobs\TransmitCbmsDocumentJob;
use App\Models\CbmsTransmission;
use App\Models\SalesInvoice;
use App\Models\SalesReturn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use LogicException;

class CbmsQueueService
{
    public function __construct(
        private readonly CbmsSalesBillPayloadBuilder $sales,
        private readonly CbmsBillReturnPayloadBuilder $returns,
        private readonly CbmsTransmissionService $transmissions,
        private readonly CbmsTransmissionStateMachine $states,
    ) {}

    public function queueAfterCommit(Model $document): void
    {
        $type = $document::class;
        $id = $document->getKey();
        DB::afterCommit(function () use ($type, $id): void {
            $fresh = $type::query()->find($id);
            if ($fresh) $this->queue($fresh);
        });
    }

    public function queue(Model $document): CbmsTransmission
    {
        [$endpoint, $built] = $this->build($document, now());
        $readiness = new CbmsReadinessResult($built['readiness']['reason_codes'] ?? []);
        $transmission = $this->transmissions->record($document, $endpoint, $built['payload'] ?? [], $readiness);

        return $this->queueTransmission($transmission, $readiness);
    }

    public function retry(CbmsTransmission $transmission): CbmsTransmission
    {
        if ($transmission->status !== CbmsTransmission::STATUS_RETRYABLE_FAILURE) {
            throw new LogicException('Only retryable CBMS failures may be retried.');
        }

        [$endpoint, $built] = $this->build($transmission->transmittable, now());
        if ($endpoint !== $transmission->endpoint_type) {
            throw new LogicException('CBMS retry endpoint does not match the frozen transmission.');
        }

        $readiness = new CbmsReadinessResult($built['readiness']['reason_codes'] ?? []);

        return $this->queueTransmission($transmission, $readiness);
    }

    private function queueTransmission(CbmsTransmission $transmission, CbmsReadinessResult $readiness): CbmsTransmission
    {

        $dispatch = false;
        $transmission = DB::transaction(function () use ($transmission, $readiness, &$dispatch) {
            $locked = CbmsTransmission::query()->lockForUpdate()->findOrFail($transmission->id);
            if (! $readiness->isReady()) {
                if ($locked->status === CbmsTransmission::STATUS_PENDING) {
                    return $this->states->move($locked, CbmsTransmission::STATUS_NOT_READY, ['response_body_redacted' => $readiness->toArray()]);
                }
                if ($locked->status === CbmsTransmission::STATUS_NOT_READY) {
                    $locked->forceFill(['response_body_redacted' => $readiness->toArray()])->save();
                    return $locked->refresh();
                }
                return $locked;
            }
            if (in_array($locked->status, [CbmsTransmission::STATUS_PENDING, CbmsTransmission::STATUS_NOT_READY, CbmsTransmission::STATUS_RETRYABLE_FAILURE], true)) {
                $dispatch = true;
                return $this->states->move($locked, CbmsTransmission::STATUS_QUEUED, ['response_body_redacted' => null]);
            }
            return $locked;
        });

        if ($dispatch) TransmitCbmsDocumentJob::dispatch($transmission->id)->afterCommit();
        return $transmission->refresh();
    }

    private function build(Model $document, $attemptedAt): array
    {
        if ($document instanceof SalesInvoice) return [CbmsTransmission::ENDPOINT_BILL, $this->sales->build($document, (int) $document->company_id, $attemptedAt)];
        if ($document instanceof SalesReturn) return [CbmsTransmission::ENDPOINT_BILL_RETURN, $this->returns->build($document, (int) $document->company_id, $attemptedAt)];
        throw new LogicException('Unsupported CBMS document type.');
    }
}
