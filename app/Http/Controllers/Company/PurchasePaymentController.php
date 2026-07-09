<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\AccountTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\FinancialYear;
use App\Models\PurchaseInvoice;
use App\Models\PurchasePayment;
use App\Models\Account;
use App\Models\Supplier;
use App\Services\ValidationService;
use App\Services\InvoiceNumberService;
use App\Services\FileUploadService;
use App\Services\AccountBalanceService;
use App\Services\SupplierTransactionService;
use App\Models\SupplierTransaction;
class PurchasePaymentController extends Controller
{
      public function index(Request $request)
    {
        $companyId = auth()->user()->company_id;

$query = PurchasePayment::with(
    'supplier',
    'invoice',
    'account'
)
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

        // Supplier Filter

        if ($request->supplier_id)
        {
            $query->where(
                'supplier_id',
                $request->supplier_id
            );
        }
$activeFy = FinancialYear::where(
    'company_id',
    $companyId
)
->where('is_active',1)
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

if ($request->filled('start_date')) {
    $startDate = $request->start_date;
}

if ($request->filled('end_date')) {
    $endDate = $request->end_date;
}

        // Start Date

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

        $suppliers = Supplier::where(
            'company_id',
            $companyId
        )->get();
        $financialYears = FinancialYear::where(
    'company_id',
    $companyId
)->get();

$accounts = Account::where(
    'company_id',
    $companyId
)->get();

return view(
    'company.purchase-payments.index',
    compact(
        'payments',
        'suppliers',
        'accounts',
        'financialYears',
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

        $invoice = PurchaseInvoice::with(
                'supplier'
            )
            ->where(
                'company_id',
                $companyId
            )
            ->findOrFail($id);

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
            'company.purchase-payments.create',
            compact(
                'invoice',
                'accounts'
            )
        );
    }

    /**
     * 🔥 STORE PAYMENT
     */

    public function store(Request $request)
    {
        $request->validate([


   'account_id' =>
'required|exists:accounts,id',

'purchase_invoice_id' =>
'required|exists:purchase_invoices,id',

           'payment_date' =>
    ValidationService::requiredDate(),
'amount' =>
    ValidationService::requiredAmount(),

            'receipt_file' =>
    ValidationService::document(),
    'payment_method' =>
    'required|string|max:50',
    'reference_no' =>
    'nullable|string|max:100',

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
->where(
    'is_active',
    1
)
->firstOrFail();
if (
    $request->payment_date < $activeFy->start_date ||
    $request->payment_date > $activeFy->end_date
)
{
    throw new \Exception(
        'Payment date must be inside active financial year.'
    );
}

 $invoice = PurchaseInvoice::where(
        'company_id',
        $companyId
    )
    ->findOrFail(
        $request->purchase_invoice_id
    );

/* HERE */

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
             * 🔥 DUE CHECK
             */

            if (
                $request->amount >
                $invoice->due_amount
            ) {
                throw new \Exception(
                    'Payment exceeds due amount.'
                );
            }

            /**
             * 🔥 ACCOUNT BALANCE CHECK
             */

            if (
                $account->current_balance <
                $request->amount
            ) {
                throw new \Exception(
                    'Insufficient account balance.'
                );
            }

            /**
             * 🔥 PAYMENT NUMBER
             * PAY-5-2026-0001
             */
$paymentNo = InvoiceNumberService::generate(
    'PP',
    $companyId,
    $activeFy->id,
    PurchasePayment::class,
    'payment_no'
);

            /**
             * 🔥 FILE UPLOAD
             */

            $receiptFile = null;

if (
    $request->hasFile(
        'receipt_file'
    )
)
{
    $receiptFile =
        FileUploadService::uploadFile(
            $request->file(
                'receipt_file'
            ),
            'companies/' .
            $companyId .
            '/payments'
        );
}

            /**
             * 🔥 CREATE PAYMENT
             */
$payment = PurchasePayment::create([

    'created_by' =>
        auth()->id(),

    'company_id' =>
        $companyId,

    'financial_year_id' =>
        $activeFy->id,

    'purchase_invoice_id' =>
        $invoice->id,

    'supplier_id' =>
        $invoice->supplier_id,

    'account_id' =>
        $account->id,

    'payment_no' =>
        $paymentNo,

    'payment_date' =>
        $request->payment_date,

    'amount' =>
        $request->amount,

    'payment_method' =>
        $request->payment_method,

    'reference_no' =>
        $request->reference_no,

    'receipt_file' =>
        $receiptFile,

    'note' =>
        $request->note,

    'status' => 1,

]);

            /**
             * 🔥 DEDUCT ACCOUNT BALANCE
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
        'purchase_payment',

    'reference_id' =>
        $payment->id,

    'description' =>
        'Purchase Payment',

    'debit' =>
        0,

    'credit' =>
        $request->amount,

]);

            /**
             * 🔥 UPDATE PURCHASE INVOICE
             */

            $paidAmount =
                $invoice->paid_amount
                + $request->amount;

            $dueAmount =
                $invoice->grand_total
                - $paidAmount;

            /**
             * 🔥 PAYMENT STATUS
             */

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

/**
 * 🔥 UPDATE SUPPLIER BALANCE
 */

SupplierTransactionService::createTransaction([

    'company_id'        => $companyId,

    'financial_year_id' => $activeFy->id,

    'supplier_id'       => $invoice->supplier_id,

    'transaction_date'  => $request->payment_date,

    'voucher_no'        => $paymentNo,

    'reference_type'    => 'purchase_payment',

    'reference_id'      => $payment->id,

    'reference_no'      => $paymentNo,

    'description'       => 'Purchase Payment',

    'debit'             => $request->amount,

    'credit'            => 0,

    'created_by'        => auth()->id(),

    'status'            => 1,

]);
  });

        return redirect()
            ->route(
                'company.purchases.show',
                $request->purchase_invoice_id
            )
            ->with(
                'success',
                'Payment added successfully.'
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



public function show($id)
{
    $payment = PurchasePayment::with([
        'supplier',
        'invoice',
        'account'
    ])
    ->where(
        'company_id',
        auth()->user()->company_id
    )
    ->findOrFail($id);

    return view(
        'company.purchase-payments.show',
        compact('payment')
    );
}




public function edit($id)
{
    $payment = PurchasePayment::with([
        'supplier',
        'invoice',
        'account'
    ])
    ->where(
        'company_id',
        auth()->user()->company_id
    )
    ->findOrFail($id);

    if ($payment->status == 0)
    {
        return back()->with(
            'error',
            'Cancelled payment cannot be edited.'
        );
    }

    return view(
        'company.purchase-payments.edit',
        compact('payment')
    );
}





public function update(
    Request $request,
    $id
)
{
    $request->validate([

    'receipt_file' =>
        ValidationService::document(),

    'payment_method' =>
        'nullable|string|max:50',

    'reference_no' =>
        'nullable|string|max:100',

    'note' =>
        'nullable|string',

]);

    try
    {
        $payment = PurchasePayment::where(
                'company_id',
                auth()->user()->company_id
            )
            ->findOrFail($id);

        if ($payment->status == 0)
        {
            return back()->with(
                'error',
                'Cancelled payment cannot be edited.'
            );
        }

        
       $receiptFile = $payment->receipt_file;

if ($request->hasFile('receipt_file'))
{
    $receiptFile =
        FileUploadService::replaceFile(

            $request,

            'receipt_file',

            $payment->receipt_file,

            'companies/' .
            auth()->user()->company_id .
            '/payments'

        );
}
        

        $payment->update([

            'payment_method' =>
                $request->payment_method,

            'reference_no' =>
                $request->reference_no,

            'note' =>
                $request->note,

            'receipt_file' =>
                $receiptFile,

        ]);

        return redirect()
            ->route(
                'company.purchase-payments.show',
                $payment->id
            )
            ->with(
                'success',
                'Payment updated successfully.'
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
    DB::beginTransaction();

    try {

        $payment = PurchasePayment::where(
                'company_id',
                auth()->user()->company_id
            )
            ->with(
                'supplier',
                'invoice',
                'account'
            )
            ->findOrFail($id);

        if ($payment->status == 0)
        {
            return back()->with(
                'error',
                'Payment already cancelled.'
            );
        }

        /**
         * 🔥 REVERSE ACCOUNT TRANSACTION
         */

$accountTransaction = AccountTransaction::where(
    'company_id',
    auth()->user()->company_id
)
->where(
    'reference_type',
    'purchase_payment'
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

    'purchase_payment_cancel',

    'Purchase Payment Cancel'

);

$invoice = $payment->invoice;

if (!$invoice)
{
    throw new \Exception(
        'Purchase invoice not found.'
    );
}

$paidAmount = max(
    0,
    $invoice->paid_amount -
    $payment->amount
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
        

        /**
         * 🔥 RESTORE SUPPLIER BALANCE
         */



        /**
         * 🔥 CANCEL PAYMENT
         */

      /**
 * 🔥 CANCEL PAYMENT
 */

$payment->update([

    'status' => 0,

    'note' =>
        trim(
            ($payment->note ?? '') .
            ' [Cancelled]'
        ),

]);



DB::commit();

return back()->with(
    'success',
    'Payment cancelled successfully.'
);

    }
    catch (\Exception $e)
    {
        DB::rollBack();

        return back()->with(
            'error',
            $e->getMessage()
        );
    }
    
}




public function printList(Request $request)
{
    $companyId = auth()->user()->company_id;

 $query = PurchasePayment::with([
    'supplier',
    'invoice',
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
// all भए कुनै filter छैन

 if ($request->supplier_id)
{
    $query->where(
        'supplier_id',
        $request->supplier_id
    );
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

if ($request->filled('start_date')) {
    $startDate = $request->start_date;
}

if ($request->filled('end_date')) {
    $endDate = $request->end_date;
}

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
        ->get();

    return view(
        'company.purchase-payments.print-list',
        compact('payments')
    );
}
}
