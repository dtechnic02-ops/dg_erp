<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\FinancialYear;
use Illuminate\Support\Facades\DB;
use App\Models\Supplier;
use App\Models\Account;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnRefund;
use App\Services\InvoiceNumberService;
use App\Services\AccountBalanceService;
use App\Services\ValidationService;
use App\Models\AccountTransaction;
use App\Services\SupplierTransactionService;
use App\Services\FileUploadService;
use App\Models\SupplierTransaction;
class PurchaseReturnRefundController extends Controller

{/**
 * 🔥 REFUND LIST
 */

public function index(Request $request)
{
    $companyId =
        auth()->user()->company_id;

    /**
     * 🔥 QUERY
     */

    $query = PurchaseReturnRefund::with([

            'purchaseReturn.supplier',

            'account'

        ])
        ->where(
            'company_id',
            $companyId
        );
        if (!$request->filled('status'))
{
    $query->where('status',1);
}
elseif ($request->status == 'active')
{
    $query->where('status',1);
}
elseif ($request->status == 'cancelled')
{
    $query->where('status',0);
}
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

    /**
     * 🔥 SUPPLIER FILTER
     */

    if ($request->supplier_id)
    {
        $query->whereHas(
            'purchaseReturn',
            function ($q) use ($request) {

                $q->where(
                    'supplier_id',
                    $request->supplier_id
                );

            }
        );
    }
 $startDate = null;
$endDate   = null;

if (!$request->has('financial_year_id'))
{
    if ($activeFy)
    {
        $query->where(
            'financial_year_id',
            $activeFy->id
        );

        $startDate = $activeFy->start_date;
        $endDate   = $activeFy->end_date;
    }
}
else
{
    if (
    $request->filled('financial_year_id') &&
    $request->financial_year_id != 'all'
)
{
    $query->where(
        'financial_year_id',
        $request->financial_year_id
    );
}

    $startDate = $request->start_date;
    $endDate   = $request->end_date;
}

if (!empty($startDate))
{
    $query->whereDate(
        'refund_date',
        '>=',
        $startDate
    );
}

if (!empty($endDate))
{
    $query->whereDate(
        'refund_date',
        '<=',
        $endDate
    );
}

$summaryQuery = clone $query;

$totalRecords =
    $summaryQuery->count();

$totalAmount =
    (clone $summaryQuery)
        ->sum('amount');

$totalCancelled =
    (clone $summaryQuery)
        ->where(
            'status',
            0
        )
        ->count();
    $refunds = $query
        ->latest()
        ->paginate(20)
        ->withQueryString();

    /**
     * 🔥 FILTER DATA
     */

    $suppliers = Supplier::where(
            'company_id',
            $companyId
        )
        ->get();
        $financialYears = FinancialYear::where(
    'company_id',
    $companyId
)->get();

$accounts = Account::where(
    'company_id',
    $companyId
)
->where(
    'status',
    'active'
)
->get();

return view(
    'company.purchase-return-refunds.index',
    compact(
        'refunds',
        'suppliers',
        'accounts',
        'financialYears',
        'startDate',
        'endDate',
        'totalRecords',
        'totalCancelled',
        'totalAmount'
    )
);
}
    /**
     * 🔥 CREATE REFUND PAGE
     */

    public function create($id)
    {
        $companyId =
            auth()->user()->company_id;

        /**
         * 🔥 RETURN
         */

        $return = PurchaseReturn::with([

                'supplier',

                'purchaseInvoice',

                'refunds'

            ])
            ->where(
                'company_id',
                $companyId
            )
            ->findOrFail($id);
            if ($return->status == 0)
{
    return back()->with(
        'error',
        'Cancelled purchase return cannot receive refund.'
    );
}

        /**
         * 🔥 TOTAL REFUNDED
         */

      $totalRefunded =
    $return->refunds()
        ->where(
            'status',
            1
        )
        ->sum(
            'amount'
        );

        /**
         * 🔥 REMAINING REFUND
         */

        $remainingRefund =
    $return->refund_amount
    - $totalRefunded;

        /**
         * 🔥 ACCOUNTS
         */

       $accounts = Account::where(
    'company_id',
    $companyId
)
->where(
    'status',
    'active'
)
->get();

        return view(
            'company.purchase-return-refunds.create',
            compact(

                'return',

                'accounts',

                'totalRefunded',

                'remainingRefund'

            )
        );
    }

    /**
     * 🔥 STORE REFUND
     */

    public function store(Request $request)
    {

$request->validate([

    'purchase_return_id' =>
        'required|exists:purchase_returns,id',

    'account_id' =>
        'required|exists:accounts,id',

    'refund_date' =>
        ValidationService::requiredDate(),

    'amount' =>
        ValidationService::requiredAmount(),

    'payment_method' =>
        'required|string|max:50',

    'attachment' =>
        ValidationService::document(),

    'note' =>
        'nullable|string|max:1000',

]);

try
{
        DB::transaction(function () use ($request) {

            $companyId =
                auth()->user()->company_id;

$activeFy = FinancialYear::where(
    'company_id',
    $companyId
)
->whereDate(
    'start_date',
    '<=',
    $request->refund_date
)
->whereDate(
    'end_date',
    '>=',
    $request->refund_date
)
->first();

if (!$activeFy)
{
    throw new \Exception(
        'Financial Year not found for selected refund date.'
    );
}

$return = PurchaseReturn::with(
    'refunds'
)
->where(
    'company_id',
    $companyId
)
->findOrFail(
    $request->purchase_return_id
);
if (
    $return->financial_year_id !=
    $activeFy->id
)
{
    throw new \Exception(
        'Purchase Return belongs to another Financial Year.'
    );
}

          if ($return->status == 0)
{
    throw new \Exception(
        'Cancelled purchase return cannot receive refund.'
    );
}      


 


            /**
             * 🔥 ACCOUNT
             */

           $account = Account::where(
        'company_id',
        $companyId
    )
    ->where(
        'status',
        'active'
    )
    ->findOrFail(
        $request->account_id
    );

                if (
    $account->current_balance <
    $request->amount
)
{
    throw new \Exception(
        'Insufficient account balance.'
    );
}

            /**
             * 🔥 TOTAL REFUNDED
             */

          $totalRefunded =
    $return->refunds()
        ->where(
            'status',
            1
        )
        ->sum(
            'amount'
        );
            /**
             * 🔥 REMAINING
             */

 $remainingRefund =
    $return->refund_amount
    - $totalRefunded;

            /**
             * 🔥 BLOCK OVER REFUND
             */

if ($remainingRefund <= 0)
{
    throw new \Exception(
        'No refund amount remaining.'
    );
}

if (
    $request->amount >
    $remainingRefund
)
{
    throw new \Exception(
        'Refund amount exceeds remaining refund.'
    );
}
            

            /**
             * 🔥 CREATE REFUND
             */
$refundNo = InvoiceNumberService::generate(

    'PRRF',

    $companyId,

    $activeFy->id,

    PurchaseReturnRefund::class,

    'refund_no'

);

$refund = PurchaseReturnRefund::create([

    'company_id' => $companyId,
    'purchase_return_id' => $return->id,
    'account_id' => $account->id,
    'financial_year_id' => $activeFy->id,
    'refund_no' => $refundNo,
   'amount' => $request->amount,

'payment_method' => $request->payment_method,

'attachment' => null,

'refund_date' => $request->refund_date,
    'note' => $request->note,
    'created_by' => auth()->id(),
    'status' => 1,

]);

AccountBalanceService::createTransaction([
    'company_id' => $companyId,
    'financial_year_id' => $activeFy->id,
    'account_id' => $account->id,
    'transaction_date' => $request->refund_date,
    'voucher_no' => $refundNo,
    'reference_type' => 'purchase_return_refund',
    'reference_id' => $refund->id,
    'description' => 'Purchase Return Refund',
    'debit' => $request->amount,
    'credit' => 0,
]);




// ==========================
// SUPPLIER REFUND TRANSACTION
// ==========================

SupplierTransactionService::createTransaction([

    'company_id'        => $companyId,

    'financial_year_id' => $activeFy->id,

    'supplier_id'       => $return->supplier_id,

    'transaction_date'  => $request->refund_date,

    'voucher_no'        => $refundNo,

    'reference_type'    => 'purchase_return_refund',

    'reference_id'      => $refund->id,

    'reference_no'      => $refundNo,

    'description'       => 'Purchase Return Refund',

    'debit'             => 0,

    'credit'            => $request->amount,

    'created_by'        => auth()->id(),

    'status'            => 1,

]);

// ==========================
// ATTACHMENT UPLOAD
// ==========================

if ($request->hasFile('attachment'))
{
    $attachment = FileUploadService::uploadFile(

        $request->file('attachment'),

        'companies/'
        .$companyId.
        '/purchase-return-refunds'

    );

    $refund->update([

        'attachment' => $attachment

    ]);
}


}); // transaction close




return redirect()
    ->route(
        'company.purchase-return-refunds.index'
    )
    ->with(
        'success',
        'Refund received successfully.'
    );
}

catch (\Exception $e)
{
    return back()
        ->withInput()
        ->with(
            'error',
            $e->getMessage()
        );
}
    }
    /**
 * 🔥 SHOW REFUND
 */

public function show($id)
{
   $refund = PurchaseReturnRefund::with([

    'purchaseReturn.supplier',

    'account',

    'financialYear'

])
->where(
    'company_id',
    auth()->user()->company_id
)
->findOrFail($id);

    return view(
        'company.purchase-return-refunds.show',
        compact('refund')
    );
}
public function print(Request $request)
{
    $companyId =
        auth()->user()->company_id;

    $query = PurchaseReturnRefund::with([

        'purchaseReturn.supplier',

        'account',

        'financialYear'

    ])
    ->where(
        'company_id',
        $companyId
    );
    if (!$request->filled('status'))
{
    $query->where('status',1);
}
elseif ($request->status == 'active')
{
    $query->where('status',1);
}
elseif ($request->status == 'cancelled')
{
    $query->where('status',0);
}

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

    if ($request->supplier_id)
    {
        $query->whereHas(
            'purchaseReturn',
            function ($q) use ($request)
            {
                $q->where(
                    'supplier_id',
                    $request->supplier_id
                );
            }
        );
    }

    if ($request->account_id)
    {
        $query->where(
            'account_id',
            $request->account_id
        );
    }

    $startDate = null;
    $endDate = null;

    if (!$request->has('financial_year_id'))
    {
        if ($activeFy)
        {
            $query->where(
                'financial_year_id',
                $activeFy->id
            );

            $startDate =
                $activeFy->start_date;

            $endDate =
                $activeFy->end_date;
        }
    }
 else
{
    if (
        $request->filled('financial_year_id') &&
        $request->financial_year_id != 'all'
    )
    {
        $query->where(
            'financial_year_id',
            $request->financial_year_id
        );
    }

    if ($request->filled('start_date'))
    {
        $startDate = $request->start_date;
    }

    if ($request->filled('end_date'))
    {
        $endDate = $request->end_date;
    }
}

    if (!empty($startDate))
    {
        $query->whereDate(
            'refund_date',
            '>=',
            $startDate
        );
    }

    if (!empty($endDate))
    {
        $query->whereDate(
            'refund_date',
            '<=',
            $endDate
        );
    }

    $refunds = $query
        ->latest()
        ->get();

    return view(
        'company.purchase-return-refunds.print-list',
        compact(
    'refunds',
    'startDate',
    'endDate'
)
        
    );
}


public function cancel($id)
{
    $companyId = auth()->user()->company_id;

    $refund = PurchaseReturnRefund::where(
        'company_id',
        $companyId
    )->findOrFail($id);

    if ($refund->status == 0)
    {
        return back()->with(
            'error',
            'Refund already cancelled.'
        );
    }

    DB::transaction(function () use (

        $refund,

        $companyId

    ) {

        $return = PurchaseReturn::where(
            'company_id',
            $companyId
        )->findOrFail(
            $refund->purchase_return_id
        );

        $accountTransaction = AccountTransaction::where(
            'company_id',
            $companyId
        )
        ->where(
            'reference_type',
            'purchase_return_refund'
        )
        ->where(
            'reference_id',
            $refund->id
        )
        ->where(
            'status',
            1
        )
        ->firstOrFail();

        $supplierTransaction = SupplierTransaction::where(
            'company_id',
            $companyId
        )
        ->where(
            'reference_type',
            'purchase_return_refund'
        )
        ->where(
            'reference_id',
            $refund->id
        )
        ->where(
            'status',
            1
        )
        ->firstOrFail();

        AccountBalanceService::reverseTransaction(

            $accountTransaction,

            'purchase_return_refund_cancel',

            'Purchase Return Refund Cancel'

        );

        SupplierTransactionService::reverseTransaction(

            $supplierTransaction,

            'purchase_return_refund_cancel',

            'Purchase Return Refund Cancel'

        );

        $return->increment(

            'refund_amount',

            $refund->amount

        );

        $refund->update([

            'status' => 0,
            

        ]);

    });

    return redirect()
        ->route(
            'company.purchase-return-refunds.show',
            $refund->id
        )
        ->with(
            'success',
            'Refund cancelled successfully.'
        );
}
}


