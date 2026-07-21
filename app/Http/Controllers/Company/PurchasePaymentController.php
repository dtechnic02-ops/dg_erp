<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\FinancialYear;
use App\Models\PurchaseInvoice;
use App\Models\PurchasePayment;
use App\Models\Supplier;
use App\Models\SupplierTransaction;
use App\Services\AccountBalanceService;
use App\Services\FileUploadService;
use App\Services\InvoiceNumberService;
use App\Services\PurchaseInvoicePaymentStateService;
use App\Services\SupplierTransactionService;
use App\Services\ValidationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurchasePaymentController extends Controller
{
    public function index(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $query = PurchasePayment::with([
                'invoice',
                'supplier',
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

        if (!$request->has('financial_year_id')) {
            if ($activeFy) {
                $query->where('financial_year_id', $activeFy->id);
                $startDate = $activeFy->start_date;
                $endDate   = $activeFy->end_date;
            }
        } else {
            if ($request->financial_year_id) {
                $query->where('financial_year_id', $request->financial_year_id);
            }

            $startDate = $request->start_date;
            $endDate   = $request->end_date;
        }

        if (!$request->has('status')) {
            $query->where('status', 1);
        } elseif ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('invoice_no')) {
            $invoiceNo = $request->invoice_no;

            $query->whereHas('invoice', function ($invoice) use ($invoiceNo) {
                $invoice->where('invoice_no', 'like', "%{$invoiceNo}%");
            });
        }

        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        if ($request->supplier_id) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if (!empty($startDate)) {
            $query->whereDate('payment_date', '>=', $startDate);
        }

        if (!empty($endDate)) {
            $query->whereDate('payment_date', '<=', $endDate);
        }

        $perPage = in_array((int) $request->per_page, [10, 20, 100, 200], true)
            ? (int) $request->per_page
            : 20;

        $totalsQuery = (clone $query)->where('status', 1);

        $totalPayment = (clone $totalsQuery)->sum('amount');
        $totalCount   = (clone $query)->count();

        $payments = $query
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $suppliers = Supplier::where('company_id', $companyId)
            ->orderBy('name')
            ->get();

        $accounts = Account::where('company_id', $companyId)
            ->where('status', 'active')
            ->orderBy('account_name')
            ->get();

        return view(
            'company.purchase-payments.index',
            compact(
                'payments',
                'suppliers',
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
            return PurchaseInvoice::with('supplier')
                ->where('company_id', $companyId)
                ->lockForUpdate()
                ->findOrFail($id);
        });

        $remainingAmount = max(0, round((float) $invoice->due_amount, 2));
        $totalPaid       = round((float) $invoice->paid_amount, 2);

        if ($remainingAmount <= 0) {
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

        if (!$activeFy) {
            return back()->with(
                'error',
                'Please activate financial year first.'
            );
        }

        $paymentNo = InvoiceNumberService::generate(
            'PP',
            $companyId,
            $activeFy->id,
            PurchasePayment::class,
            'payment_no'
        );

        return view(
            'company.purchase-payments.create',
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
            'purchase_invoice_id' =>
                'required|exists:purchase_invoices,id,company_id,' . $companyId,
            'account_id' =>
                'required|exists:accounts,id,company_id,' . $companyId,
            'amount' =>
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

                if ($paymentDate->lt($startDate) || $paymentDate->gt($endDate)) {
                    throw new \Exception(
                        'No active financial year found for selected payment date.'
                    );
                }

                $invoice = PurchaseInvoice::where('company_id', $companyId)
                    ->lockForUpdate()
                    ->findOrFail($request->purchase_invoice_id);

                if ((int) $invoice->status !== 1) {
                    throw new \Exception('Cannot pay a cancelled purchase invoice.');
                }

                if ($invoice->financial_year_id != $activeFy->id) {
                    throw new \Exception(
                        'Invoice belongs to another financial year.'
                    );
                }

                $remainingDue = max(0, round((float) $invoice->due_amount, 2));
                $paidAmount = round((float) $request->amount, 2);

                if ($paidAmount > $remainingDue) {
                    throw new \Exception(
                        'Payment exceeds remaining amount.'
                    );
                }

                $paymentNo = InvoiceNumberService::generate(
                    'PP',
                    $companyId,
                    $activeFy->id,
                    PurchasePayment::class,
                    'payment_no'
                );

                $account = Account::where('company_id', $companyId)
                    ->where('status', 'active')
                    ->lockForUpdate()
                    ->findOrFail($request->account_id);

                if ((float) $account->current_balance < $paidAmount) {
                    throw new \Exception(
                        'Insufficient account balance.'
                    );
                }

                $receiptFile = null;

                if ($request->hasFile('receipt_file')) {
                    $receiptFile = FileUploadService::uploadFile(
                        $request->file('receipt_file'),
                        'companies/' . $companyId . '/purchase-payments'
                    );
                }

                $payment = PurchasePayment::create([
                    'company_id' => $companyId,
                    'financial_year_id' => $activeFy->id,
                    'purchase_invoice_id' => $invoice->id,
                    'supplier_id' => $invoice->supplier_id,
                    'account_id' => $request->account_id,
                    'payment_no' => $paymentNo,
                    'payment_date' => $request->payment_date,
                    'amount' => $paidAmount,
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
                    'reference_type' => 'purchase_payment',
                    'reference_id' => $payment->id,
                    'description' => 'Purchase Payment',
                    'debit' => 0,
                    'credit' => $paidAmount,
                ]);

                SupplierTransactionService::createTransaction([
                    'company_id' => $companyId,
                    'financial_year_id' => $activeFy->id,
                    'supplier_id' => $invoice->supplier_id,
                    'transaction_date' => $request->payment_date,
                    'voucher_no' => $paymentNo,
                    'reference_type' => 'purchase_payment',
                    'reference_id' => $payment->id,
                    'reference_no' => $paymentNo,
                    'description' => 'Purchase Payment',
                    'debit' => $paidAmount,
                    'credit' => 0,
                    'created_by' => auth()->id(),
                    'status' => 1,
                ]);

                PurchaseInvoicePaymentStateService::syncInvoicePaymentState($invoice);
            });

            return redirect()
                ->route('company.purchase-payments.index')
                ->with('success', 'Purchase payment recorded successfully.');
        } catch (\Throwable $e) {
            $safeMessages = [
                'No active financial year found for selected payment date.',
                'Invoice belongs to another financial year.',
                'Payment exceeds remaining amount.',
                'Cannot pay a cancelled purchase invoice.',
                'Insufficient account balance.',
            ];

            $this->logPaymentException('Purchase payment store failed.', $e, [
                'purchase_invoice_id' => $request->purchase_invoice_id,
                'payment_date'        => $request->payment_date,
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

                if ($cancelDate->lt($startDate) || $cancelDate->gt($endDate)) {
                    throw new \Exception(
                        'Cancel date must belong to the active financial year.'
                    );
                }

                $cancelBusinessDate = $cancelDate->toDateString();
                $cancelReason = trim($request->cancel_reason);
                $cancelDescription = 'Purchase Payment Cancel: ' . $cancelReason;

                $payment = PurchasePayment::where('company_id', $companyId)
                    ->with('supplier', 'invoice', 'account')
                    ->lockForUpdate()
                    ->findOrFail($id);

                if ($payment->status == 0) {
                    throw new \Exception('Payment already cancelled.');
                }

                $accountTransaction = AccountTransaction::where('company_id', $companyId)
                    ->where('reference_type', 'purchase_payment')
                    ->where('reference_id', $payment->id)
                    ->where('status', 1)
                    ->firstOrFail();

                $supplierTransaction = SupplierTransaction::where('company_id', $companyId)
                    ->where('reference_type', 'purchase_payment')
                    ->where('reference_id', $payment->id)
                    ->where('status', 1)
                    ->firstOrFail();

                AccountBalanceService::reverseTransaction(
                    $accountTransaction,
                    'purchase_payment_cancel',
                    $cancelDescription,
                    $cancelBusinessDate,
                    $activeFy->id
                );

                SupplierTransactionService::reverseTransaction(
                    $supplierTransaction,
                    'purchase_payment_cancel',
                    $cancelDescription,
                    $cancelBusinessDate,
                    $activeFy->id,
                    $cancelReason
                );

                $invoice = PurchaseInvoice::where('company_id', $companyId)
                    ->where('id', $payment->purchase_invoice_id)
                    ->lockForUpdate()
                    ->first();

                if (!$invoice) {
                    throw new \Exception('Purchase invoice not found.');
                }

                $payment->update([
                    'status' => 0,
                    'note' => trim(($payment->note ?? '') . ' [Cancelled: ' . $cancelReason . ']'),
                ]);

                PurchaseInvoicePaymentStateService::syncInvoicePaymentState($invoice);
            });

            return back()->with('success', 'Payment cancelled successfully.');
        } catch (\Throwable $e) {
            $safeMessages = [
                'Payment already cancelled.',
                'Purchase invoice not found.',
                'Cancel date must belong to the active financial year.',
            ];

            $this->logPaymentException('Purchase payment cancel failed.', $e, [
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

    public function show($id)
    {
        $payment = PurchasePayment::with([
                'invoice',
                'supplier',
                'account',
                'financialYear',
                'creator',
            ])
            ->where('company_id', auth()->user()->company_id)
            ->findOrFail($id);

        return view(
            'company.purchase-payments.show',
            compact('payment')
        );
    }

    public function printList(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $query = PurchasePayment::with([
                'invoice',
                'supplier',
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

        if (!$request->has('financial_year_id')) {
            if ($activeFy) {
                $query->where('financial_year_id', $activeFy->id);
                $startDate = $activeFy->start_date;
                $endDate   = $activeFy->end_date;
            }
        } else {
            if ($request->financial_year_id) {
                $query->where('financial_year_id', $request->financial_year_id);
            }

            $startDate = $request->start_date;
            $endDate   = $request->end_date;
        }

        if (!$request->has('status')) {
            $query->where('status', 1);
        } elseif ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('invoice_no')) {
            $invoiceNo = $request->invoice_no;

            $query->whereHas('invoice', function ($invoice) use ($invoiceNo) {
                $invoice->where('invoice_no', 'like', "%{$invoiceNo}%");
            });
        }

        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        if ($request->supplier_id) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if (!empty($startDate)) {
            $query->whereDate('payment_date', '>=', $startDate);
        }

        if (!empty($endDate)) {
            $query->whereDate('payment_date', '<=', $endDate);
        }

        $totalsQuery = (clone $query)->where('status', 1);

        $totalPayment   = (clone $totalsQuery)->sum('amount');
        $totalCount     = (clone $query)->count();
        $activeCount    = (clone $query)->where('status', 1)->count();
        $cancelledCount = (clone $query)->where('status', 0)->count();

        $payments = $query
            ->latest()
            ->get();

        $suppliers = Supplier::where('company_id', $companyId)
            ->orderBy('name')
            ->get();

        $accounts = Account::where('company_id', $companyId)
            ->where('status', 'active')
            ->orderBy('account_name')
            ->get();

        return view(
            'company.purchase-payments.print-list',
            compact(
                'payments',
                'suppliers',
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
        $payment = PurchasePayment::with([
                'invoice',
                'supplier',
                'account',
                'financialYear',
                'creator',
            ])
            ->where('company_id', auth()->user()->company_id)
            ->findOrFail($id);

        return view(
            'company.purchase-payments.print',
            compact('payment')
        );
    }

    public function edit($id)
    {
        $payment = PurchasePayment::with([
                'supplier',
                'invoice',
                'account',
            ])
            ->where('company_id', auth()->user()->company_id)
            ->findOrFail($id);

        if ($payment->status == 0) {
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

    public function update(Request $request, $id)
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

        try {
            $payment = PurchasePayment::where(
                    'company_id',
                    auth()->user()->company_id
                )
                ->findOrFail($id);

            if ($payment->status == 0) {
                return back()->with(
                    'error',
                    'Cancelled payment cannot be edited.'
                );
            }

            $receiptFile = $payment->receipt_file;

            if ($request->hasFile('receipt_file')) {
                $receiptFile = FileUploadService::replaceFile(
                    $request,
                    'receipt_file',
                    $payment->receipt_file,
                    'companies/' . auth()->user()->company_id . '/purchase-payments'
                );
            }

            $payment->update([
                'payment_method' => $request->payment_method,
                'reference_no'   => $request->reference_no,
                'note'           => $request->note,
                'receipt_file'   => $receiptFile,
                'updated_by'     => auth()->id(),
            ]);

            return redirect()
                ->route('company.purchase-payments.show', $payment->id)
                ->with('success', 'Payment updated successfully.');
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
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
}
