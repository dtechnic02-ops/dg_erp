<?php

namespace App\Services;

use App\Models\Company;
use App\Models\FiscalDocumentAuditEvent;
use App\Models\SalesInvoice;
use App\Models\SalesReturn;
use App\Models\FinancialYear;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Builder;
use RuntimeException;

class FiscalDocumentPolicyService
{
    public const ISSUED_INVOICE_MUTATION_MESSAGE = 'This invoice has already been issued under IRD/CBMS mode and cannot be edited, deleted, or directly cancelled. Use the prescribed return/credit-note correction process.';
    public const ISSUED_CREDIT_NOTE_MUTATION_MESSAGE = 'This Credit Note has already been issued under IRD/CBMS mode and cannot be edited, deleted, or directly cancelled.';

    public function __construct(private readonly NepalIrdCbmsModeService $mode)
    {
    }

    public function isIssuedDocumentImmutableForCompany(Company $company): bool
    {
        return $this->mode->isActiveForCompany($company);
    }

    public function isSalesInvoiceImmutable(SalesInvoice $invoice): bool
    {
        return $this->wasFiscallyIssued($invoice);
    }

    public function wasFiscallyIssued(SalesInvoice $invoice): bool
    {
        if (! $invoice->exists) return false;

        return $invoice->fiscal_issued_at !== null
            || (Schema::hasTable('fiscal_document_audit_events') && FiscalDocumentAuditEvent::query()
                ->where('company_id', $invoice->company_id)
                ->where('document_type', 'sales_invoice')
                ->where('document_id', $invoice->getKey())
                ->where('event_type', 'invoice_issued')
                ->exists());
    }

    public function scopeFiscallyIssuedSales(Builder $query): Builder
    {
        if (! Schema::hasColumn('sales_invoices', 'fiscal_issued_at')
            && ! Schema::hasTable('fiscal_document_audit_events')) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $issued): void {
            if (Schema::hasColumn('sales_invoices', 'fiscal_issued_at')) {
                $issued->whereNotNull('fiscal_issued_at');
            }
            if (Schema::hasTable('fiscal_document_audit_events')) {
                $method = Schema::hasColumn('sales_invoices', 'fiscal_issued_at') ? 'orWhereExists' : 'whereExists';
                $issued->{$method}(function ($events): void {
                    $events->selectRaw('1')->from('fiscal_document_audit_events')
                        ->whereColumn('fiscal_document_audit_events.document_id', 'sales_invoices.id')
                        ->whereColumn('fiscal_document_audit_events.company_id', 'sales_invoices.company_id')
                        ->where('fiscal_document_audit_events.document_type', 'sales_invoice')
                        ->where('fiscal_document_audit_events.event_type', 'invoice_issued');
                });
            }
        });
    }

    public function companyHasFiscallyIssuedSalesInvoice(Company $company): bool
    {
        if (! Schema::hasColumn('sales_invoices', 'fiscal_issued_at')
            && ! Schema::hasTable('fiscal_document_audit_events')) return false;

        return SalesInvoice::query()->where('company_id', $company->id)
            ->where(function ($query) use ($company) {
                if (Schema::hasColumn('sales_invoices', 'fiscal_issued_at')) {
                    $query->whereNotNull('fiscal_issued_at');
                }
                if (Schema::hasTable('fiscal_document_audit_events')) {
                    $method = Schema::hasColumn('sales_invoices', 'fiscal_issued_at') ? 'orWhereExists' : 'whereExists';
                    $query->{$method}(function ($events) use ($company) {
                        $events->selectRaw('1')
                            ->from('fiscal_document_audit_events')
                            ->whereColumn('fiscal_document_audit_events.document_id', 'sales_invoices.id')
                            ->where('fiscal_document_audit_events.company_id', $company->id)
                            ->where('fiscal_document_audit_events.document_type', 'sales_invoice')
                            ->where('fiscal_document_audit_events.event_type', 'invoice_issued');
                    });
                }
            })
            ->exists();
    }

    public function financialYearHasPermanentFiscalHistory(FinancialYear $financialYear): bool
    {
        $invoices = SalesInvoice::query()
            ->where('company_id', $financialYear->company_id)
            ->where('financial_year_id', $financialYear->id);

        if ($this->scopeFiscallyIssuedSales($invoices)->exists()) {
            return true;
        }

        return Schema::hasColumn('sales_returns', 'fiscal_issued_at')
            && SalesReturn::query()
                ->where('company_id', $financialYear->company_id)
                ->where('financial_year_id', $financialYear->id)
                ->whereNotNull('fiscal_issued_at')
                ->exists();
    }

    public function companyHasPermanentFiscalHistory(Company $company): bool
    {
        if ($this->companyHasFiscallyIssuedSalesInvoice($company)) {
            return true;
        }

        if (Schema::hasColumn('sales_returns', 'fiscal_issued_at')
            && SalesReturn::query()->where('company_id', $company->id)->whereNotNull('fiscal_issued_at')->exists()) {
            return true;
        }

        return Schema::hasTable('fiscal_document_audit_events')
            && FiscalDocumentAuditEvent::query()->where('company_id', $company->id)->exists();
    }

    public function isSalesReturnImmutable(SalesReturn $return): bool
    {
        if (! $return->exists) return false;

        return $return->fiscal_issued_at !== null;
    }

    public function assertSalesInvoiceMayBeMutated(SalesInvoice $invoice): void
    {
        if ($this->isSalesInvoiceImmutable($invoice)) {
            throw new RuntimeException(self::ISSUED_INVOICE_MUTATION_MESSAGE);
        }
    }

    public function assertSalesReturnMayBeMutated(SalesReturn $return): void
    {
        if ($this->isSalesReturnImmutable($return)) {
            throw new RuntimeException(self::ISSUED_CREDIT_NOTE_MUTATION_MESSAGE);
        }
    }
}
