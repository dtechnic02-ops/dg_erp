<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Services\AccountBalanceService;
use App\Services\StockService;
use App\Services\PurchaseService;
use App\Services\SupplierBalanceService;
use App\Services\CustomerStatementService;
class MaintenanceController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | DASHBOARD
    |--------------------------------------------------------------------------
    */
    public function index()
    {
        return view(
            'company.maintenance.index'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | RECALCULATE ACCOUNT LEDGER
    |--------------------------------------------------------------------------
    */
    public function recalculateLedger()
    {
        AccountBalanceService::recalculateAllLedger(
            auth()->user()->company_id
        );

        return back()->with(
            'success',
            'Account Ledger Recalculated Successfully.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | RECALCULATE STOCK
    |--------------------------------------------------------------------------
    */
    public function recalculateStock()
    {
        StockService::recalculateAllStock(
            auth()->user()->company_id
        );

        return back()->with(
            'success',
            'Stock Recalculated Successfully.'
        );
    }
    /*
|--------------------------------------------------------------------------
| RECALCULATE PURCHASE INVOICE
|--------------------------------------------------------------------------
*/
public function recalculatePurchaseInvoices()
{
    PurchaseService::recalculateAllInvoices(
        auth()->user()->company_id
    );

    return back()->with(
        'success',
        'Purchase Invoices Recalculated Successfully.'
    );
}
/*
|--------------------------------------------------------------------------
| RECALCULATE SUPPLIER BALANCE
|--------------------------------------------------------------------------
*/
public function recalculateSupplierBalance()
{
    SupplierBalanceService::recalculateAllSuppliers(
        auth()->user()->company_id
    );

    return back()->with(
        'success',
        'Supplier Balance Recalculated Successfully.'
    );
}

    /*
    |--------------------------------------------------------------------------
    | RECALCULATE CUSTOMER STATEMENT
    |--------------------------------------------------------------------------
    */
    public function recalculateCustomerStatement()
    {
        $summary = CustomerStatementService::recalculateAll(
            auth()->user()->company_id,
            auth()->id()
        );

        $message = sprintf(
            'Customer Statement Recalculated. Customers Processed: %d. Statements Recalculated: %d. Errors: %d.',
            $summary['total_customers_processed'],
            $summary['total_statements_recalculated'],
            $summary['total_errors']
        );

        if ($summary['total_errors'] > 0) {
            return back()->with('warning', $message);
        }

        return back()->with('success', $message);
    }

    
}