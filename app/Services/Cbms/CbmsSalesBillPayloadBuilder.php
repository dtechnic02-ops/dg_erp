<?php

namespace App\Services\Cbms;

use App\Models\CompanyCbmsApiConfiguration;
use App\Models\SalesInvoice;
use App\Services\SalesFiscalReconciliationService;
use DateTimeInterface;

class CbmsSalesBillPayloadBuilder
{
    public function __construct(
        private readonly CbmsReadinessService $readiness,
        private readonly SalesFiscalReconciliationService $reconciliation,
        private readonly CbmsFiscalYearFormatter $fiscalYears,
        private readonly CbmsDateFormatter $dates,
        private readonly CbmsRealtimeClassifier $realtime,
        private readonly CbmsTaxAmountMapper $taxes,
    ) {}

    public function build(SalesInvoice $invoice, int $companyId, DateTimeInterface $attemptedAt): array
    {
        $readiness = $this->readiness->forInvoice($invoice, $companyId);
        if (!$readiness->isReady()) return ['readiness' => $readiness->toArray(), 'payload' => null];

        $invoice->loadMissing(['financialYear']);
        $configuration = CompanyCbmsApiConfiguration::where('company_id', $invoice->company_id)->sole();
        $values = $this->reconciliation->reconcile($invoice);
        $payload = [
            'username' => $configuration->client_identifier,
            'password' => $configuration->encrypted_credential,
            'seller_pan' => $invoice->seller_pan_snapshot,
            'buyer_pan' => $invoice->buyer_tax_no_snapshot,
            'fiscal_year' => $this->fiscalYears->format($invoice->financialYear),
            'buyer_name' => $invoice->buyer_name_snapshot,
            'invoice_number' => $invoice->invoice_no,
            'invoice_date' => $this->dates->format($invoice->sale_date),
        ] + $this->taxes->map($values) + [
            'isrealtime' => $this->realtime->isRealtime($invoice->fiscal_issued_at, $attemptedAt),
            'datetimeClient' => $this->realtime->clientDateTime($attemptedAt),
        ];

        return ['readiness' => $readiness->toArray(), 'payload' => $payload];
    }
}
