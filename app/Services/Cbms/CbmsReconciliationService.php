<?php

namespace App\Services\Cbms;

use App\Models\CbmsTransmission;
use App\Models\SalesInvoice;
use App\Models\SalesReturn;
use Illuminate\Support\Facades\DB;
use LogicException;

class CbmsReconciliationService
{
    public function __construct(
        private readonly CbmsReconciliationVerifier $verifier,
        private readonly CbmsTransmissionStateMachine $states,
    ) {}

    public function reconcile(CbmsTransmission $transmission): CbmsTransmission
    {
        if ($transmission->status !== CbmsTransmission::STATUS_DUPLICATE_REQUIRES_RECONCILIATION) {
            throw new LogicException('Only duplicate CBMS responses can be reconciled.');
        }
        $result = $this->verifier->verify($transmission->loadMissing('transmittable.financialYear'));
        if (! $result->verified || ! $this->exactMatch($transmission, $result->evidence)) return $transmission->refresh();

        return DB::transaction(function () use ($transmission, $result) {
            $locked = CbmsTransmission::query()->lockForUpdate()->findOrFail($transmission->id);
            return $this->states->move($locked, CbmsTransmission::STATUS_SUBMITTED, [
                'submitted_at' => now(), 'response_category' => 'duplicate_verified_exact_match',
                'response_body_redacted' => ['reconciliation' => $result->reason],
            ], true);
        });
    }

    private function exactMatch(CbmsTransmission $transmission, array $evidence): bool
    {
        $document = $transmission->transmittable;
        $invoice = $document instanceof SalesReturn ? $document->invoice : $document;
        $number = $document instanceof SalesReturn ? $document->return_no : $document->invoice_no;
        $type = $document instanceof SalesReturn ? CbmsTransmission::ENDPOINT_BILL_RETURN : CbmsTransmission::ENDPOINT_BILL;
        $fy = app(CbmsFiscalYearFormatter::class)->format($document->financialYear);
        $required = [
            'seller_pan' => (string) $invoice->seller_pan_snapshot,
            'fiscal_year' => $fy, 'document_number' => (string) $number,
            'document_type' => $type, 'total_amount' => round((float) $document->grand_total, 2),
            'payload_hash' => (string) $transmission->payload_hash,
        ];
        foreach ($required as $key => $expected) {
            if (! array_key_exists($key, $evidence)) return false;
            if ($key === 'total_amount' ? round((float) $evidence[$key], 2) !== $expected : (string) $evidence[$key] !== (string) $expected) return false;
        }
        return true;
    }
}
