<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\FinancialYear;
use App\Models\PurchasePayment;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnRefund;
class SupplierLedgerController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | SUPPLIER LEDGER
    |--------------------------------------------------------------------------
    */
    public function index(
        Request $request,
        $id
    )
    {
        $companyId =
            auth()->user()->company_id;

       $supplier = Supplier::where(
    'id',
    $id
)
->where(
    'company_id',
    $companyId
)
->firstOrFail();

        $financialYears = FinancialYear::where(
            'company_id',
            $companyId
        )
        ->latest('id')
        ->get();

     $activeFy = FinancialYear::where(
    'company_id',
    $companyId
)
->where(
    'is_active',
    1
)
->first();

if (!$activeFy)
{
    return back()->with(
        'error',
        'Active Financial Year not found.'
    );
}


$financialYearId =
    $request->financial_year_id
    ??
    $activeFy->id;

$selectedFy = FinancialYear::where(
    'company_id',
    $companyId
)
->findOrFail(
    $financialYearId
);

$ledger = [];


        /*
        |--------------------------------------------------------------------------
        | Opening Balance
        |--------------------------------------------------------------------------
        */

        if (
    $supplier->opening_balance > 0
)
{
    $ledger[] = [

        'date' =>
            $selectedFy->start_date,

        'voucher' =>
            'OPENING',

        'type' =>
            'Opening Balance',

        'debit' =>
            $supplier->opening_balance,

        'credit' =>
            0,

    ];
}

        /*
        |--------------------------------------------------------------------------
        | Future
        |--------------------------------------------------------------------------
        */

        // Purchase Invoice
        $purchases = PurchaseInvoice::where(
    'company_id',
    $companyId
)
->where(
    'supplier_id',
    $supplier->id
)
->where(
    'financial_year_id',
    $financialYearId
)
->where(
    'status',
    1
)
->orderBy(
    'purchase_date'
)
->get();

foreach ($purchases as $purchase)
{
    $ledger[] = [

        'date' =>
            $purchase->purchase_date,

        'voucher' =>
            $purchase->invoice_no,

        'type' =>
            'Purchase',

       'debit' =>
    $purchase->grand_total,
    

        'credit' =>
            0,
            

    ];
}

        // Purchase Payment
        $payments = PurchasePayment::where(
    'company_id',
    $companyId
)
->where(
    'supplier_id',
    $supplier->id
)

->where(
    'financial_year_id',
    $financialYearId
)
->where(
    'status',
    1
)
->orderBy(
    'payment_date'
)
->get();

foreach ($payments as $payment)
{
    $ledger[] = [

        'date' =>
            $payment->payment_date,

        'voucher' =>
            $payment->payment_no,

        'type' =>
            'Purchase Payment',

        'debit' =>
            0,

        'credit' =>
            $payment->amount,

    ];
}

        // Purchase Return


        $returns = PurchaseReturn::where(
    'company_id',
    $companyId
)
->where(
    'supplier_id',
    $supplier->id
)
->where(
    'financial_year_id',
    $financialYearId
)
->where(
    'status',
    1
)
->orderBy(
    'return_date'
)
->get();

foreach ($returns as $return)
{
    $ledger[] = [

        'date' =>
            $return->return_date,

        'voucher' =>
            $return->return_no,

        'type' =>
            'Purchase Return',

      'debit' =>
    $return->adjust_amount,

'credit' =>
    0,

    ];
}

// Purchase Return Refund

$refunds = PurchaseReturnRefund::where(
    'company_id',
    $companyId
)
->whereIn(
    'purchase_return_id',
    $returns->pluck('id')
)
->where(
    'financial_year_id',
    $financialYearId
)
->where(
    'status',
    1
)
->orderBy(
    'refund_date'
)
->get();

foreach ($refunds as $refund)
{
    $ledger[] = [

        'date' =>
            $refund->refund_date,

        'voucher' =>
            $refund->refund_no,

        'type' =>
            'Purchase Return Refund',

        'debit' =>
            0,

        'credit' =>
            $refund->amount,

    ];
}
usort($ledger, function ($a, $b) {

    return strtotime($a['date'])
        <=>
        strtotime($b['date']);

});

$balance = 0;

foreach ($ledger as &$row)
{
    $balance += $row['debit'];
    $balance -= $row['credit'];

    $row['balance'] = $balance;
}

unset($row);

$totalDebit = collect($ledger)->sum(
    'debit'
);

$totalCredit = collect($ledger)->sum(
    'credit'
);

$currentBalance = $balance;

       return view(
    'company.supplier-ledger.index',
 compact(

    'supplier',

    'ledger',

    'financialYears',

    'activeFy',

    'selectedFy',

    'totalDebit',

    'totalCredit',

    'currentBalance'

)
);
    }
}