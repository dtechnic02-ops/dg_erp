<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\FinancialYear;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturn;
use App\Models\SalesInvoice;
use App\Models\SalesReturn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VatReportController extends Controller
{
    public function index(Request $request)
    {
        $report = $this->buildReport($request);

        if ($report instanceof \Illuminate\Http\RedirectResponse) {
            return $report;
        }

        return view(
            'company.vat-report.index',
            $report
        );
    }

    public function print(Request $request)
    {
        return redirect()->route(
            'company.vat-report.index',
            array_merge(
                $request->query(),
                ['print' => 1]
            )
        );
    }

    protected function buildReport(Request $request): array|\Illuminate\Http\RedirectResponse
    {
        $companyId = Auth::user()->company_id;
        $type = $request->type;

        $activeFy = FinancialYear::where('company_id', $companyId)
            ->where('is_active', 1)
            ->first();

        $financialYears = FinancialYear::where('company_id', $companyId)
            ->latest('id')
            ->get();

        if (!$request->has('financial_year_id')) {
            if (!$activeFy) {
                return back()->with('error', 'Active Financial Year not found.');
            }

            $financialYear = $activeFy;
            $fromDate = $request->from_date ?? $activeFy->start_date;
            $toDate = $request->to_date ?? $activeFy->end_date;
        } else {
            if ($request->filled('financial_year_id')) {
                $financialYear = FinancialYear::where('company_id', $companyId)
                    ->find($request->financial_year_id);

                if (!$financialYear) {
                    return back()->with('error', 'Selected financial year not found.');
                }
            } else {
                $financialYear = null;
            }

            $fromDate = $request->from_date;
            $toDate = $request->to_date;
        }

        $salesInvoices = SalesInvoice::with('customer')
            ->where('company_id', $companyId)
            ->when($financialYear, function (Builder $query) use ($financialYear) {
                $query->where('financial_year_id', $financialYear->id);
            })
            ->when($fromDate, function (Builder $query) use ($fromDate) {
                $query->whereDate('sale_date', '>=', $fromDate);
            })
            ->when($toDate, function (Builder $query) use ($toDate) {
                $query->whereDate('sale_date', '<=', $toDate);
            });

        $this->applyDocumentStatusFilter($salesInvoices, $request);
        $salesInvoices = $salesInvoices->get();

        $salesReturns = SalesReturn::with('customer')
            ->where('company_id', $companyId)
            ->when($financialYear, function (Builder $query) use ($financialYear) {
                $query->where('financial_year_id', $financialYear->id);
            })
            ->when($fromDate, function (Builder $query) use ($fromDate) {
                $query->whereDate('return_date', '>=', $fromDate);
            })
            ->when($toDate, function (Builder $query) use ($toDate) {
                $query->whereDate('return_date', '<=', $toDate);
            });

        $this->applyDocumentStatusFilter($salesReturns, $request);
        $salesReturns = $salesReturns->get();

        $purchaseInvoices = PurchaseInvoice::with('supplier')
            ->where('company_id', $companyId)
            ->when($financialYear, function (Builder $query) use ($financialYear) {
                $query->where('financial_year_id', $financialYear->id);
            })
            ->when($fromDate, function (Builder $query) use ($fromDate) {
                $query->whereDate('purchase_date', '>=', $fromDate);
            })
            ->when($toDate, function (Builder $query) use ($toDate) {
                $query->whereDate('purchase_date', '<=', $toDate);
            });

        $this->applyDocumentStatusFilter($purchaseInvoices, $request);
        $purchaseInvoices = $purchaseInvoices->get();

        $purchaseReturns = PurchaseReturn::with('supplier')
            ->where('company_id', $companyId)
            ->when($financialYear, function (Builder $query) use ($financialYear) {
                $query->where('financial_year_id', $financialYear->id);
            })
            ->when($fromDate, function (Builder $query) use ($fromDate) {
                $query->whereDate('return_date', '>=', $fromDate);
            })
            ->when($toDate, function (Builder $query) use ($toDate) {
                $query->whereDate('return_date', '<=', $toDate);
            });

        $this->applyDocumentStatusFilter($purchaseReturns, $request);
        $purchaseReturns = $purchaseReturns->get();

        $salesVat = $salesInvoices->sum('total_vat');
        $salesReturnVat = $salesReturns->sum('total_vat');
        $purchaseVat = $purchaseInvoices->sum('total_vat');
        $purchaseReturnVat = $purchaseReturns->sum('total_vat');
        $netOutputVat = $salesVat - $salesReturnVat;
        $netInputVat = $purchaseVat - $purchaseReturnVat;
        $vatPayable = $netOutputVat - $netInputVat;

        $transactions = collect();

        if (!$type || $type === 'sale') {
            foreach ($salesInvoices as $row) {
                $transactions->push([
                    'date'       => $row->sale_date,
                    'voucher_no' => $row->invoice_no,
                    'type'       => 'Sale',
                    'party'      => $row->customer->name ?? '',
                    'vat_amount' => $row->total_vat,
                    'status'     => (int) $row->status === 1 ? 'Active' : 'Cancelled',
                ]);
            }
        }

        if (!$type || $type === 'sales_return') {
            foreach ($salesReturns as $row) {
                $transactions->push([
                    'date'       => $row->return_date,
                    'voucher_no' => $row->return_no,
                    'type'       => 'Sales Return',
                    'party'      => $row->customer->name ?? '',
                    'vat_amount' => $row->total_vat,
                    'status'     => (int) $row->status === 1 ? 'Active' : 'Cancelled',
                ]);
            }
        }

        if (!$type || $type === 'purchase') {
            foreach ($purchaseInvoices as $row) {
                $transactions->push([
                    'date'       => $row->purchase_date,
                    'voucher_no' => $row->invoice_no,
                    'type'       => 'Purchase',
                    'party'      => $row->supplier->name ?? '',
                    'vat_amount' => $row->total_vat,
                    'status'     => (int) $row->status === 1 ? 'Active' : 'Cancelled',
                ]);
            }
        }

        if (!$type || $type === 'purchase_return') {
            foreach ($purchaseReturns as $row) {
                $transactions->push([
                    'date'       => $row->return_date,
                    'voucher_no' => $row->return_no,
                    'type'       => 'Purchase Return',
                    'party'      => $row->supplier->name ?? '',
                    'vat_amount' => $row->total_vat,
                    'status'     => (int) $row->status === 1 ? 'Active' : 'Cancelled',
                ]);
            }
        }

        $transactions = $transactions
            ->sortBy('date')
            ->values();

        return compact(
            'transactions',
            'type',
            'fromDate',
            'toDate',
            'salesVat',
            'salesReturnVat',
            'purchaseVat',
            'purchaseReturnVat',
            'netOutputVat',
            'netInputVat',
            'vatPayable',
            'financialYears',
            'activeFy',
            'financialYear'
        );
    }

    protected function applyDocumentStatusFilter(Builder $query, Request $request): void
    {
        if (!$request->has('status')) {
            $query->where('status', 1);

            return;
        }

        if ($request->filled('status')) {
            $query->where('status', (int) $request->status);
        }
    }
}
