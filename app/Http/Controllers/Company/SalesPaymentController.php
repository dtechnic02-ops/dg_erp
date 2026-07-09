<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\AccountTransaction;
use Illuminate\Http\Request;
use App\Models\CustomerTransaction;
use App\Services\AccountBalanceService;
use App\Services\CustomerTransactionService;
use Illuminate\Support\Facades\DB;
use App\Services\InvoiceNumberService;
use App\Models\Account;
use App\Models\Customer;
use App\Models\SalesInvoice;
use App\Models\SalesPayment;
use App\Models\FinancialYear;
use App\Services\ValidationService;
use App\Services\FileUploadService;

class SalesPaymentController extends Controller
{
    /**
     * 🔥 INDEX
     */

    public function index(Request $request)
    {
        $companyId =
            auth()->user()->company_id;

        $query = SalesPayment::with([

                'salesInvoice',
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
        $endDate   = $activeFy->end_date;
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
    $endDate   = $request->end_date;
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
                    'payment_no',
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

 if (!empty($startDate))
{
    $query->whereDate(
        'payment_date',
        '>=',
        $startDate
    );
}

if (!empty($endDate))
{
    $query->whereDate(
        'payment_date',
        '<=',
        $endDate
    );
}

        $payments = $query
            ->latest()
            ->paginate(20)
            ->withQueryString();

        /**
         * 🔥 TOTAL
         */

        $totalPayment =
            $query->sum(
                'paid_amount'
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
    'company.sales-payment.index',
    compact(
        'payments',
        'customers',
        'financialYears',
        'totalPayment',
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

       $invoice = SalesInvoice::with([

    'customer',
    'payments'

])
->where(
    'company_id',
    $companyId
)
->findOrFail($id);

/**
 * 🔥 REMAINING
 */

$remainingAmount =

$invoice->due_amount;

$totalPaid =

$invoice->paid_amount;

        /**
         * 🔥 BLOCK FULL PAYMENT
         */

        if ($remainingAmount <= 0)
        {
            return redirect()
                ->back()
                ->with(
                    'error',
                    'Invoice already fully paid.'
                );
        }

        /**
         * 🔥 ACCOUNTS
         */

        $accounts = Account::where(
                'company_id',
                $companyId
            )
            ->get();

        /**
         * 🔥 PAYMENT NUMBER
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

$paymentNo = InvoiceNumberService::generate(

    'SP',

    $companyId,

    $activeFy->id,

    SalesPayment::class,

    'payment_no'

);

        return view(
            'company.sales-payment.create',
            compact(

'invoice',

'accounts',

'paymentNo',

'remainingAmount',

'totalPaid'

)
        );
    }

    /**
     * 🔥 STORE PAYMENT
     */

    public function store(Request $request)
    {
        $request->validate([

        'sales_invoice_id' =>

    'required|exists:sales_invoices,id',
'account_id' =>

    'required|exists:accounts,id',

          'paid_amount' =>
    ValidationService::requiredAmount(),

'payment_date' =>
    ValidationService::requiredDate(),

'payment_method' =>
    'required|string|max:50',

'reference_no' =>
    'nullable|string|max:100',

'receipt_file' =>
    ValidationService::document(),
    

'note' =>
    'nullable|string|max:1000',

        ]);
          try {
        DB::transaction(function ()
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

$paymentDate = \Carbon\Carbon::parse(
    $request->payment_date
);

$startDate = \Carbon\Carbon::parse(
    $activeFy->start_date
);

$endDate = \Carbon\Carbon::parse(
    $activeFy->end_date
);

if (
    $paymentDate->lt($startDate)
    ||
    $paymentDate->gt($endDate)
)
{
    throw new \Exception(
        'No active financial year found for selected payment date.'
    );
}

            /**
             * 🔥 SALES INVOICE
             */

            $invoice = SalesInvoice::with(
                    'payments'
                )
                ->where(
                    'company_id',
                    $companyId
                )
                ->findOrFail(
                    $request->sales_invoice_id
                );
                if (
    $invoice->financial_year_id !=
    $activeFy->id
)
{
    throw new \Exception(
        'Invoice belongs to another financial year.'
    );
}

            /**
             * 🔥 TOTAL PAID
             */

            $totalPaid =
                $invoice->payments
                ->sum(
                    'paid_amount'
                );

            /**
             * 🔥 REMAINING
             */

            $remainingAmount =

                $invoice->grand_total

                -

                $totalPaid;

            /**
             * 🔥 BLOCK OVER PAYMENT
             */

            if (
                $request->paid_amount
                >
                $remainingAmount
            ) {

                throw new \Exception(

                    'Payment exceeds remaining amount.'

                );
            }
$paymentNo = InvoiceNumberService::generate(

    'SP',

    $companyId,

    $activeFy->id,

    SalesPayment::class,

    'payment_no'

);
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

           $receiptFile = null;

if ($request->hasFile('receipt_file'))
{
    $receiptFile = FileUploadService::uploadFile(

        $request->file('receipt_file'),

        'companies/' .
        $companyId .
        '/sales-payments'

    );
}

            $payment = SalesPayment::create([

                'company_id' =>
                    $companyId,

                    'financial_year_id' =>
                  $activeFy->id,

                'sales_invoice_id' =>
                    $invoice->id,

                'customer_id' =>
                    $invoice->customer_id,

                'account_id' =>
                    $request->account_id,

     'payment_no' =>

    $paymentNo,

                'payment_date' =>
                    $request->payment_date,

                'paid_amount' =>
                    $request->paid_amount,


'payment_method' =>
    $request->payment_method,

'reference_no' =>
    $request->reference_no,

'receipt_file' =>
    $receiptFile,

'note' =>
    $request->note,

'created_by' =>
    auth()->id(),

                'status' => 1,

            ]);

            /**
             * 🔥 ACCOUNT INCREASE
             */
AccountBalanceService::createTransaction([

    'company_id' =>

        $companyId,

    'financial_year_id' =>

        $activeFy->id,

    'account_id' =>

        $account->id,

    'transaction_date' =>

        $request->payment_date,

    'voucher_no' =>

        $paymentNo,

    'reference_type' =>

        'sales_payment',

    'reference_id' =>

        $payment->id,

    'description' =>

        'Sales Payment',

    'debit' =>

        $request->paid_amount,

    'credit' =>

        0,

]);

            /**
             * 🔥 CUSTOMER DUE REDUCE
             */

         CustomerTransactionService::createTransaction([

    'company_id' =>

        $companyId,

    'financial_year_id' =>

        $activeFy->id,

    'customer_id' =>

        $invoice->customer_id,

    'transaction_date' =>

        $request->payment_date,

    'voucher_no' =>

        $paymentNo,

    'reference_type' =>

        'sales_payment',

    'reference_id' =>

        $payment->id,

    'reference_no' =>

        $paymentNo,

    'description' =>

        'Sales Payment',

    'debit' =>

        0,

    'credit' =>

        $request->paid_amount,

    'created_by' =>

        auth()->id(),

    'status' => 1,

]);

            /**
             * 🔥 UPDATE INVOICE
             */

            $invoice->paid_amount +=
                $request->paid_amount;

            $invoice->due_amount =

                $invoice->grand_total

                -

                $invoice->paid_amount;

            /**
             * 🔥 PAYMENT STATUS
             */

            if (
                $invoice->due_amount <= 0
            )
            {
                $invoice->payment_status =
                    'paid';
            }
            elseif (
                $invoice->paid_amount > 0
            )
            {
                $invoice->payment_status =
                    'partial';
            }
            else
            {
                $invoice->payment_status =
                    'unpaid';
            }

            $invoice->save();

        });

        return redirect()
            ->route(
                'company.sales-payment.index'
            )
            ->with(
                'success',
                'Sales payment received successfully.'
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



    public function cancel($id)
{
    $companyId = auth()->user()->company_id;

    DB::transaction(function () use ($id, $companyId) {

        $payment = SalesPayment::where(
            'company_id',
            $companyId
        )
        ->with(
            'customer',
            'salesInvoice',
            'account'
        )
        ->findOrFail($id);

        if ($payment->status == 0)
        {
            throw new \Exception(
                'Payment already cancelled.'
            );
        }

        $accountTransaction = AccountTransaction::where(
            'company_id',
            $companyId
        )
        ->where(
            'reference_type',
            'sales_payment'
        )
        ->where(
            'reference_id',
            $payment->id
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
            'sales_payment'
        )
        ->where(
            'reference_id',
            $payment->id
        )
        ->where(
            'status',
            1
        )
        ->firstOrFail();

        AccountBalanceService::reverseTransaction(

            $accountTransaction,

            'sales_payment_cancel',

            'Sales Payment Cancel'

        );

        CustomerTransactionService::reverseTransaction(

            $customerTransaction,

            'sales_payment_cancel',

            'Sales Payment Cancel'

        );

        $invoice = $payment->salesInvoice;

        if (!$invoice)
        {
            throw new \Exception(
                'Sales invoice not found.'
            );
        }

        $paidAmount = max(

            0,

            $invoice->paid_amount -
            $payment->paid_amount

        );

        $dueAmount = max(

            0,

            $invoice->grand_total -
            $paidAmount

        );

        if ($dueAmount <= 0)
        {
            $paymentStatus = 'paid';
        }
        elseif ($paidAmount > 0)
        {
            $paymentStatus = 'partial';
        }
        else
        {
            $paymentStatus = 'unpaid';
        }

        $invoice->update([

            'paid_amount' =>

                $paidAmount,

            'due_amount' =>

                $dueAmount,

            'payment_status' =>

                $paymentStatus,

        ]);

        $payment->update([

            'status' => 0,

            'note' => trim(

                ($payment->note ?? '') .
                ' [Cancelled]'

            ),

        ]);

    });

    return back()->with(

        'success',

        'Payment cancelled successfully.'

    );
}

    /**
     * 🔥 SHOW
     */

    public function show($id)
    {
        $payment = SalesPayment::with([

                'salesInvoice',
                'customer',
                'account'

            ])
            ->where(
                'company_id',
                auth()->user()->company_id
            )
            ->findOrFail($id);

        return view(
            'company.sales-payment.show',
            compact(
                'payment'
            )
        );
    }
}