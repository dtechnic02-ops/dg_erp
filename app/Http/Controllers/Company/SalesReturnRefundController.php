<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Account;
use App\Models\Customer;
use App\Models\SalesReturn;
use App\Models\SalesReturnRefund;
use App\Models\FinancialYear;
use App\Services\AccountBalanceService;
use App\Services\CustomerTransactionService;
use App\Models\AccountTransaction;
use App\Models\CustomerTransaction;
class SalesReturnRefundController extends Controller
{
    /**
     * 🔥 INDEX
     */

    public function index(Request $request)
    {
        $companyId =
            auth()->user()->company_id;

        $query = SalesReturnRefund::with([

                'salesReturn',
                'customer',
                'account'

            ])

            ->where(
                'company_id',
                $companyId
            );
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

        $startDate = $activeFy->start_date;
        $endDate = $activeFy->end_date;
    }
}
else
{
    if ($request->financial_year_id)
    {
        $query->where(
            'financial_year_id',
            $request->financial_year_id
        );
    }

    $startDate = $request->start_date;
    $endDate = $request->end_date;
}
      
        /**
         * 🔥 SEARCH
         */

        if ($request->search)
        {
            $search =
                $request->search;

            $query->where(function ($q)
            use ($search) {

                $q->where(
                    'refund_no',
                    'like',
                    "%{$search}%"
                )

                ->orWhereHas(
                    'customer',
                    function ($customer)
                    use ($search) {

                        $customer->where(
                            'name',
                            'like',
                            "%{$search}%"
                        );
                    }
                );

            });
        }

        /**
         * 🔥 CUSTOMER FILTER
         */

        if ($request->customer_id)
        {
            $query->where(
                'customer_id',
                $request->customer_id
            );
        }

        /**
         * 🔥 START DATE
         */

        if ($request->start_date)
        {
            $query->whereDate(
                'refund_date',
                '>=',
                $request->start_date
            );
        }

        /**
         * 🔥 END DATE
         */

        if ($request->end_date)
        {
            $query->whereDate(
                'refund_date',
                '<=',
                $request->end_date
            );
        }

        $refunds = $query
            ->latest()
            ->paginate(20)
            ->withQueryString();

        /**
         * 🔥 TOTALS
         */

        $totalRefund =
            $query->sum(
                'refund_amount'
            );

        /**
         * 🔥 CUSTOMERS
         */

        $customers = Customer::where(
                'company_id',
                $companyId
            )
            ->get();

        return view(
            'company.sales-return-refund.index',
compact(
    'refunds',
    'customers',
    'financialYears',
    'totalRefund',
    'startDate',
    'endDate'
)
        );
    }

    /**
     * 🔥 CREATE PAGE
     */

    public function create($id)
    {
        $companyId =
            auth()->user()->company_id;

        $return = SalesReturn::with([

                'customer',
                'invoice',
                'refunds'

            ])
            ->where(
                'company_id',
                $companyId
            )
            ->findOrFail($id);




      
$remainingAmount = $return->refund_amount;
        if ($remainingAmount <= 0)
        {
            return redirect()
                ->back()
                ->with(
                    'error',
                    'Refund already completed.'
                );
        }

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

        /**
         * 🔥 REFUND NUMBER
         */
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
        'Please activate financial year first.'
    );
}

$fyYear = date(
    'Y',
    strtotime(
        $activeFy->start_date
    )
);

$lastRefund = SalesReturnRefund::where(
        'company_id',
        $companyId
    )
    ->where(
        'financial_year_id',
        $activeFy->id
    )
    ->latest('id')
    ->first();

$nextNumber = 1;

if ($lastRefund)
{
    $parts = explode(
        '-',
        $lastRefund->refund_no
    );

    $nextNumber =
        ((int) end($parts)) + 1;
}

$refundNo =
    'SRR-' .
    $companyId .
    '-' .
    $activeFy->name .
    '-' .
    str_pad(
        $nextNumber,
        4,
        '0',
        STR_PAD_LEFT
    );
        

        return view(
            'company.sales-return-refund.create',
            compact(

                'return',

                'accounts',

                'refundNo',

                'remainingAmount'

            )
        );
    }

    /**
     * 🔥 STORE REFUND
     */

    public function store(Request $request)
    {
$request->validate([

'sales_return_id' =>

'required|exists:sales_returns,id,company_id,' . auth()->user()->company_id,

'account_id' =>

'required|exists:accounts,id,company_id,' . auth()->user()->company_id,


'refund_amount' =>

'required|numeric|min:1',

'refund_date' =>

'required|date',

]);
  try
    {

        $refund = DB::transaction(function ()
use ($request) {

            $companyId =
                auth()->user()->company_id;
                $activeFy = FinancialYear::where(
    'company_id',
    $companyId
)
->where(
    'is_active',
    1
)
->firstOrFail();

$refundDate = \Carbon\Carbon::parse(
    $request->refund_date
);

$startDate = \Carbon\Carbon::parse(
    $activeFy->start_date
);

$endDate = \Carbon\Carbon::parse(
    $activeFy->end_date
);

if (
    $refundDate->lt($startDate)
    ||
    $refundDate->gt($endDate)
)
{
    throw new \Exception(
        'No active financial year found for selected refund date.'
    );
}

            /**
             * 🔥 SALES RETURN
             */

            $return = SalesReturn::with(
                    'refunds'
                )
                ->where(
                    'company_id',
                    $companyId
                )
                ->findOrFail(
                    $request->sales_return_id
                );

      $remainingAmount = $return->refund_amount;

            if (
                $request->refund_amount
                >
                $remainingAmount
            ) {

                throw new \Exception(

                    'Refund exceeds remaining amount.'

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

            /**
             * 🔥 BALANCE CHECK
             */

            if (
                $account->current_balance
                <
                $request->refund_amount
            ) {

                throw new \Exception(

                    'Insufficient account balance.'

                );
            }

            /**
             * 🔥 CREATE REFUND
             */

$fyYear = date(
    'Y',
    strtotime(
        $activeFy->start_date
    )
);

$lastRefund = SalesReturnRefund::where(
        'company_id',
        $companyId
    )
    ->where(
        'financial_year_id',
        $activeFy->id
    )
    ->latest('id')
    ->first();

$nextNumber = 1;

if ($lastRefund)
{
    $parts = explode(
        '-',
        $lastRefund->refund_no
    );

    $nextNumber =
        ((int) end($parts)) + 1;
}

$refundNo =
    'SRR-' .
    $companyId .
    '-' .
    $activeFy->name .
    '-' .
    str_pad(
        $nextNumber,
        4,
        '0',
        STR_PAD_LEFT
    );
               
$refund =
SalesReturnRefund::create([
    

                    'company_id' =>
                        $companyId,

                        'financial_year_id' =>
                         $activeFy->id,

                    'sales_return_id' =>
                        $return->id,

                    'customer_id' =>
                        $return->customer_id,

                    'account_id' =>
                        $request->account_id,

                    'refund_no' =>

                    $refundNo,

                    'refund_date' =>
                        $request->refund_date,

                    'refund_amount' =>
                        $request->refund_amount,

                    'note' =>
                        $request->note,

                    'created_by' =>
                        auth()->id(),

                    'status' => 1,
                    

                ]);

            /**
             * 🔥 ACCOUNT DECREASE
             */

          AccountBalanceService::createTransaction([

    'company_id'         => $companyId,

    'financial_year_id'  => $activeFy->id,

    'account_id'         => $request->account_id,

    'transaction_date'   => $request->refund_date,

    'voucher_no'         => $refundNo,

    'reference_type'     => 'sales_return_refund',

    'reference_id'       => $refund->id,

    'description'        => 'Sales Return Refund',

    'debit'              => 0,

    'credit'             => $request->refund_amount,

    'created_by'         => auth()->id(),

]);
CustomerTransactionService::createTransaction([

    'company_id'        => $companyId,

    'financial_year_id' => $activeFy->id,

    'customer_id'       => $return->customer_id,

    'transaction_date'  => $request->refund_date,

    'voucher_no'        => $refundNo,

    'reference_type'    => 'sales_return_refund',

    'reference_id'      => $refund->id,

    'reference_no'      => $refundNo,

    'description'       => 'Sales Return Refund',

    'debit'             => 0,

    'credit'            => $request->refund_amount,

    'remarks'           => $request->note,

    'created_by'        => auth()->id(),

    'status'            => 1,

]);

$return->decrement(

    'refund_amount',

    $request->refund_amount

);

            /**
             * 🔥 CUSTOMER BALANCE REDUCE
             */





return $refund;



});

  return redirect()
            ->route(
                'company.sales-return-refund.show',
                $refund->id
            )
            ->with(
                'success',
                'Sales return refund created successfully.'
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
     * 🔥 SHOW
     */

    public function show($id)
    {
        $refund = SalesReturnRefund::with([

                'salesReturn',
                'customer',
                'account'

            ])
            ->where(
                'company_id',
                auth()->user()->company_id
            )
            ->findOrFail($id);

        return view(
            'company.sales-return-refund.show',
            compact(
                'refund'
            )
        );
    }



 public function cancel($id)
{
    $companyId = auth()->user()->company_id;

    $refund = SalesReturnRefund::where(
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

        $return = SalesReturn::where(
            'company_id',
            $companyId
        )->findOrFail(
            $refund->sales_return_id
        );

       $accountTransaction = AccountTransaction::where(
    'company_id',
    $companyId
)
->where(
    'reference_type',
    'sales_return_refund'
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
$customerTransaction = CustomerTransaction::where(
    'company_id',
    $companyId
)
->where(
    'reference_type',
    'sales_return_refund'
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

            'sales_return_refund_cancel',

            'Sales Return Refund Cancel'

        );

        CustomerTransactionService::reverseTransaction(

            $customerTransaction,

            'sales_return_refund_cancel',

            'Sales Return Refund Cancel'

        );

        $return->increment(

            'refund_amount',

            $refund->refund_amount

        );

        $refund->update([

            'status' => 0,

        ]);

    });

    return redirect()
        ->route(
            'company.sales-return-refund.show',
            $refund->id
        )
        ->with(
            'success',
            'Refund cancelled successfully.'
        );
}
}