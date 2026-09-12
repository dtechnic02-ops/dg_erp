<?php

namespace App\Services;

use App\Models\SalesInvoice;

class SalesFiscalReadinessService
{
    public function __construct(
        private readonly NepalIrdCbmsModeService $mode,
        private readonly SalesTaxClassificationService $taxClassifications,
        private readonly SalesFiscalSnapshotService $snapshots,
        private readonly SalesFiscalPaymentModeService $paymentModes,
        private readonly SalesFiscalIssueDateTimeService $issueDateTime,
        private readonly SalesFiscalReconciliationService $reconciliation,
    ) {
    }

    public function errors(SalesInvoice $invoice): array
    {
        $invoice->loadMissing(['company', 'items']);
        $summary = $this->taxClassifications->summarize($invoice->items);
        $errors = [];

        $isFiscalMode = $invoice->company && $this->mode->isActiveForCompany($invoice->company);

        if ($isFiscalMode) {
            if (! $this->snapshots->isComplete($invoice)) {
                $errors[] = 'The sales invoice has no complete immutable fiscal identity snapshot.';
            }
            $errors = array_merge($errors, $this->paymentModes->readinessErrors($invoice));
            if (! $this->issueDateTime->isComplete($invoice)) {
                $errors[] = 'The sales invoice has no immutable fiscal issue timestamp.';
            }
            $errors = array_merge($errors, $this->snapshots->itemDetailComplianceErrors($invoice));
            $errors = array_merge($errors, $this->reconciliation->reconcile($invoice)['errors']);
        }

        if (! $isFiscalMode && (float) $invoice->discount != 0.0) {
            $errors[] = 'IRD/CBMS submission is currently unavailable for invoices containing an invoice-level discount because the approved taxable/exempt discount allocation method is not configured.';
        }
        if ($invoice->items->contains(fn ($item) => ! $item->tax_classification
            || $item->tax_classification === SalesTaxClassificationService::LEGACY_UNCLASSIFIED)) {
            $errors[] = 'One or more sales lines have no authoritative tax classification.';
        }
        if ($invoice->items->contains(fn ($item) => in_array($item->tax_classification, [
            SalesTaxClassificationService::ZERO_RATED,
            SalesTaxClassificationService::OUT_OF_SCOPE,
        ], true))) {
            $errors[] = 'Zero Rated and Out of Scope sales are not mapped to an official IRD CBMS Version 1 payload bucket.';
        }

        if (! $isFiscalMode) {
            $reconciled = round(
                $summary['taxable_sales_vat'] + $summary['tax_exempted_sales']
                + $summary['export_sales'] + (float) $invoice->total_vat,
                2
            );
            if (abs($reconciled - (float) $invoice->grand_total) > 0.01) {
                $errors[] = 'Canonical sales tax bases and VAT do not reconcile to the invoice grand total.';
            }
        }

        return $errors;
    }

    public function isReady(SalesInvoice $invoice): bool
    {
        return $this->errors($invoice) === [];
    }

    public function fiscalPrintErrors(SalesInvoice $invoice): array
    {
        $invoice->loadMissing(['company', 'items']);

        $errors = [];
        if (! $this->snapshots->isComplete($invoice)) {
            $errors[] = 'The sales invoice has no complete immutable fiscal identity snapshot.';
        }
        $errors = array_merge($errors, $this->paymentModes->readinessErrors($invoice));
        if (! $this->issueDateTime->isComplete($invoice)) {
            $errors[] = 'The sales invoice has no immutable fiscal issue timestamp.';
        }
        $errors = array_merge($errors, $this->snapshots->itemDetailComplianceErrors($invoice));
        $errors = array_merge($errors, $this->reconciliation->reconcile($invoice)['errors']);

        return array_values(array_unique($errors));
    }

}
