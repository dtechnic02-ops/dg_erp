<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Services\AccountBalanceService;
use App\Services\StockService;
use App\Services\PurchaseService;
use App\Services\SupplierBalanceService;
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

    
}