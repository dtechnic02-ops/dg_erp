<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesCompanyPermission;
use App\Http\Controllers\Concerns\AuthorizesSubscriptionModule;
use App\Http\Controllers\Concerns\HandlesTransactionDocumentationEdit;
use Illuminate\Routing\Controllers\HasMiddleware;
use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\FinancialYear;
use App\Models\LoanAccount;
use App\Models\LoanPayment;
use App\Models\LoanSavingLedger;
use App\Models\PartyAccount;
use App\Services\AccountBalanceService;
use App\Services\ValidationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LoanPaymentController extends Controller implements HasMiddleware
{
    use AuthorizesCompanyPermission;
    use AuthorizesSubscriptionModule;
    use HandlesTransactionDocumentationEdit;

    public static function middleware(): array
    {
        return self::subscriptionModuleMiddleware();
    }

    protected static function subscriptionModuleCode(): string
    {
        return 'loan';
    }

    protected function findCompanyPayment(int $id, array $with = []): LoanPayment
    {
        return LoanPayment::with($with)
            ->where('company_id', auth()->user()->company_id)
            ->findOrFail($id);
    }

    private function filteredPaymentQuery(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $query = LoanPayment::with([
            'loanAccount.partyAccount:id,company_id,name',
            'loanAccount:id,company_id,loan_no,loan_name,loan_type,party_account_id',
            'account:id,company_id,account_name',
            'createdBy:id,name',
            'savingLedgers:id,loan_payment_id,type,status',
        ])->where('company_id', $companyId);

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($paymentQuery) use ($search) {
                $paymentQuery->where('reference_no', 'like', '%' . $search . '%')
                    ->orWhereHas('loanAccount', function ($loanQuery) use ($search) {
                        $loanQuery->where('loan_no', 'like', '%' . $search . '%')
                            ->orWhere('loan_name', 'like', '%' . $search . '%')
                            ->orWhereHas('partyAccount', function ($partyQuery) use ($search) {
                                $partyQuery->where('name', 'like', '%' . $search . '%');
                            });
                    });
            });
        }

        if ($request->filled('loan_no')) {
            $query->whereHas('loanAccount', function ($loanQuery) use ($request) {
                $loanQuery->where('loan_no', 'like', '%' . $request->loan_no . '%');
            });
        }

        if ($request->filled('loan_account_id')) {
            $query->where('loan_account_id', $request->loan_account_id);
        }

        if ($request->filled('party_account_id')) {
            $query->whereHas('loanAccount', function ($loanQuery) use ($request) {
                $loanQuery->where('party_account_id', $request->party_account_id);
            });
        }

        if ($request->filled('payment_source')) {
            if ($request->payment_source === 'saving') {
                $query->whereHas('savingLedgers', function ($ledgerQuery) {
                    $ledgerQuery->active()->where('type', 'withdraw');
                });
            } elseif ($request->payment_source === 'account') {
                $query->whereDoesntHave('savingLedgers', function ($ledgerQuery) {
                    $ledgerQuery->active()->where('type', 'withdraw');
                });
            }
        }

        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('payment_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('payment_date', '<=', $request->date_to);
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('status', LoanPayment::STATUS_ACTIVE);
            } elseif ($request->status === 'cancelled') {
                $query->where('status', LoanPayment::STATUS_CANCELLED);
            }
        }

        return $query->latest('payment_date')->latest('id');
    }

    private function activePaymentSummaryQuery($baseQuery)
    {
        return (clone $baseQuery)->where('status', LoanPayment::STATUS_ACTIVE);
    }

    private function generatePaymentReference(int $companyId, FinancialYear $activeFy): string
    {
        $last = LoanPayment::where('company_id', $companyId)
            ->where('financial_year_id', $activeFy->id)
            ->lockForUpdate()
            ->latest('id')
            ->first();

        $next = 1;

        if ($last && $last->reference_no) {
            $parts = explode('-', $last->reference_no);
            $next = ((int) end($parts)) + 1;
        }

        return 'LPAY-'
            . $companyId
            . '-'
            . $activeFy->name
            . '-'
            . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function validateStoreRequest(Request $request, int $companyId): void
    {
        $request->validate([
            'loan_account_id' => [
                'required',
                'integer',
                ValidationService::existsForCompany('loan_accounts', $companyId),
            ],
            'financial_year_id' => [
                'required',
                'integer',
                ValidationService::existsForCompany('financial_years', $companyId),
            ],
            'payment_source' => ['required', Rule::in(['account', 'saving'])],
            'account_id' => [
                'required_if:payment_source,account',
                'nullable',
                'integer',
                ValidationService::existsForCompany('accounts', $companyId),
            ],
            'payment_date' => ValidationService::requiredDate(),
            'next_payment_date' => ValidationService::date(),
            'principal_amount' => 'required|numeric|min:0.01',
            'interest_amount' => ValidationService::amount(),
            'fine_amount' => ValidationService::amount(),
            'saving_amount' => ValidationService::amount(),
            'attachment' => ValidationService::document(5120),
            'note' => ValidationService::text(),
        ]);
    }

    private function validateEditRequest(Request $request): void
    {
        $request->validate([
            'payment_date' => ValidationService::requiredDate(),
            'next_payment_date' => ValidationService::date(),
            'attachment' => ValidationService::document(5120),
            'note' => ValidationService::text(),
        ]);
    }

    public function index(Request $request)
    {
        $this->authorizeCompanyPermission('view_loan_payment');

        $companyId = auth()->user()->company_id;
        $filteredQuery = $this->filteredPaymentQuery($request);
        $summaryQuery = $this->activePaymentSummaryQuery($filteredQuery);

        $totalPayments = (clone $summaryQuery)->count();
        $totalPrincipal = (clone $summaryQuery)->sum('principal_amount');
        $totalInterest = (clone $summaryQuery)->sum('interest_amount');
        $totalFine = (clone $summaryQuery)->sum('fine_amount');
        $totalSaving = (clone $summaryQuery)->sum('saving_amount');
        $totalAmount = (clone $summaryQuery)->sum('total_amount');

        $allowedPerPage = [10, 20, 50, 100, 200];
        $perPage = (int) $request->get('per_page', 10);

        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $payments = $filteredQuery
            ->paginate($perPage)
            ->withQueryString();

        $loanAccounts = LoanAccount::where('company_id', $companyId)
            ->orderBy('loan_no')
            ->get(['id', 'loan_no', 'loan_name']);

        $partyAccounts = PartyAccount::where('company_id', $companyId)
            ->where('status', PartyAccount::STATUS_ACTIVE)
            ->orderBy('name')
            ->get(['id', 'name']);

        $accounts = Account::where('company_id', $companyId)
            ->where('status', 1)
            ->orderBy('account_name')
            ->get(['id', 'account_name']);

        return view(
            'company.loan-payment.index',
            compact(
                'payments',
                'perPage',
                'loanAccounts',
                'partyAccounts',
                'accounts',
                'totalPayments',
                'totalPrincipal',
                'totalInterest',
                'totalFine',
                'totalSaving',
                'totalAmount'
            )
        );
    }

    public function create($id)
    {
        $this->authorizeCompanyPermission('create_loan_payment');

        $companyId = auth()->user()->company_id;

        $loan = LoanAccount::with(['partyAccount', 'financialYear'])
            ->where('company_id', $companyId)
            ->findOrFail($id);

        $accounts = Account::where('company_id', $companyId)
            ->where('status', 1)
            ->get();

        $savingBalance = LoanSavingLedger::where('company_id', $companyId)
            ->where('loan_account_id', $loan->id)
            ->active()
            ->latest('id')
            ->value('balance_after') ?? 0;

        $activeFy = FinancialYear::where('company_id', $companyId)
            ->where('is_active', 1)
            ->first();

        return view(
            'company.loan-payment.create',
            compact('loan', 'accounts', 'savingBalance', 'activeFy')
        );
    }

    public function store(Request $request)
    {
        $this->authorizeCompanyPermission('create_loan_payment');
        $this->validateStoreRequest($request, auth()->user()->company_id);

        $paymentSource = $request->input('payment_source', 'account');

        if ($paymentSource === 'saving') {
            $request->merge(['saving_amount' => 0]);
        }

        $paymentId = null;

        try {
            DB::transaction(function () use ($request, $paymentSource, &$paymentId) {
                $companyId = auth()->user()->company_id;
                $activeFy = $this->assertActiveFinancialYear($companyId);

                if ((int) $request->financial_year_id !== (int) $activeFy->id) {
                    throw new \Exception('Financial year must be the active financial year.');
                }

                $this->assertDateWithinFinancialYear(
                    $request->payment_date,
                    $activeFy,
                    'Payment date must be inside the active financial year.'
                );

                if ($request->filled('next_payment_date')) {
                    $this->assertDateWithinFinancialYear(
                        $request->next_payment_date,
                        $activeFy,
                        'Next payment date must be inside the active financial year.'
                    );
                }

                $loan = LoanAccount::with('partyAccount')
                    ->where('company_id', $companyId)
                    ->lockForUpdate()
                    ->findOrFail($request->loan_account_id);

                $party = $loan->partyAccount;

                if (!$party) {
                    throw new \Exception('Party account not found for this loan.');
                }

                if ((float) $request->principal_amount > (float) $loan->remaining_principal) {
                    throw new \Exception('Principal exceeds remaining.');
                }

                $principal = (float) $request->principal_amount;
                $interest = (float) ($request->interest_amount ?? 0);
                $fine = (float) ($request->fine_amount ?? 0);
                $savingAmount = (float) ($request->saving_amount ?? 0);
                $referenceNo = $this->generatePaymentReference($companyId, $activeFy);
                $file = $this->storeAttachment($request, $companyId);
                $newRemaining = (float) $loan->remaining_principal - $principal;

                if ($paymentSource === 'saving') {
                    $paymentId = $this->storeSavingWithdrawPayment(
                        $request,
                        $loan,
                        $party,
                        $companyId,
                        (int) $activeFy->id,
                        $referenceNo,
                        $principal,
                        $interest,
                        $fine,
                        $newRemaining,
                        $file
                    );

                    return;
                }

                $paymentId = $this->storeAccountPayment(
                    $request,
                    $loan,
                    $party,
                    $companyId,
                    (int) $activeFy->id,
                    $referenceNo,
                    $principal,
                    $interest,
                    $fine,
                    $savingAmount,
                    $newRemaining,
                    $file
                );
            });
        } catch (\Exception $exception) {
            return back()
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('company.loan-payment.show', $paymentId)
            ->with('success', 'Payment saved.');
    }

    public function show($id)
    {
        $this->authorizeCompanyPermission('view_loan_payment');

        $payment = $this->findCompanyPayment($id, [
            'loanAccount.partyAccount',
            'loanAccount.account',
            'loanAccount.financialYear',
            'account',
            'financialYear',
            'createdBy',
            'updatedBy',
            'cancelledBy',
            'savingLedgers',
        ]);

        return view('company.loan-payment.show', compact('payment'));
    }

    public function edit($id)
    {
        $this->authorizeCompanyPermission('edit_loan_payment');

        $payment = $this->findCompanyPayment($id, [
            'loanAccount.partyAccount',
            'account',
            'financialYear',
        ]);

        if (!$payment->isActive()) {
            return redirect()
                ->route('company.loan-payment.show', $payment->id)
                ->with('error', 'Cancelled payment cannot be edited.');
        }

        return view('company.loan-payment.edit', compact('payment'));
    }

    public function update(Request $request, $id)
    {
        $this->authorizeCompanyPermission('edit_loan_payment');
        $this->validateEditRequest($request);

        try {
            DB::transaction(function () use ($request, $id) {
                $companyId = auth()->user()->company_id;

                $payment = LoanPayment::where('company_id', $companyId)
                    ->lockForUpdate()
                    ->findOrFail($id);

                $this->guardEditableTransaction(
                    $payment,
                    'Cancelled payment cannot be edited.',
                    LoanPayment::STATUS_CANCELLED
                );

                $activeFy = $this->assertActiveFinancialYear($companyId);

                $this->assertTransactionFinancialYear(
                    $payment,
                    $activeFy,
                    'Loan payment belongs to another financial year.'
                );

                $this->assertDateWithinFinancialYear(
                    $request->payment_date,
                    $activeFy,
                    'Payment date must be inside the active financial year.'
                );

                if ($request->filled('next_payment_date')) {
                    $this->assertDateWithinFinancialYear(
                        $request->next_payment_date,
                        $activeFy,
                        'Next payment date must be inside the active financial year.'
                    );
                }

                $file = $payment->attachment;

                if ($request->hasFile('attachment')) {
                    $file = $this->storeAttachment($request, $companyId);
                }

                $paymentDate = Carbon::parse($request->payment_date)->toDateString();
                $nextPaymentDate = $request->filled('next_payment_date')
                    ? Carbon::parse($request->next_payment_date)->toDateString()
                    : $payment->next_payment_date;

                $payment->update($this->appendUpdatedBy([
                    'payment_date' => $paymentDate,
                    'next_payment_date' => $nextPaymentDate,
                    'attachment' => $file,
                    'note' => $request->note,
                ], $payment));

                $payment->savingLedgers()
                    ->active()
                    ->update(['date' => $paymentDate]);

                if ($nextPaymentDate) {
                    LoanAccount::where('company_id', $companyId)
                        ->where('id', $payment->loan_account_id)
                        ->update(['next_payment_date' => $nextPaymentDate]);
                }

                $this->logDocumentationEdit('Loan payment updated.', $payment);
            });
        } catch (\Exception $exception) {
            return back()
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('company.loan-payment.show', $id)
            ->with('success', 'Payment updated successfully.');
    }

    public function cancel(Request $request, $id)
    {
        $this->authorizeCompanyPermission('cancel_loan_payment');

        $request->validate([
            'cancel_date' => ValidationService::requiredDate(),
            'cancel_reason' => ValidationService::requiredString(500),
        ]);

        try {
            DB::transaction(function () use ($request, $id) {
                $companyId = auth()->user()->company_id;
                $activeFy = $this->assertActiveFinancialYear($companyId);

                $this->assertDateWithinFinancialYear(
                    $request->cancel_date,
                    $activeFy,
                    'Cancel date must belong to the active financial year.'
                );

                $cancelDate = Carbon::parse($request->cancel_date)->toDateString();
                $cancelReason = trim($request->cancel_reason);

                $payment = LoanPayment::with(['savingLedgers', 'loanAccount'])
                    ->where('company_id', $companyId)
                    ->lockForUpdate()
                    ->findOrFail($id);

                if (!$payment->isActive()) {
                    throw new \Exception('Payment already cancelled.');
                }

                $this->reversePaymentEffects($payment, $companyId, $cancelDate, $cancelReason);

                $payment->update([
                    'status' => LoanPayment::STATUS_CANCELLED,
                    'cancelled_by' => auth()->id(),
                    'cancelled_date' => $cancelDate,
                    'cancel_reason' => $cancelReason,
                    'note' => trim(($payment->note ?? '') . ' [Cancelled: ' . $cancelReason . ']'),
                ]);
            });
        } catch (\Exception $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Payment cancelled successfully.');
    }

    public function print($id)
    {
        $this->authorizeCompanyPermission('print_loan_payment');

        $payment = $this->findCompanyPayment($id, [
            'loanAccount.partyAccount',
            'loanAccount.account',
            'account',
            'financialYear',
            'createdBy',
            'cancelledBy',
        ]);

        return view('company.loan-payment.print', compact('payment'));
    }

    private function reversePaymentEffects(
        LoanPayment $payment,
        int $companyId,
        string $cancelDate,
        string $cancelReason
    ): void {
        $loan = LoanAccount::where('company_id', $companyId)
            ->lockForUpdate()
            ->findOrFail($payment->loan_account_id);

        $party = PartyAccount::where('company_id', $companyId)
            ->lockForUpdate()
            ->findOrFail($loan->party_account_id);

        $principal = (float) $payment->principal_amount;
        $newRemaining = (float) $loan->remaining_principal + $principal;

        $loanData = [
            'remaining_principal' => $newRemaining,
        ];

        if ($newRemaining > 0 && !$loan->isCancelled()) {
            $loanData['status'] = LoanAccount::STATUS_ACTIVE;
        }

        $loan->update($loanData);

        $party->increment('current_balance', $principal);

        if ($payment->isPaidFromSaving()) {
            $this->reverseSavingWithdrawPayment($payment, $loan, $companyId, $cancelDate, $cancelReason);

            return;
        }

        $this->reverseAccountPayment($payment, $loan, $companyId, $cancelDate, $cancelReason);
    }

    private function reverseAccountPayment(
        LoanPayment $payment,
        LoanAccount $loan,
        int $companyId,
        string $cancelDate,
        string $cancelReason
    ): void {
        $total = (float) $payment->total_amount;
        $savingAmount = (float) $payment->saving_amount;

        if ($payment->account_id) {
            $account = Account::where('company_id', $companyId)
                ->lockForUpdate()
                ->findOrFail($payment->account_id);

            if ($loan->loan_type === 'taken') {
                $account->increment('current_balance', $total);
            } else {
                if ((float) $account->current_balance < $total) {
                    throw new \Exception('Insufficient account balance to reverse payment.');
                }

                $account->decrement('current_balance', $total);
            }

            $accountTransaction = AccountTransaction::where('company_id', $companyId)
                ->where('reference_type', 'LoanPayment')
                ->where('reference_id', $payment->id)
                ->where('status', 1)
                ->first();

            if ($accountTransaction) {
                AccountBalanceService::reverseTransaction(
                    $accountTransaction,
                    'loan_payment_cancel',
                    'Loan Payment Cancel: ' . $cancelReason,
                    $cancelDate,
                    (int) $payment->financial_year_id
                );
            }
        }

        if ($savingAmount > 0) {
            $this->createSavingLedgerReversal(
                $payment,
                $loan,
                $companyId,
                'withdraw',
                $savingAmount,
                $cancelDate,
                'Saving deposit reversed: ' . $cancelReason
            );
        }

        $payment->savingLedgers()->active()->update(['status' => LoanSavingLedger::STATUS_INACTIVE]);
    }

    private function reverseSavingWithdrawPayment(
        LoanPayment $payment,
        LoanAccount $loan,
        int $companyId,
        string $cancelDate,
        string $cancelReason
    ): void {
        $this->createSavingLedgerReversal(
            $payment,
            $loan,
            $companyId,
            'deposit',
            (float) $payment->total_amount,
            $cancelDate,
            'Saving withdraw reversed: ' . $cancelReason
        );

        $payment->savingLedgers()->active()->update(['status' => LoanSavingLedger::STATUS_INACTIVE]);
    }

    private function createSavingLedgerReversal(
        LoanPayment $payment,
        LoanAccount $loan,
        int $companyId,
        string $type,
        float $amount,
        string $date,
        string $note
    ): void {
        $currentSaving = LoanSavingLedger::where('company_id', $companyId)
            ->where('loan_account_id', $loan->id)
            ->active()
            ->latest('id')
            ->value('balance_after') ?? 0;

        if ($type === 'withdraw' && $amount > (float) $currentSaving) {
            throw new \Exception('Insufficient saving balance to reverse payment.');
        }

        $balanceAfter = $type === 'deposit'
            ? (float) $currentSaving + $amount
            : (float) $currentSaving - $amount;

        LoanSavingLedger::create([
            'company_id' => $companyId,
            'financial_year_id' => $payment->financial_year_id,
            'loan_account_id' => $loan->id,
            'loan_payment_id' => $payment->id,
            'account_id' => $payment->account_id ?? $loan->account_id,
            'type' => $type,
            'amount' => $amount,
            'balance_after' => $balanceAfter,
            'date' => $date,
            'note' => $note,
            'created_by' => auth()->id(),
            'status' => LoanSavingLedger::STATUS_ACTIVE,
        ]);
    }

    private function storeAccountPayment(
        Request $request,
        LoanAccount $loan,
        PartyAccount $party,
        int $companyId,
        int $financialYearId,
        string $referenceNo,
        float $principal,
        float $interest,
        float $fine,
        float $savingAmount,
        float $newRemaining,
        ?string $file
    ): int {
        $total = $principal + $interest + $fine + $savingAmount;

        $account = Account::where('company_id', $companyId)
            ->lockForUpdate()
            ->findOrFail($request->account_id);

        if ($loan->loan_type === 'taken') {
            if ((float) $account->current_balance < $total) {
                throw new \Exception('Insufficient balance.');
            }

            $account->decrement('current_balance', $total);
        } else {
            $account->increment('current_balance', $total);
        }

        $party->decrement('current_balance', $principal);

        $payment = LoanPayment::create([
            'company_id' => $companyId,
            'financial_year_id' => $financialYearId,
            'loan_account_id' => $loan->id,
            'account_id' => $account->id,
            'reference_no' => $referenceNo,
            'principal_amount' => $principal,
            'interest_amount' => $interest,
            'fine_amount' => $fine,
            'saving_amount' => $savingAmount,
            'total_amount' => $total,
            'remaining_principal' => $newRemaining,
            'payment_date' => $request->payment_date,
            'next_payment_date' => $request->next_payment_date,
            'attachment' => $file,
            'note' => $request->note,
            'created_by' => auth()->id(),
            'status' => LoanPayment::STATUS_ACTIVE,
        ]);

        AccountBalanceService::createTransaction([
            'company_id'        => $companyId,
            'financial_year_id'   => $financialYearId,
            'account_id'          => $account->id,
            'transaction_date'  => $request->payment_date,
            'voucher_no'          => $referenceNo,
            'reference_type'      => 'LoanPayment',
            'reference_id'        => $payment->id,
            'description'         => 'Loan Payment',
            'debit'               => $loan->loan_type === 'given' ? $total : 0,
            'credit'              => $loan->loan_type === 'taken' ? $total : 0,
            'created_by'          => auth()->id(),
        ], false);

        if ($savingAmount > 0) {
            $previousSaving = LoanSavingLedger::where('company_id', $companyId)
                ->where('loan_account_id', $loan->id)
                ->active()
                ->latest('id')
                ->value('balance_after') ?? 0;

            LoanSavingLedger::create([
                'company_id' => $companyId,
                'financial_year_id' => $financialYearId,
                'loan_account_id' => $loan->id,
                'loan_payment_id' => $payment->id,
                'account_id' => $account->id,
                'type' => 'deposit',
                'amount' => $savingAmount,
                'balance_after' => (float) $previousSaving + $savingAmount,
                'date' => $request->payment_date,
                'attachment' => $file,
                'note' => $request->note,
                'created_by' => auth()->id(),
                'status' => LoanSavingLedger::STATUS_ACTIVE,
            ]);
        }

        $this->finalizeLoanAfterPayment($loan, $newRemaining, $request->next_payment_date);

        return $payment->id;
    }

    private function storeSavingWithdrawPayment(
        Request $request,
        LoanAccount $loan,
        PartyAccount $party,
        int $companyId,
        int $financialYearId,
        string $referenceNo,
        float $principal,
        float $interest,
        float $fine,
        float $newRemaining,
        ?string $file
    ): int {
        $total = $principal + $interest + $fine;

        if ($total <= 0) {
            throw new \Exception('Payment amount must be greater than zero.');
        }

        $currentSaving = LoanSavingLedger::where('company_id', $companyId)
            ->where('loan_account_id', $loan->id)
            ->active()
            ->latest('id')
            ->value('balance_after') ?? 0;

        if ($total > (float) $currentSaving) {
            throw new \Exception('Insufficient saving balance.');
        }

        $party->decrement('current_balance', $principal);

        $payment = LoanPayment::create([
            'company_id' => $companyId,
            'financial_year_id' => $financialYearId,
            'loan_account_id' => $loan->id,
            'account_id' => null,
            'reference_no' => $referenceNo,
            'principal_amount' => $principal,
            'interest_amount' => $interest,
            'fine_amount' => $fine,
            'saving_amount' => 0,
            'total_amount' => $total,
            'remaining_principal' => $newRemaining,
            'payment_date' => $request->payment_date,
            'next_payment_date' => $request->next_payment_date,
            'attachment' => $file,
            'note' => $request->note,
            'created_by' => auth()->id(),
            'status' => LoanPayment::STATUS_ACTIVE,
        ]);

        LoanSavingLedger::create([
            'company_id' => $companyId,
            'financial_year_id' => $financialYearId,
            'loan_account_id' => $loan->id,
            'loan_payment_id' => $payment->id,
            'account_id' => $loan->account_id,
            'type' => 'withdraw',
            'amount' => $total,
            'balance_after' => (float) $currentSaving - $total,
            'date' => $request->payment_date,
            'attachment' => $file,
            'note' => $request->note,
            'created_by' => auth()->id(),
            'status' => LoanSavingLedger::STATUS_ACTIVE,
        ]);

        $this->finalizeLoanAfterPayment($loan, $newRemaining, $request->next_payment_date);

        return $payment->id;
    }

    private function finalizeLoanAfterPayment(
        LoanAccount $loan,
        float $newRemaining,
        ?string $nextPaymentDate
    ): void {
        $data = [
            'remaining_principal' => $newRemaining,
        ];

        if ($nextPaymentDate) {
            $data['next_payment_date'] = $nextPaymentDate;
        }

        $loan->update($data);
    }

    private function storeAttachment(Request $request, int $companyId): ?string
    {
        if (!$request->hasFile('attachment')) {
            return null;
        }

        $folder = 'companies/' . $companyId . '/loan-payments';

        if (!file_exists(public_path($folder))) {
            mkdir(public_path($folder), 0777, true);
        }

        $uploadedFile = $request->file('attachment');
        $name = $uploadedFile->hashName();
        $uploadedFile->move(public_path($folder), $name);

        return $folder . '/' . $name;
    }
}
