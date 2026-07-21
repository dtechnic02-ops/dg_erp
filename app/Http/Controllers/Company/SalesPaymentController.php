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
use App\Models\SalesReturnRefundAdjustment;
use App\Models\FinancialYear;
use App\Services\ValidationService;
use App\Services\FileUploadService;
use Illuminate\Support\Facades\Log;

class SalesPaymentController extends Controller
{
    public function index(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $query = SalesPayment::with([
                'salesInvoice',
                'customer',
                'account',
            ])
            ->where('company_id', $companyId);

        $financialYears = FinancialYear::where('company_id', $companyId)
            ->latest('id')
            ->get();

        $activeFy = FinancialYear::where('company_id', $companyId)
            ->where('is_active', 1)
            ->first();

        $startDate = null;
        $endDate = null;

        if (!$request->has('financial_year_id'))
        {
            if ($activeFy)
            {
                $query->where('financial_year_id', $activeFy->id);
                $startDate = $activeFy->start_date;
                $endDate   = $activeFy->end_date;
            }
        }
        else
        {
            if ($request->financial_year_id)
            {
                $query->where('financial_year_id', $request->financial_year_id);
            }

            $startDate = $request->start_date;
            $endDate   = $request->end_date;
        }

        if (!$request->has('status'))
        {
            $query->where('status', 1);
        }
        elseif ($request->filled('status'))
        {
            $query->where('status', $request->status);
        }

        if ($request->filled('invoice_no'))
        {
            $invoiceNo = $request->invoice_no;

            $query->whereHas('salesInvoice', function ($invoice) use ($invoiceNo) {
                $invoice->where('invoice_no', 'like', "%{$invoiceNo}%");
            });
        }

        if ($request->filled('account_id'))
        {
            $query->where('account_id', $request->account_id);
        }

        if ($request->customer_id)
        {
            $query->where('customer_id', $request->customer_id);
        }

        if (!empty($startDate))
        {
            $query->whereDate('payment_date', '>=', $startDate);
        }

        if (!empty($endDate))
        {
            $query->whereDate('payment_date', '<=', $endDate);
        }

        $perPage = in_array((int) $request->per_page, [10, 20, 100, 200], true)
            ? (int) $request->per_page
            : 20;

        $totalsQuery = (clone $query)->where('status', 1);

        $totalPayment = (clone $totalsQuery)->sum('paid_amount');
        $totalCount   = (clone $query)->count();

        $payments = $query
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $customers = Customer::where('company_id', $companyId)
            ->orderBy('name')
            ->get();

        $accounts = Account::where('company_id', $companyId)
            ->where('status', 'active')
            ->orderBy('account_name')
            ->get();

        return view(
            'company.sales-payment.index',
            compact(
                'payments',
                'customers',
                'accounts',
                'financialYears',
                'activeFy',
                'totalPayment',
                'totalCount',
                'startDate',
                'endDate',
                'perPage'
            )
        );
    }

    public function create($id)
    {
        $companyId = auth()->user()->company_id;

        $invoice = DB::transaction(function () use ($companyId, $id) {
            return SalesInvoice::with('customer')
                ->where('company_id', $companyId)
                ->lockForUpdate()
                ->findOrFail($id);
        });

        $remainingAmount = max(0, round((float) $invoice->due_amount, 2));
        $totalPaid       = round((float) $invoice->paid_amount, 2);

        if ($remainingAmount <= 0)
        {
            return redirect()
                ->back()
                ->with('error', 'Invoice already fully paid.');
        }

        $accounts = Account::where('company_id', $companyId)
            ->where('status', 'active')
            ->orderBy('account_name')
            ->get();

        $activeFy = FinancialYear::where('company_id', $companyId)
            ->where('is_active', 1)
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

    public function store(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $request->validate([
            'sales_invoice_id' =>
                'required|exists:sales_invoices,id,company_id,' . $companyId,
            'account_id' =>
                'required|exists:accounts,id,company_id,' . $companyId,
            'paid_amount' =>
                'required|numeric|gt:0',
            'payment_date' =>
                ValidationService::requiredDate(),
            'reference_no' =>
                ValidationService::string(100),
            'receipt_file' =>
                ValidationService::document(),
            'note' =>
                ValidationService::text(),
        ]);

        try {
            DB::transaction(function () use ($request) {

                $companyId = auth()->user()->company_id;

                $activeFy = FinancialYear::where('company_id', $companyId)
                    ->where('is_active', 1)
                    ->firstOrFail();

                $paymentDate = \Carbon\Carbon::parse($request->payment_date);
                $startDate   = \Carbon\Carbon::parse($activeFy->start_date);
                $endDate     = \Carbon\Carbon::parse($activeFy->end_date);

                if ($paymentDate->lt($startDate) || $paymentDate->gt($endDate))
                {
                    throw new \Exception(
                        'No active financial year found for selected payment date.'
                    );
                }

                $invoice = SalesInvoice::where('company_id', $companyId)
                    ->lockForUpdate()
                    ->findOrFail($request->sales_invoice_id);

                if ((int) $invoice->status !== 1) {
                    throw new \Exception('Cannot pay a cancelled sales invoice.');
                }

                if ($invoice->financial_year_id != $activeFy->id)
                {
                    throw new \Exception(
                        'Invoice belongs to another financial year.'
                    );
                }

                $remainingDue = max(0, round((float) $invoice->due_amount, 2));
                $paidAmount = round((float) $request->paid_amount, 2);

                if ($paidAmount > $remainingDue)
                {
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

                $account = Account::where('company_id', $companyId)
                    ->where('status', 'active')
                    ->lockForUpdate()
                    ->findOrFail($request->account_id);

                $receiptFile = null;

                if ($request->hasFile('receipt_file'))
                {
                    $receiptFile = FileUploadService::uploadFile(
                        $request->file('receipt_file'),
                        'companies/' . $companyId . '/sales-payments'
                    );
                }

                $payment = SalesPayment::create([
                    'company_id' => $companyId,
                    'financial_year_id' => $activeFy->id,
                    'sales_invoice_id' => $invoice->id,
                    'customer_id' => $invoice->customer_id,
                    'account_id' => $request->account_id,
                    'payment_no' => $paymentNo,
                    'payment_date' => $request->payment_date,
                    'paid_amount' => $paidAmount,
                    'payment_method' => $request->payment_method,
                    'reference_no' => $request->reference_no,
                    'receipt_file' => $receiptFile,
                    'note' => $request->note,
                    'created_by' => auth()->id(),
                    'status' => 1,
                ]);

                AccountBalanceService::createTransaction([
                    'company_id' => $companyId,
                    'financial_year_id' => $activeFy->id,
                    'account_id' => $account->id,
                    'transaction_date' => $request->payment_date,
                    'voucher_no' => $paymentNo,
                    'reference_type' => 'sales_payment',
                    'reference_id' => $payment->id,
                    'description' => 'Sales Payment',
                    'debit' => $paidAmount,
                    'credit' => 0,
                ]);

                CustomerTransactionService::createTransaction([
                    'company_id' => $companyId,
                    'financial_year_id' => $activeFy->id,
                    'customer_id' => $invoice->customer_id,
                    'transaction_date' => $request->payment_date,
                    'voucher_no' => $paymentNo,
                    'reference_type' => 'sales_payment',
                    'reference_id' => $payment->id,
                    'reference_no' => $paymentNo,
                    'description' => 'Sales Payment',
                    'debit' => 0,
                    'credit' => $paidAmount,
                    'created_by' => auth()->id(),
                    'status' => 1,
                ]);

                $this->syncInvoicePaymentState($invoice);
            });

            return redirect()
                ->route('company.sales-payment.index')
                ->with('success', 'Sales payment received successfully.');
        }
        catch (\Throwable $e)
        {
            $safeMessages = [
                'No active financial year found for selected payment date.',
                'Invoice belongs to another financial year.',
                'Payment exceeds remaining amount.',
                'Cannot pay a cancelled sales invoice.',
                'Insufficient account balance.',
            ];

            $this->logPaymentException('Sales payment store failed.', $e, [
                'sales_invoice_id' => $request->sales_invoice_id,
                'payment_date'     => $request->payment_date,
            ]);

            $error = $this->resolveSafeExceptionMessage(
                $e,
                $safeMessages,
                'Unable to process the payment. Please try again.'
            );

            return back()
                ->withInput()
                ->with('error', $error);
        }
    }

    public function cancel(Request $request, $id)
    {
        $companyId = auth()->user()->company_id;

        $request->validate([
            'cancel_date' =>
                ValidationService::requiredDate(),
            'cancel_reason' =>
                ValidationService::requiredString(500),
        ]);

        try {
            DB::transaction(function () use ($request, $id, $companyId) {

                $activeFy = FinancialYear::where('company_id', $companyId)
                    ->where('is_active', 1)
                    ->firstOrFail();

                $cancelDate = \Carbon\Carbon::parse($request->cancel_date);
                $startDate  = \Carbon\Carbon::parse($activeFy->start_date);
                $endDate    = \Carbon\Carbon::parse($activeFy->end_date);

                if ($cancelDate->lt($startDate) || $cancelDate->gt($endDate))
                {
                    throw new \Exception(
                        'Cancel date must belong to the active financial year.'
                    );
                }

                $cancelBusinessDate = $cancelDate->toDateString();
                $cancelReason = trim($request->cancel_reason);
                $cancelDescription = 'Sales Payment Cancel: ' . $cancelReason;

                $payment = SalesPayment::where('company_id', $companyId)
                    ->with('customer', 'salesInvoice', 'account')
                    ->lockForUpdate()
                    ->findOrFail($id);

                if ($payment->status == 0)
                {
                    throw new \Exception('Payment already cancelled.');
                }

                $accountTransaction = AccountTransaction::where('company_id', $companyId)
                    ->where('reference_type', 'sales_payment')
                    ->where('reference_id', $payment->id)
                    ->where('status', 1)
                    ->firstOrFail();

                $customerTransaction = CustomerTransaction::where('company_id', $companyId)
                    ->where('reference_type', 'sales_payment')
                    ->where('reference_id', $payment->id)
                    ->where('status', 1)
                    ->firstOrFail();

                AccountBalanceService::reverseTransaction(
                    $accountTransaction,
                    'sales_payment_cancel',
                    $cancelDescription,
                    $cancelBusinessDate,
                    $activeFy->id
                );

                CustomerTransactionService::reverseTransaction(
                    $customerTransaction,
                    'sales_payment_cancel',
                    $cancelDescription,
                    $cancelBusinessDate,
                    $activeFy->id,
                    $cancelReason
                );

                $invoice = SalesInvoice::where('company_id', $companyId)
                    ->where('id', $payment->sales_invoice_id)
                    ->lockForUpdate()
                    ->first();

                if (!$invoice)
                {
                    throw new \Exception('Sales invoice not found.');
                }

                $payment->update([
                    'status' => 0,
                    'note' => trim(($payment->note ?? '') . ' [Cancelled: ' . $cancelReason . ']'),
                ]);

                $this->syncInvoicePaymentState($invoice);
            });

            return back()->with('success', 'Payment cancelled successfully.');
        }
        catch (\Throwable $e)
        {
            $safeMessages = [
                'Payment already cancelled.',
                'Sales invoice not found.',
                'Cancel date must belong to the active financial year.',
            ];

            $this->logPaymentException('Sales payment cancel failed.', $e, [
                'payment_id' => $id,
            ]);

            $error = $this->resolveSafeExceptionMessage(
                $e,
                $safeMessages,
                'Unable to cancel payment. Please try again.'
            );

            return back()->with('error', $error);
        }
    }

    protected function syncInvoicePaymentState(SalesInvoice $invoice): void
    {
        $paidAmount = round(
            $invoice->sumActivePaidAmount() + $this->sumActiveRefundAdjustments($invoice),
            2
        );
        $dueAmount = max(
            0,
            round((float) $invoice->grand_total - $paidAmount, 2)
        );

        $invoice->update([
            'paid_amount'     => $paidAmount,
            'due_amount'      => $dueAmount,
            'payment_status'  => $this->resolveInvoicePaymentStatus($paidAmount, $dueAmount),
        ]);
    }

    protected function sumActiveRefundAdjustments(SalesInvoice $invoice): float
    {
        return round(
            (float) SalesReturnRefundAdjustment::where('company_id', $invoice->company_id)
                ->where('sales_invoice_id', $invoice->id)
                ->where('status', 1)
                ->sum('adjust_amount'),
            2
        );
    }

    protected function resolveInvoicePaymentStatus(float $paidAmount, float $dueAmount): string
    {
        if ($dueAmount <= 0) {
            return 'paid';
        }

        if ($paidAmount > 0) {
            return 'partial';
        }

        return 'unpaid';
    }

    protected function resolveSafeExceptionMessage(
        \Throwable $e,
        array $safeMessages,
        string $fallback
    ): string {
        $message = $e->getMessage();

        if (in_array($message, $safeMessages, true)) {
            return $message;
        }

        return $fallback;
    }

    protected function logPaymentException(
        string $context,
        \Throwable $e,
        array $extra = []
    ): void {
        Log::error($context, array_merge([
            'company_id' => auth()->user()->company_id ?? null,
            'user_id'    => auth()->id(),
            'exception'  => get_class($e),
            'message'    => $e->getMessage(),
        ], $extra));
    }

    public function show($id)
    {
        $payment = SalesPayment::with([
                'salesInvoice',
                'customer',
                'account',
                'financialYear',
                'creator',
            ])
            ->where('company_id', auth()->user()->company_id)
            ->findOrFail($id);

        return view(
            'company.sales-payment.show',
            compact('payment')
        );
    }

    public function printList(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $query = SalesPayment::with([
                'salesInvoice',
                'customer',
                'account',
            ])
            ->where('company_id', $companyId);

        $financialYears = FinancialYear::where('company_id', $companyId)
            ->latest('id')
            ->get();

        $activeFy = FinancialYear::where('company_id', $companyId)
            ->where('is_active', 1)
            ->first();

        $startDate = null;
        $endDate = null;

        if (!$request->has('financial_year_id'))
        {
            if ($activeFy)
            {
                $query->where('financial_year_id', $activeFy->id);
                $startDate = $activeFy->start_date;
                $endDate   = $activeFy->end_date;
            }
        }
        else
        {
            if ($request->financial_year_id)
            {
                $query->where('financial_year_id', $request->financial_year_id);
            }

            $startDate = $request->start_date;
            $endDate   = $request->end_date;
        }

        if (!$request->has('status'))
        {
            $query->where('status', 1);
        }
        elseif ($request->filled('status'))
        {
            $query->where('status', $request->status);
        }

        if ($request->filled('invoice_no'))
        {
            $invoiceNo = $request->invoice_no;

            $query->whereHas('salesInvoice', function ($invoice) use ($invoiceNo) {
                $invoice->where('invoice_no', 'like', "%{$invoiceNo}%");
            });
        }

        if ($request->filled('account_id'))
        {
            $query->where('account_id', $request->account_id);
        }

        if ($request->customer_id)
        {
            $query->where('customer_id', $request->customer_id);
        }

        if (!empty($startDate))
        {
            $query->whereDate('payment_date', '>=', $startDate);
        }

        if (!empty($endDate))
        {
            $query->whereDate('payment_date', '<=', $endDate);
        }

        $totalsQuery = (clone $query)->where('status', 1);

        $totalPayment   = (clone $totalsQuery)->sum('paid_amount');
        $totalCount     = (clone $query)->count();
        $activeCount    = (clone $query)->where('status', 1)->count();
        $cancelledCount = (clone $query)->where('status', 0)->count();

        $payments = $query
            ->latest()
            ->get();

        $customers = Customer::where('company_id', $companyId)
            ->orderBy('name')
            ->get();

        $accounts = Account::where('company_id', $companyId)
            ->where('status', 'active')
            ->orderBy('account_name')
            ->get();

        return view(
            'company.sales-payment.print-list',
            compact(
                'payments',
                'customers',
                'accounts',
                'financialYears',
                'activeFy',
                'totalPayment',
                'totalCount',
                'activeCount',
                'cancelledCount',
                'startDate',
                'endDate'
            )
        );
    }

    public function print($id)
    {
        $payment = SalesPayment::with([
                'salesInvoice',
                'customer',
                'account',
                'financialYear',
                'creator',
            ])
            ->where('company_id', auth()->user()->company_id)
            ->findOrFail($id);

        return view(
            'company.sales-payment.print',
            compact('payment')
        );
    }
}
