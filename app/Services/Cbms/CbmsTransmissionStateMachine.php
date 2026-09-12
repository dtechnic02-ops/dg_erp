<?php

namespace App\Services\Cbms;

use App\Models\CbmsTransmission;
use LogicException;

class CbmsTransmissionStateMachine
{
    private const ALLOWED = [
        CbmsTransmission::STATUS_PENDING => [CbmsTransmission::STATUS_NOT_READY, CbmsTransmission::STATUS_QUEUED],
        CbmsTransmission::STATUS_NOT_READY => [CbmsTransmission::STATUS_QUEUED],
        CbmsTransmission::STATUS_QUEUED => [CbmsTransmission::STATUS_PROCESSING, CbmsTransmission::STATUS_NOT_READY],
        CbmsTransmission::STATUS_PROCESSING => [
            CbmsTransmission::STATUS_NOT_READY, CbmsTransmission::STATUS_SUBMITTED,
            CbmsTransmission::STATUS_DUPLICATE_REQUIRES_RECONCILIATION,
            CbmsTransmission::STATUS_RETRYABLE_FAILURE, CbmsTransmission::STATUS_PERMANENT_FAILURE,
        ],
        CbmsTransmission::STATUS_RETRYABLE_FAILURE => [CbmsTransmission::STATUS_QUEUED],
        CbmsTransmission::STATUS_DUPLICATE_REQUIRES_RECONCILIATION => [CbmsTransmission::STATUS_SUBMITTED],
    ];

    public function move(CbmsTransmission $transmission, string $to, array $attributes = [], bool $verifiedReconciliation = false): CbmsTransmission
    {
        $from = (string) $transmission->status;
        if (! in_array($to, self::ALLOWED[$from] ?? [], true)) {
            throw new LogicException("Invalid CBMS transmission transition: {$from} -> {$to}.");
        }
        if ($from === CbmsTransmission::STATUS_DUPLICATE_REQUIRES_RECONCILIATION
            && $to === CbmsTransmission::STATUS_SUBMITTED && ! $verifiedReconciliation) {
            throw new LogicException('Duplicate CBMS evidence requires verifier-confirmed reconciliation.');
        }

        $transmission->forceFill(['status' => $to] + $attributes)->save();
        return $transmission->refresh();
    }
}
