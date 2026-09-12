<?php

namespace App\Services\Cbms;

use App\Models\CompanyCbmsApiConfiguration;
use App\Models\CbmsTransmission;
use App\Models\SalesInvoice;
use App\Models\SalesReturn;
use App\Services\SalesFiscalReconciliationService;
use App\Services\SalesTaxClassificationService;
use App\Services\NepalIrdCbmsModeService;
use Throwable;

class CbmsReadinessService
{
    public const WRONG_COMPANY_SCOPE = 'WRONG_COMPANY_SCOPE';
    public const NOT_NEPAL_FISCAL_DOCUMENT = 'NOT_NEPAL_FISCAL_DOCUMENT';
    public const CBMS_MODE_NOT_ACTIVE = 'CBMS_MODE_NOT_ACTIVE';
    public const NOT_FISCALLY_ISSUED = 'NOT_FISCALLY_ISSUED';
    public const MISSING_SELLER_PAN = 'MISSING_SELLER_PAN';
    public const MISSING_BUYER_EVIDENCE = 'MISSING_BUYER_EVIDENCE';
    public const MISSING_CBMS_CONFIGURATION = 'MISSING_CBMS_CONFIGURATION';
    public const MISSING_CBMS_CREDENTIALS = 'MISSING_CBMS_CREDENTIALS';
    public const UNRESOLVED_FISCAL_YEAR = 'UNRESOLVED_FISCAL_YEAR';
    public const UNRESOLVED_CBMS_DATE = 'UNRESOLVED_CBMS_DATE';
    public const UNRESOLVED_CBMS_TAX_CLASSIFICATION = 'UNRESOLVED_CBMS_TAX_CLASSIFICATION';
    public const FISCAL_RECONCILIATION_FAILED = 'FISCAL_RECONCILIATION_FAILED';
    public const MISSING_RETURN_REASON = 'MISSING_RETURN_REASON';
    public const ORIGINAL_BILL_NOT_CONFIRMED = 'ORIGINAL_BILL_NOT_CONFIRMED';

    public function __construct(
        private readonly SalesFiscalReconciliationService $reconciliation,
        private readonly CbmsFiscalYearFormatter $fiscalYears,
        private readonly CbmsDateFormatter $dates,
        private readonly NepalIrdCbmsModeService $mode,
    ) {}

    public function forInvoice(SalesInvoice $invoice, int $companyId): CbmsReadinessResult
    {
        return $this->evaluate($invoice, $companyId, false);
    }

    public function forReturn(SalesReturn $return, int $companyId): CbmsReadinessResult
    {
        return $this->evaluate($return, $companyId, true);
    }

    private function evaluate(SalesInvoice|SalesReturn $document, int $companyId, bool $isReturn): CbmsReadinessResult
    {
        $document->loadMissing(['company.countryMaster', 'financialYear', 'items']);
        $reasons = [];
        if ((int) $document->company_id !== $companyId) $reasons[] = self::WRONG_COMPANY_SCOPE;
        if ($document->company?->countryMaster?->iso_code !== 'NP') $reasons[] = self::NOT_NEPAL_FISCAL_DOCUMENT;
        if (! $document->company || ! $this->mode->isActiveForCompany($document->company)) $reasons[] = self::CBMS_MODE_NOT_ACTIVE;
        if (!$document->fiscal_issued_at) $reasons[] = self::NOT_FISCALLY_ISSUED;

        $invoice = $isReturn ? $document->invoice : $document;
        if (!$invoice || blank($invoice->seller_pan_snapshot)) $reasons[] = self::MISSING_SELLER_PAN;
        if (!$invoice || blank($invoice->buyer_name_snapshot)) $reasons[] = self::MISSING_BUYER_EVIDENCE;
        if ($isReturn && blank($document->note)) $reasons[] = self::MISSING_RETURN_REASON;
        if ($isReturn && $invoice) {
            $confirmed = CbmsTransmission::query()
                ->where('company_id', $document->company_id)
                ->where('transmittable_type', $invoice->getMorphClass())
                ->where('transmittable_id', $invoice->getKey())
                ->where('endpoint_type', CbmsTransmission::ENDPOINT_BILL)
                ->where('status', CbmsTransmission::STATUS_SUBMITTED)->exists();
            if (! $confirmed) $reasons[] = self::ORIGINAL_BILL_NOT_CONFIRMED;
        }

        $configuration = CompanyCbmsApiConfiguration::where('company_id', $document->company_id)->first();
        if (!$configuration) {
            $reasons[] = self::MISSING_CBMS_CONFIGURATION;
        } elseif (blank($configuration->client_identifier) || !$this->hasCredential($configuration)) {
            $reasons[] = self::MISSING_CBMS_CREDENTIALS;
        }

        try { $this->fiscalYears->format($document->financialYear); } catch (Throwable) { $reasons[] = self::UNRESOLVED_FISCAL_YEAR; }
        try { $this->dates->format($isReturn ? $document->return_date : $document->sale_date); } catch (Throwable) { $reasons[] = self::UNRESOLVED_CBMS_DATE; }

        $result = $isReturn ? $this->reconciliation->reconcileReturn($document) : $this->reconciliation->reconcile($document);
        if (!$result['is_reconciled']) $reasons[] = self::FISCAL_RECONCILIATION_FAILED;
        if ($this->hasUnsupportedClassification($document->items)) $reasons[] = self::UNRESOLVED_CBMS_TAX_CLASSIFICATION;

        return new CbmsReadinessResult(array_values(array_unique($reasons)));
    }

    private function hasCredential(CompanyCbmsApiConfiguration $configuration): bool
    {
        try { return filled($configuration->encrypted_credential); } catch (Throwable) { return false; }
    }

    private function hasUnsupportedClassification(iterable $items): bool
    {
        foreach ($items as $item) {
            if (in_array($item->tax_classification, [SalesTaxClassificationService::ZERO_RATED, SalesTaxClassificationService::OUT_OF_SCOPE, SalesTaxClassificationService::LEGACY_UNCLASSIFIED], true)) return true;
        }
        return false;
    }
}
