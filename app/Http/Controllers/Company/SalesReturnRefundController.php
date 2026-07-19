<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\Customer;
use App\Models\CustomerTransaction;
use App\Models\FinancialYear;
use App\Models\SalesInvoice;
use App\Models\SalesReturn;
use App\Models\SalesReturnRefund;
use App\Models\SalesReturnRefundAdjustment;
use App\Services\AccountBalanceService;
use App\Services\CustomerTransactionService;
use App\Services\FileUploadService;
use App\Services\InvoiceNumberService;
use App\Services\SalesReturnSyncService;
use App\Services\ValidationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SalesReturnRefundController extends Controller
{
    public function index(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $query = SalesReturnRefund::with([
            'salesReturn',
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

        if (!$request->has('financial_year_id')) {
            if ($activeFy) {
                $query->where('financial_year_id', $activeFy->id);
                $startDate = $activeFy->start_date;
                $endDate = $activeFy->end_date;
            }
        } else {
            if ($request->financial_year_id) {
                $query->where('financial_year_id', $request->financial_year_id);
            }

            $startDate = $request->start_date;
            $endDate = $request->end_date;
        }

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('refund_no', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($customer) use ($search) {
                        $customer->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->customer_id) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('status')) {
            if ((int) $request->status === SalesReturnRefund::STATUS_CANCELLED) {
                $query->where('status', SalesReturnRefund::STATUS_CANCELLED);
            } else {
                $query->active();
            }
        }

        if (!empty($startDate)) {
            $query->whereDate('refund_date', '>=', $startDate);
        }

        if (!empty($endDate)) {
            $query->whereDate('refund_date', '<=', $endDate);
        }

        $perPage = in_array((int) $request->per_page, [10, 20, 100, 200], true)
            ? (int) $request->per_page
            : 20;

        $totalsQuery = (clone $query)->where('status', '!=', 0);

        $totalRefund = (clone $totalsQuery)->sum('refund_amount');
        $totalAdjust = (clone $totalsQuery)->sum('adjust_amount');
        $totalCash = (clone $totalsQuery)->sum('cash_amount');

        $refunds = $query
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $customers = Customer::where('company_id', $companyId)->get();

        return view(
            'company.sales-return-refund.index',
            compact(
                'refunds',
                'customers',
                'financialYears',
                'activeFy',
                'totalRefund',
                'totalAdjust',
                'totalCash',
                'startDate',
                'endDate',
                'perPage'
            )
        );
    }

    public function create($id)
    {
        $companyId = auth()->user()->company_id;

        $return = SalesReturn::with([
            'customer',
            'invoice',
            'refunds',
        ])
            ->where('company_id', $companyId)
            ->where('status', 1)
            ->findOrFail($id);

        $remainingAmount = $this->calculateRemainingRefund($return);

        if ($remainingAmount <= 0) {
            return redirect()
                ->back()
                ->with('error', 'This sales return has already been fully refunded.');
        }

        $activeFy = FinancialYear::where('company_id', $companyId)
            ->where('is_active', 1)
            ->first();

        if (!$activeFy) {
            return back()->with('error', 'Please activate financial year first.');
        }

        $outstandingInvoices = SalesInvoice::where('company_id', $companyId)
            ->where('customer_id', $return->customer_id)
            ->where('status', 1)
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->where('due_amount', '>', 0)
            ->orderBy('sale_date')
            ->get();

        $accounts = Account::where('company_id', $companyId)
            ->where('status', 'active')
            ->get();

        $refundNo = InvoiceNumberService::generate(
            'SRR',
            $companyId,
            $activeFy->id,
            SalesReturnRefund::class,
            'refund_no'
        );

        return view(
            'company.sales-return-refund.create',
            compact(
                'return',
                'accounts',
                'refundNo',
                'remainingAmount',
                'outstandingInvoices'
            )
        );
    }

    public function store(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $request->validate([
            'sales_return_id'     => 'required|exists:sales_returns,id,company_id,' . $companyId,
            'refund_date'         => 'required|date',
            'sales_invoice_id'    => 'nullable|array',
            'sales_invoice_id.*'  => 'nullable|integer',
            'adjust_amount'       => 'nullable|array',
            'adjust_amount.*'     => 'nullable|numeric|min:0',
            'account_id'          => 'nullable|exists:accounts,id,company_id,' . $companyId,
            'cash_amount'         => 'nullable|numeric|min:0',
            'reference_no'        => 'nullable|string|max:100',
            'attachment'          => ValidationService::document(),
            'note'                => 'nullable|string|max:1000',
        ]);

        $safeMessages = [
            'Please activate financial year first.',
            'No active financial year found for selected refund date.',
            'Sales return is not active or has no refundable amount.',
            'Total adjustment exceeds remaining refund.',
            'Settlement amount exceeds remaining refund.',
            'Invoice adjustment exceeds invoice due amount.',
            'Invalid invoice selected for adjustment.',
            'Refund account is required when cash refund is due.',
            'Insufficient account balance.',
            'Nothing to refund.',
            'Refunded amount exceeds grand total.',
            'Remaining amount cannot be negative.',
        ];

        try {
            $refund = DB::transaction(function () use ($request, $companyId) {
                $activeFy = $this->resolveFinancialYear($companyId, $request->refund_date);

                if (!$activeFy) {
                    throw new \Exception('No active financial year found for selected refund date.');
                }

                $return = SalesReturn::where('company_id', $companyId)
                    ->where('status', 1)
                    ->lockForUpdate()
                    ->findOrFail($request->sales_return_id);

                $remainingRefund = $this->calculateRemainingRefundLocked($return);

                if ($remainingRefund <= 0) {
                    throw new \Exception('Sales return is not active or has no refundable amount.');
                }

                $adjustments = $this->buildAdjustments(
                    $request,
                    $companyId,
                    $return->customer_id,
                    $remainingRefund
                );

                $totalAdjustment = collect($adjustments)->sum('amount');

                if ($request->filled('cash_amount')) {
                    $cashAmount = round((float) $request->cash_amount, 2);
                } elseif ($request->account_id) {
                    $cashAmount = round($remainingRefund - $totalAdjustment, 2);
                } else {
                    $cashAmount = 0;
                }

                $settlementTotal = round($totalAdjustment + $cashAmount, 2);

                if ($settlementTotal <= 0) {
                    throw new \Exception('Nothing to refund.');
                }

                if ($totalAdjustment > $remainingRefund) {
                    throw new \Exception('Total adjustment exceeds remaining refund.');
                }

                if ($cashAmount < 0) {
                    throw new \Exception('Settlement amount exceeds remaining refund.');
                }

                if ($settlementTotal > $remainingRefund) {
                    throw new \Exception('Settlement amount exceeds remaining refund.');
                }

                $account = null;

                if ($cashAmount > 0) {
                    if (!$request->account_id) {
                        throw new \Exception('Refund account is required when cash refund is due.');
                    }

                    $account = Account::where('company_id', $companyId)
                        ->where('status', 'active')
                        ->lockForUpdate()
                        ->findOrFail($request->account_id);

                    if ((float) $account->current_balance < $cashAmount) {
                        throw new \Exception('Insufficient account balance.');
                    }
                }

                $attachmentPath = null;

                if ($cashAmount > 0 && $request->hasFile('attachment')) {
                    $attachmentPath = FileUploadService::uploadFile(
                        $request->file('attachment'),
                        'companies/' . $companyId . '/sales-return-refunds'
                    );
                }

                $refundNo = InvoiceNumberService::generate(
                    'SRR',
                    $companyId,
                    $activeFy->id,
                    SalesReturnRefund::class,
                    'refund_no'
                );

                $refund = SalesReturnRefund::create([
                    'company_id'        => $companyId,
                    'financial_year_id' => $activeFy->id,
                    'sales_return_id'   => $return->id,
                    'customer_id'       => $return->customer_id,
                    'account_id'        => $account?->id,
                    'refund_no'         => $refundNo,
                    'refund_date'       => $request->refund_date,
                    'refund_amount'     => $settlementTotal,
                    'adjust_amount'     => $totalAdjustment,
                    'cash_amount'       => $cashAmount,
                    'reference_no'      => $request->reference_no,
                    'attachment'        => $attachmentPath,
                    'note'              => $request->note,
                    'created_by'        => auth()->id(),
                    'status'            => SalesReturnRefund::STATUS_ACTIVE,
                ]);

                foreach ($adjustments as $adjustment) {
                    /** @var SalesInvoice $invoice */
                    $invoice = $adjustment['invoice'];
                    $amount = (float) $adjustment['amount'];

                    $this->applyInvoiceAdjustment($invoice, $amount);

                    SalesReturnRefundAdjustment::create([
                        'company_id'              => $companyId,
                        'sales_return_refund_id'  => $refund->id,
                        'sales_invoice_id'        => $invoice->id,
                        'adjust_amount'           => $amount,
                        'status'                  => 1,
                        'created_by'              => auth()->id(),
                    ]);
                }

                if ($totalAdjustment > 0) {
                    CustomerTransactionService::createTransaction([
                        'company_id'        => $companyId,
                        'financial_year_id' => $activeFy->id,
                        'customer_id'       => $return->customer_id,
                        'transaction_date'  => $request->refund_date,
                        'voucher_no'        => $refundNo,
                        'reference_type'    => 'sales_return_refund_adjustment',
                        'reference_id'      => $refund->id,
                        'reference_no'      => $refundNo,
                        'description'       => 'Sales Return Refund Adjustment',
                        'debit'             => 0,
                        'credit'            => $totalAdjustment,
                        'remarks'           => $request->note,
                        'created_by'        => auth()->id(),
                        'status'            => 1,
                    ]);
                }

                if ($cashAmount > 0) {
                    AccountBalanceService::createTransaction([
                        'company_id'        => $companyId,
                        'financial_year_id' => $activeFy->id,
                        'account_id'        => $account->id,
                        'transaction_date'  => $request->refund_date,
                        'voucher_no'        => $refundNo,
                        'reference_type'    => 'sales_return_refund',
                        'reference_id'      => $refund->id,
                        'description'       => 'Sales Return Refund',
                        'debit'             => 0,
                        'credit'            => $cashAmount,
                        'created_by'        => auth()->id(),
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
                        'description'       => 'Sales Return Refund Cash',
                        'debit'             => 0,
                        'credit'            => $cashAmount,
                        'remarks'           => $request->note,
                        'created_by'        => auth()->id(),
                        'status'            => 1,
                    ]);
                }

                SalesReturnSyncService::sync($return, true);

                return $refund;
            });

            return redirect()
                ->route('company.sales-return-refund.show', $refund->id)
                ->with('success', 'Sales return refund created successfully.');
        } catch (\Throwable $e) {
            $this->logRefundException('Sales return refund store failed.', $e, [
                'sales_return_id' => $request->sales_return_id,
                'refund_date'     => $request->refund_date,
            ]);

            $error = $this->resolveSafeExceptionMessage(
                $e,
                $safeMessages,
                'Unable to save sales return refund. Please try again.'
            );

            return back()
                ->withInput()
                ->with('error', $error);
        }
    }

    public function show($id)
    {
        $companyId = auth()->user()->company_id;

        $refund = SalesReturnRefund::with([
            'salesReturn',
            'customer',
            'account',
            'financialYear',
            'adjustments.invoice',
        ])
            ->where('company_id', $companyId)
            ->findOrFail($id);

        $remainingRefund = $refund->salesReturn
            ? $this->calculateRemainingRefund($refund->salesReturn)
            : 0;

        $customerTransactions = CustomerTransaction::where('company_id', $companyId)
            ->where('reference_id', $refund->id)
            ->whereIn('reference_type', ['sales_return_refund', 'sales_return_refund_adjustment'])
            ->orderBy('id')
            ->get();

        $accountTransaction = null;

        if ((float) $refund->cash_amount > 0) {
            $accountTransaction = AccountTransaction::where('company_id', $companyId)
                ->where('reference_type', 'sales_return_refund')
                ->where('reference_id', $refund->id)
                ->orderByDesc('id')
                ->first();
        }

        return view(
            'company.sales-return-refund.show',
            compact('refund', 'remainingRefund', 'customerTransactions', 'accountTransaction')
        );
    }

    public function print($id)
    {
        $companyId = auth()->user()->company_id;

        $refund = SalesReturnRefund::with([
            'salesReturn',
            'customer',
            'account',
            'financialYear',
            'adjustments.invoice',
        ])
            ->where('company_id', $companyId)
            ->findOrFail($id);

        return view(
            'company.sales-return-refund.print',
            compact('refund')
        );
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

        $safeMessages = [
            'Refund already cancelled.',
            'Refund cannot be cancelled.',
            'Refunded amount exceeds grand total.',
            'Remaining amount cannot be negative.',
            'Cancel date must belong to the active financial year.',
        ];

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
                $cancelDescription = 'Sales Return Refund Cancel: ' . $cancelReason;

                $refund = SalesReturnRefund::with([
                    'adjustments.invoice',
                ])
                    ->where('company_id', $companyId)
                    ->lockForUpdate()
                    ->findOrFail($id);

                if ((int) $refund->status === SalesReturnRefund::STATUS_CANCELLED) {
                    throw new \Exception('Refund already cancelled.');
                }

                foreach ($refund->adjustments as $adjustment) {
                    if ((int) $adjustment->status !== 1) {
                        continue;
                    }

                    $invoice = $adjustment->invoice;

                    if ($invoice) {
                        $invoice = SalesInvoice::where('company_id', $companyId)
                            ->where('id', $invoice->id)
                            ->lockForUpdate()
                            ->first();

                        if ($invoice) {
                            $this->reverseInvoiceAdjustment(
                                $invoice,
                                (float) $adjustment->adjust_amount
                            );
                        }
                    }

                    $adjustment->update([
                        'status'     => 0,
                        'updated_by' => auth()->id(),
                    ]);
                }

                if ((float) $refund->cash_amount > 0) {
                    $accountTransaction = AccountTransaction::where('company_id', $companyId)
                        ->where('reference_type', 'sales_return_refund')
                        ->where('reference_id', $refund->id)
                        ->where('status', 1)
                        ->first();

                    if ($accountTransaction) {
                        AccountBalanceService::reverseTransaction(
                            $accountTransaction,
                            'sales_return_refund_cancel',
                            $cancelDescription,
                            $cancelBusinessDate,
                            $activeFy->id
                        );
                    }
                }

                $customerTransactions = CustomerTransaction::where('company_id', $companyId)
                    ->whereIn('reference_type', [
                        'sales_return_refund',
                        'sales_return_refund_adjustment',
                    ])
                    ->where('reference_id', $refund->id)
                    ->where('status', 1)
                    ->get();

                foreach ($customerTransactions as $customerTransaction) {
                    CustomerTransactionService::reverseTransaction(
                        $customerTransaction,
                        'sales_return_refund_cancel',
                        $cancelDescription,
                        $cancelBusinessDate,
                        $activeFy->id,
                        $cancelReason
                    );
                }

                $refund->update([
                    'status'     => SalesReturnRefund::STATUS_CANCELLED,
                    'updated_by' => auth()->id(),
                    'note'       => trim(($refund->note ?? '') . ' [Cancelled: ' . $cancelReason . ']'),
                ]);

                $salesReturn = SalesReturn::where('company_id', $companyId)
                    ->where('id', $refund->sales_return_id)
                    ->lockForUpdate()
                    ->first();

                if ($salesReturn) {
                    SalesReturnSyncService::sync($salesReturn, true);
                }
            });

            return redirect()
                ->route('company.sales-return-refund.show', $id)
                ->with('success', 'Refund cancelled successfully.');
        } catch (\Throwable $e) {
            $this->logRefundException('Sales return refund cancel failed.', $e, [
                'refund_id' => $id,
            ]);

            $error = $this->resolveSafeExceptionMessage(
                $e,
                $safeMessages,
                'Unable to cancel refund. Please try again.'
            );

            return back()->with('error', $error);
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

    protected function logRefundException(
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

    protected function resolveFinancialYear(int $companyId, string $date): ?FinancialYear
    {
        $activeFy = FinancialYear::where('company_id', $companyId)
            ->where('is_active', 1)
            ->first();

        if (!$activeFy) {
            return null;
        }

        $parsedDate = \Carbon\Carbon::parse($date);
        $startDate = \Carbon\Carbon::parse($activeFy->start_date);
        $endDate = \Carbon\Carbon::parse($activeFy->end_date);

        if ($parsedDate->lt($startDate) || $parsedDate->gt($endDate)) {
            return null;
        }

        return $activeFy;
    }

    protected function calculateRemainingRefund(SalesReturn $return): float
    {
        return SalesReturnSyncService::calculateRemainingAmount($return);
    }

    protected function calculateRemainingRefundLocked(SalesReturn $return): float
    {
        return SalesReturnSyncService::calculateRemainingAmount($return, true);
    }

    protected function buildAdjustments(
        Request $request,
        int $companyId,
        int $customerId,
        float $remainingRefund
    ): array {
        $invoiceIds = $request->input('sales_invoice_id', []);
        $amounts = $request->input('adjust_amount', []);
        $adjustments = [];
        $totalAdjustment = 0;

        foreach ($invoiceIds as $index => $invoiceId) {
            $amount = round((float) ($amounts[$index] ?? 0), 2);

            if ($amount <= 0) {
                continue;
            }

            $invoice = SalesInvoice::where('company_id', $companyId)
                ->where('customer_id', $customerId)
                ->where('status', 1)
                ->whereIn('payment_status', ['unpaid', 'partial'])
                ->where('due_amount', '>', 0)
                ->lockForUpdate()
                ->find($invoiceId);

            if (!$invoice) {
                throw new \Exception('Invalid invoice selected for adjustment.');
            }

            if ($amount > (float) $invoice->due_amount) {
                throw new \Exception('Invoice adjustment exceeds invoice due amount.');
            }

            $totalAdjustment += $amount;

            if ($totalAdjustment > $remainingRefund) {
                throw new \Exception('Total adjustment exceeds remaining refund.');
            }

            $adjustments[] = [
                'invoice' => $invoice,
                'amount'  => $amount,
            ];
        }

        return $adjustments;
    }

    protected function applyInvoiceAdjustment(SalesInvoice $invoice, float $amount): void
    {
        $invoice->paid_amount = (float) $invoice->paid_amount + $amount;
        $invoice->due_amount = max(0, (float) $invoice->grand_total - (float) $invoice->paid_amount);
        $invoice->payment_status = $this->resolvePaymentStatus(
            (float) $invoice->paid_amount,
            (float) $invoice->due_amount
        );
        $invoice->save();
    }

    protected function reverseInvoiceAdjustment(SalesInvoice $invoice, float $amount): void
    {
        $paidAmount = max(0, (float) $invoice->paid_amount - $amount);
        $dueAmount = max(0, (float) $invoice->grand_total - $paidAmount);

        $invoice->update([
            'paid_amount'     => $paidAmount,
            'due_amount'      => $dueAmount,
            'payment_status'  => $this->resolvePaymentStatus($paidAmount, $dueAmount),
        ]);
    }

    protected function resolvePaymentStatus(float $paidAmount, float $dueAmount): string
    {
        if ($dueAmount <= 0) {
            return 'paid';
        }

        if ($paidAmount > 0) {
            return 'partial';
        }

        return 'unpaid';
    }
}
