<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\SalesInvoice;
use App\Models\SalesReturn;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturn;
use App\Models\FinancialYear;
class VatReportController extends Controller
{
    /**
     * VAT REPORT
     */
    public function index(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $type = $request->type;
        
$financialYear = FinancialYear::where('company_id', $companyId)
    ->where('is_active', 1)
    ->first();

if (!$financialYear) {
    return back()->with('error', 'Active Financial Year not found.');
}
     $fromDate = $request->from_date
    ?? $financialYear->start_date;

$toDate = $request->to_date
    ?? $financialYear->end_date;
        /*
        |--------------------------------------------------------------------------
        | SALES VAT
        |--------------------------------------------------------------------------
        */

        $salesInvoices = SalesInvoice::with('customer')
            ->where('company_id', $companyId)
              ->where('financial_year_id', $financialYear->id)
            ->when($fromDate, function ($q) use ($fromDate) {
                $q->whereDate('sale_date', '>=', $fromDate);
            })
            ->when($toDate, function ($q) use ($toDate) {
                $q->whereDate('sale_date', '<=', $toDate);
            })
            ->get();

        /*
        |--------------------------------------------------------------------------
        | SALES RETURN VAT
        |--------------------------------------------------------------------------
        */

        $salesReturns = SalesReturn::with('customer')
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYear->id)
            ->when($fromDate, function ($q) use ($fromDate) {
                $q->whereDate('return_date', '>=', $fromDate);
            })
            ->when($toDate, function ($q) use ($toDate) {
                $q->whereDate('return_date', '<=', $toDate);
            })
            ->get();

        /*
        |--------------------------------------------------------------------------
        | PURCHASE VAT
        |--------------------------------------------------------------------------
        */

        $purchaseInvoices = PurchaseInvoice::with('supplier')
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYear->id)
            ->when($fromDate, function ($q) use ($fromDate) {
                $q->whereDate('purchase_date', '>=', $fromDate);
            })
            ->when($toDate, function ($q) use ($toDate) {
                $q->whereDate('purchase_date', '<=', $toDate);
            })
            ->get();

        /*
        |--------------------------------------------------------------------------
        | PURCHASE RETURN VAT
        |--------------------------------------------------------------------------
        */

        $purchaseReturns = PurchaseReturn::with('supplier')
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYear->id)
            ->when($fromDate, function ($q) use ($fromDate) {
                $q->whereDate('return_date', '>=', $fromDate);
            })
            ->when($toDate, function ($q) use ($toDate) {
                $q->whereDate('return_date', '<=', $toDate);
            })
            ->get();

        /*
        |--------------------------------------------------------------------------
        | SUMMARY
        |--------------------------------------------------------------------------
        */

        $salesVat = $salesInvoices->sum('total_vat');

        $salesReturnVat = $salesReturns->sum('total_vat');

        $purchaseVat = $purchaseInvoices->sum('total_vat');

        $purchaseReturnVat = $purchaseReturns->sum('total_vat');

        $netOutputVat = $salesVat - $salesReturnVat;

        $netInputVat = $purchaseVat - $purchaseReturnVat;

        $vatPayable = $netOutputVat - $netInputVat;

        /*
        |--------------------------------------------------------------------------
        | DETAIL REPORT
        |--------------------------------------------------------------------------
        */

        $transactions = collect();

if (!$type || $type == 'sale') {

    foreach ($salesInvoices as $row) {

        $transactions->push([
            'date'       => $row->sale_date,
            'voucher_no' => $row->invoice_no,
            'type'       => 'Sale',
            'party'      => $row->customer->name ?? '',
            'vat_amount' => $row->total_vat,
        ]);
    }
}

    if (!$type || $type == 'sales_return') {

    foreach ($salesReturns as $row) {

        $transactions->push([
            'date'       => $row->return_date,
            'voucher_no' => $row->return_no,
            'type'       => 'Sales Return',
            'party'      => $row->customer->name ?? '',
            'vat_amount' => $row->total_vat,
        ]);
    }
}

       if (!$type || $type == 'purchase') {

    foreach ($purchaseInvoices as $row) {

        $transactions->push([
            'date'       => $row->purchase_date,
            'voucher_no' => $row->invoice_no,
            'type'       => 'Purchase',
            'party'      => $row->supplier->name ?? '',
            'vat_amount' => $row->total_vat,
        ]);
    }
}
        if (!$type || $type == 'purchase_return') {

    foreach ($purchaseReturns as $row) {

        $transactions->push([
            'date'       => $row->return_date,
            'voucher_no' => $row->return_no,
            'type'       => 'Purchase Return',
            'party'      => $row->supplier->name ?? '',
            'vat_amount' => $row->total_vat,
        ]);
    }
}
        $transactions = $transactions
            ->sortBy('date')
            ->values();

       return view(
    'company.vat-report.index',
    compact(
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
        'vatPayable'
    )
);
    }

    /**
     * PRINT
     */
    public function print(Request $request)
    {
        $companyId = Auth::user()->company_id;

$financialYear = FinancialYear::where('company_id', $companyId)
    ->where('is_active', 1)
    ->first();
if (!$financialYear) {
    return back()->with('error', 'Active Financial Year not found.');
}
$toDate = $request->to_date
    ?? $financialYear->end_date;

$toDate = $request->to_date
    ?? $financialYear->end_date;

        // Index को logic जस्तै राख्ने
        // वा private function बनाएर reuse गर्न सकिन्छ

        return view('company.vat-report.print');
    }
}