<?php

namespace App\Services;

use App\Models\SalesInvoice;
use App\Models\SalesReturn;
use Illuminate\Support\Collection;

class SalesFiscalReportService
{
    private const AMOUNT_KEYS = [
        'gross_sales', 'total_discount', 'net_sales', 'taxable_sales', 'exempt_sales',
        'zero_rated_sales', 'export_sales', 'out_of_scope_sales', 'vat_total', 'grand_total',
    ];

    public function __construct(
        private readonly FiscalDocumentPolicyService $policy,
        private readonly SalesFiscalReconciliationService $reconciliation,
        private readonly SalesFiscalReadinessService $readiness,
    ) {
    }

    public function report(int $companyId, ?int $financialYearId = null, ?string $fromDate = null, ?string $toDate = null): array
    {
        $invoiceQuery = SalesInvoice::query()
            ->with(['items', 'financialYear', 'company'])
            ->where('company_id', $companyId);
        $invoices = $this->policy->scopeFiscallyIssuedSales($invoiceQuery)
            ->when($financialYearId, fn ($query) => $query->where('financial_year_id', $financialYearId))
            ->when($fromDate, fn ($query) => $query->whereDate('sale_date', '>=', $fromDate))
            ->when($toDate, fn ($query) => $query->whereDate('sale_date', '<=', $toDate))
            ->orderBy('sale_date')->orderBy('id')->get();

        $returns = SalesReturn::query()
            ->with(['items', 'financialYear', 'invoice.items', 'invoice.company'])
            ->where('company_id', $companyId)
            ->whereNotNull('fiscal_issued_at')
            ->whereHas('invoice', function ($query) use ($companyId): void {
                $query->where('company_id', $companyId);
                $this->policy->scopeFiscallyIssuedSales($query);
            })
            ->when($financialYearId, fn ($query) => $query->where('financial_year_id', $financialYearId))
            ->when($fromDate, fn ($query) => $query->whereDate('return_date', '>=', $fromDate))
            ->when($toDate, fn ($query) => $query->whereDate('return_date', '<=', $toDate))
            ->orderBy('return_date')->orderBy('id')->get();

        $salesRows = $invoices->map(fn (SalesInvoice $invoice) => $this->invoiceRow($invoice))->values();
        $returnRows = $returns->map(fn (SalesReturn $return) => $this->returnRow($return))->values();

        $sales = $this->totals($salesRows);
        $creditNotes = $this->totals($returnRows);

        return [
            'sales' => $salesRows,
            'credit_notes' => $returnRows,
            'summary' => [
                'sales' => $sales,
                'returns' => $creditNotes,
                'net' => [
                    'taxable_sales' => round($sales['taxable_sales'] - $creditNotes['taxable_sales'], 2),
                    'exempt_sales' => round($sales['exempt_sales'] - $creditNotes['exempt_sales'], 2),
                    'zero_rated_sales' => round($sales['zero_rated_sales'] - $creditNotes['zero_rated_sales'], 2),
                    'export_sales' => round($sales['export_sales'] - $creditNotes['export_sales'], 2),
                    'out_of_scope_sales' => round($sales['out_of_scope_sales'] - $creditNotes['out_of_scope_sales'], 2),
                    'vat_total' => round($sales['vat_total'] - $creditNotes['vat_total'], 2),
                    'grand_total' => round($sales['grand_total'] - $creditNotes['grand_total'], 2),
                ],
                'not_ready_count' => $salesRows->where('is_ready', false)->count()
                    + $returnRows->where('is_ready', false)->count(),
            ],
        ];
    }

    private function invoiceRow(SalesInvoice $invoice): array
    {
        $values = $this->reconciliation->reconcile($invoice);
        $errors = $this->readiness->fiscalPrintErrors($invoice);

        return $this->baseAmounts($values, $errors) + [
            'id' => $invoice->id,
            'financial_year' => $invoice->financialYear?->name,
            'transaction_date' => $invoice->sale_date?->format('Y-m-d'),
            'fiscal_issue_date' => $invoice->fiscal_issued_at?->copy()->timezone(SalesFiscalIssueDateTimeService::NEPAL_TIMEZONE)->format('Y-m-d H:i:s'),
            'document_no' => $invoice->invoice_no,
            'buyer_name' => $invoice->buyer_name_snapshot,
            'buyer_pan' => $invoice->buyer_tax_no_snapshot,
            'payment_mode' => $invoice->fiscal_payment_mode,
            'fiscal_status' => 'Issued',
        ];
    }

    private function returnRow(SalesReturn $return): array
    {
        $values = $this->reconciliation->reconcileReturn($return);
        $errors = $values['errors'];
        $invoice = $return->invoice;
        if (empty($invoice?->buyer_name_snapshot)) {
            $errors[] = 'The original fiscal invoice has no frozen buyer identity.';
        }

        return $this->baseAmounts($values, array_values(array_unique($errors))) + [
            'id' => $return->id,
            'financial_year' => $return->financialYear?->name,
            'transaction_date' => $return->return_date?->format('Y-m-d'),
            'fiscal_issue_date' => $return->fiscal_issued_at?->copy()->timezone(SalesFiscalIssueDateTimeService::NEPAL_TIMEZONE)->format('Y-m-d H:i:s'),
            'document_no' => $return->return_no,
            'original_invoice_no' => $invoice?->invoice_no,
            'original_invoice_date' => $invoice?->sale_date?->format('Y-m-d'),
            'buyer_name' => $invoice?->buyer_name_snapshot,
            'buyer_pan' => $invoice?->buyer_tax_no_snapshot,
            'reason' => $return->note,
        ];
    }

    private function baseAmounts(array $values, array $errors): array
    {
        $ready = $errors === [] && $values['is_reconciled'];
        $row = ['is_ready' => $ready, 'errors' => $errors];
        foreach (self::AMOUNT_KEYS as $key) {
            $row[$key] = $ready ? (float) $values[$key] : null;
        }
        return $row;
    }

    private function totals(Collection $rows): array
    {
        $totals = array_fill_keys(self::AMOUNT_KEYS, 0.0);
        foreach ($rows->where('is_ready', true) as $row) {
            foreach (self::AMOUNT_KEYS as $key) {
                $totals[$key] = round($totals[$key] + $row[$key], 2);
            }
        }
        return $totals;
    }
}
