<?php

namespace App\Services\Cbms;

use App\Models\CompanyCbmsApiConfiguration;
use App\Models\SalesReturn;
use App\Services\SalesFiscalReconciliationService;
use DateTimeInterface;

class CbmsBillReturnPayloadBuilder
{
    public function __construct(
        private readonly CbmsReadinessService $readiness,
        private readonly SalesFiscalReconciliationService $reconciliation,
        private readonly CbmsFiscalYearFormatter $fiscalYears,
        private readonly CbmsDateFormatter $dates,
        private readonly CbmsRealtimeClassifier $realtime,
        private readonly CbmsTaxAmountMapper $taxes,
    ) {}

    public function build(SalesReturn $return, int $companyId, DateTimeInterface $attemptedAt): array
    {
        $readiness = $this->readiness->forReturn($return, $companyId);
        if (!$readiness->isReady()) return ['readiness' => $readiness->toArray(), 'payload' => null];

        $return->loadMissing(['invoice', 'financialYear']);
        $configuration = CompanyCbmsApiConfiguration::where('company_id', $return->company_id)->sole();
        $values = $this->reconciliation->reconcileReturn($return);
        $payload = [
            'username' => $configuration->client_identifier,
            'password' => $configuration->encrypted_credential,
            'seller_pan' => $return->invoice->seller_pan_snapshot,
            'buyer_pan' => $return->invoice->buyer_tax_no_snapshot,
            'fiscal_year' => $this->fiscalYears->format($return->financialYear),
            'buyer_name' => $return->invoice->buyer_name_snapshot,
            'ref_invoice_number' => $return->invoice->invoice_no,
            'credit_note_number' => $return->return_no,
            'credit_note_date' => $this->dates->format($return->return_date),
            'reason_for_return' => trim((string) $return->note),
        ] + $this->taxes->map($values) + [
            'isrealtime' => $this->realtime->isRealtime($return->fiscal_issued_at, $attemptedAt),
            'datetimeClient' => $this->realtime->clientDateTime($attemptedAt),
        ];

        return ['readiness' => $readiness->toArray(), 'payload' => $payload];
    }
}
