<?php

namespace App\Services;

use App\Models\Company;
use App\Models\FiscalDocumentAuditEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use LogicException;

class FiscalDocumentAuditService
{
    public function __construct(
        private readonly FiscalDocumentPolicyService $policy,
        private readonly SalesFiscalReconciliationService $reconciliation,
        private readonly SalesFiscalPaymentModeService $paymentModes,
    )
    {
    }

    public function recordIssued(Company $company, string $type, Model $document, string $number, ?int $actorId): void
    {
        if ($type === 'sales_return') {
            if (! $this->policy->isSalesReturnImmutable($document)) return;
        } elseif (! $this->policy->isIssuedDocumentImmutableForCompany($company)) {
            return;
        }
        $event = $type === 'sales_invoice' ? 'invoice_issued' : 'issued';
        $metadata = match ($type) {
            'sales_invoice' => $this->salesInvoiceIssuedMetadata($document),
            'sales_return' => $this->salesReturnIssuedMetadata($document),
            default => [],
        };
        $this->singleton($company, $type, $document, $number, $event, $actorId, $metadata);
    }

    public function recordBlockedSalesInvoiceAction(Model $invoice, string $action, ?int $actorId, ?string $context = null): void
    {
        $company = $invoice->company;
        if (! $company || ! $this->policy->wasFiscallyIssued($invoice)) return;

        $events = [
            'edit' => 'protected_update_blocked',
            'update' => 'protected_update_blocked',
            'cancel' => 'protected_cancel_blocked',
            'delete' => 'protected_delete_blocked',
            'force_delete' => 'protected_delete_blocked',
            'company_delete' => 'protected_company_deletion_blocked',
        ];
        if (! isset($events[$action])) throw new LogicException('Unsupported protected fiscal action.');

        $this->append($company, 'sales_invoice', $invoice, (string) $invoice->invoice_no, $events[$action], $actorId, array_filter([
            'attempted_action' => $action,
            'context' => $context,
            'reason_code' => 'issued_fiscal_document_immutable',
            'original_preserved' => true,
        ], fn ($value) => $value !== null));
    }

    public function recordSalesReturnCreated(Model $invoice, Model $salesReturn, ?int $actorId): void
    {
        if ((int) $invoice->company_id !== (int) $salesReturn->company_id
            || (int) $salesReturn->sales_invoice_id !== (int) $invoice->getKey()) {
            throw new LogicException('Fiscal Sales Return linkage must remain within the original invoice company.');
        }
        $company = $invoice->company;
        if (! $company || ! $this->policy->wasFiscallyIssued($invoice)) return;

        FiscalDocumentAuditEvent::create($this->data($company, 'sales_invoice', $invoice, (string) $invoice->invoice_no, 'sales_return_created', $actorId, [
            'sales_return_id' => $salesReturn->getKey(),
            'sales_return_number' => $salesReturn->return_no,
            'fiscal_issued_at' => $salesReturn->fiscal_issued_at?->toISOString(),
            'original_invoice_id' => $invoice->getKey(),
            'original_invoice_number' => $invoice->invoice_no,
            'reason' => $salesReturn->note,
            'fiscal_reconciliation' => $this->returnFiscalTotals($salesReturn),
            'returned_amount' => $salesReturn->grand_total,
            'returned_vat' => $salesReturn->total_vat,
            'original_preserved' => true,
        ]) + ['deduplication_key' => implode(':', [$company->id, 'sales_invoice', $invoice->getKey(), 'sales_return_created', $salesReturn->getKey()])]);
    }

    public function recordCancelled(Company $company, string $type, Model $document, string $number, int $actorId, string $reason): void
    {
        if (! $this->isPermanentlyFiscal($type, $document)
            && ! $this->policy->isIssuedDocumentImmutableForCompany($company)) return;
        $this->singleton($company, $type, $document, $number, 'cancelled', $actorId, ['reason' => $reason, 'status' => 'cancelled']);
    }

    public function recordPrint(Company $company, string $type, Model $document, string $number, int $actorId): string
    {
        if (! $this->isPermanentlyFiscal($type, $document)) return '';

        return DB::transaction(function () use ($company, $type, $document, $number, $actorId): string {
            $document->newQuery()->whereKey($document->getKey())->lockForUpdate()->firstOrFail();

            if ($type === 'sales_invoice') {
                return $this->recordSalesInvoicePrint($company, $document, $number, $actorId);
            }

            return $this->recordSalesReturnPrint($company, $document, $number, $actorId);
        });
    }

    private function recordSalesReturnPrint(Company $company, Model $document, string $number, int $actorId): string
    {
        $history = FiscalDocumentAuditEvent::query()
            ->where('company_id', $company->id)
            ->where('document_type', 'sales_return')
            ->where('document_id', $document->getKey());

        if (! (clone $history)->where('event_type', 'original_printed')->exists()) {
            $this->singleton($company, 'sales_return', $document, $number, 'original_printed', $actorId, [
                'print_number' => 0, 'print_kind' => 'original',
            ]);
            return 'Original';
        }

        $copyNumber = (clone $history)->where('event_type', 'reprinted')->count() + 1;
        $this->append($company, 'sales_return', $document, $number, 'reprinted', $actorId, [
            'print_number' => $copyNumber, 'print_kind' => 'copy_of_original',
        ]);
        return "Copy of Original ({$copyNumber})";
    }

    private function recordSalesInvoicePrint(Company $company, Model $document, string $number, int $actorId): string
    {
        $history = FiscalDocumentAuditEvent::query()
            ->where('company_id', $company->id)
            ->where('document_type', 'sales_invoice')
            ->where('document_id', $document->getKey());

        if (! (clone $history)->where('event_type', 'original_printed')->exists()) {
            FiscalDocumentAuditEvent::create($this->data(
                $company,
                'sales_invoice',
                $document,
                $number,
                'original_printed',
                $actorId,
                ['print_number' => 0, 'print_kind' => 'original']
            ) + ['deduplication_key' => implode(':', [$company->id, 'sales_invoice', $document->getKey(), 'original_printed'])]);

            return 'Original';
        }

        $copyNumber = (clone $history)->where('event_type', 'reprinted')->count() + 1;
        FiscalDocumentAuditEvent::create($this->data(
            $company,
            'sales_invoice',
            $document,
            $number,
            'reprinted',
            $actorId,
            ['print_number' => $copyNumber, 'print_kind' => 'copy_of_original']
        ) + ['deduplication_key' => implode(':', [$company->id, 'sales_invoice', $document->getKey(), 'reprinted', $copyNumber])]);

        return "Copy of Original ({$copyNumber})";
    }

    public function history(Company $company, string $type, Model $document)
    {
        return FiscalDocumentAuditEvent::with('actor')->where('company_id', $company->id)
            ->where('document_type', $type)->where('document_id', $document->getKey())
            ->orderBy('event_at')->orderBy('id')->get();
    }

    private function singleton(Company $company, string $type, Model $document, string $number, string $event, ?int $actorId, array $metadata): FiscalDocumentAuditEvent
    {
        return FiscalDocumentAuditEvent::firstOrCreate(
            ['deduplication_key' => implode(':', [$company->id, $type, $document->getKey(), $event])],
            $this->data($company, $type, $document, $number, $event, $actorId, $metadata)
        );
    }

    private function append(Company $company, string $type, Model $document, string $number, string $event, ?int $actorId, array $metadata): void
    {
        FiscalDocumentAuditEvent::create($this->data($company, $type, $document, $number, $event, $actorId, $metadata));
    }

    private function data(Company $company, string $type, Model $document, string $number, string $event, ?int $actorId, array $metadata): array
    {
        return ['company_id' => $company->id, 'document_type' => $type, 'document_id' => $document->getKey(), 'document_number' => $number, 'event_type' => $event, 'actor_id' => $actorId, 'event_at' => now(), 'metadata' => $metadata];
    }

    private function salesInvoiceIssuedMetadata(Model $invoice): array
    {
        $reconciliation = $this->reconciliation->reconcile($invoice);
        $fiscalTotals = collect($reconciliation)->only([
            'gross_sales', 'total_discount', 'net_sales', 'taxable_sales', 'exempt_sales',
            'zero_rated_sales', 'export_sales', 'out_of_scope_sales', 'vat_total',
            'grand_total', 'is_reconciled',
        ])->all();

        return [
            'financial_year_id' => $invoice->financial_year_id,
            'invoice_date' => $invoice->sale_date?->format('Y-m-d'),
            'fiscal_issued_at' => $invoice->fiscal_issued_at?->toISOString(),
            'grand_total' => $invoice->grand_total,
            'vat_total' => $invoice->total_vat,
            'fiscal_reconciliation' => $fiscalTotals,
            'snapshot_complete' => app(SalesFiscalSnapshotService::class)->isComplete($invoice),
            'hs_compliance_complete' => app(SalesFiscalSnapshotService::class)
                ->itemDetailComplianceErrors($invoice) === [],
            'fiscal_payment_mode' => $invoice->fiscal_payment_mode,
            'fiscal_payment_presentation' => $this->paymentModes->irdPresentation($invoice->fiscal_payment_mode),
            'result' => 'succeeded',
            'original_preserved' => true,
        ];
    }

    private function salesReturnIssuedMetadata(Model $return): array
    {
        $return->loadMissing('invoice');

        return [
            'sales_return_id' => $return->getKey(),
            'return_no' => $return->return_no,
            'fiscal_issued_at' => $return->fiscal_issued_at?->toISOString(),
            'original_invoice_id' => $return->sales_invoice_id,
            'original_invoice_number' => $return->invoice?->invoice_no,
            'reason' => $return->note,
            'fiscal_reconciliation' => $this->returnFiscalTotals($return),
            'result' => 'succeeded',
            'original_preserved' => true,
        ];
    }

    private function returnFiscalTotals(Model $return): array
    {
        return collect($this->reconciliation->reconcileReturn($return))->only([
            'gross_sales', 'total_discount', 'net_sales', 'taxable_sales', 'exempt_sales',
            'zero_rated_sales', 'export_sales', 'out_of_scope_sales', 'vat_total',
            'grand_total', 'is_reconciled',
        ])->all();
    }

    private function isPermanentlyFiscal(string $type, Model $document): bool
    {
        return match ($type) {
            'sales_invoice' => $this->policy->wasFiscallyIssued($document),
            'sales_return' => $this->policy->isSalesReturnImmutable($document),
            default => false,
        };
    }
}
