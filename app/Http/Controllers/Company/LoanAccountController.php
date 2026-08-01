<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesCompanyPermission;
use App\Http\Controllers\Concerns\AuthorizesSubscriptionModule;
use App\Http\Controllers\Concerns\HandlesTransactionDocumentationEdit;
use Illuminate\Routing\Controllers\HasMiddleware;
use App\Models\Account;
use App\Models\FinancialYear;
use App\Models\LoanAccount;
use App\Models\LoanSavingLedger;
use App\Models\PartyAccount;
use App\Models\AccountTransaction;
use App\Services\AccountBalanceService;
use App\Services\ValidationService;
use App\Services\Money;
use App\Services\Accounting\Integrations\LoanAccountingIntegrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LoanAccountController extends Controller implements HasMiddleware
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

    private function validateLoanAccountRequest(Request $request, int $companyId, bool $isUpdate = false): void
    {
        $rules = [
            'financial_year_id' => [
                'required',
                'integer',
                ValidationService::existsForCompany('financial_years', $companyId),
            ],
            'status'            => ValidationService::enum(['0', '1', 0, 1]),
            'attachment'        => ValidationService::document(5120),
            'note'              => ValidationService::text(),
            'request_key'       => $isUpdate ? 'nullable|uuid' : 'required|uuid',
        ];

        if (!$isUpdate) {
            $rules = array_merge($rules, [
                'loan_name'        => ValidationService::requiredString(255),
                'loan_type'        => ValidationService::requiredEnum([LoanAccount::TYPE_TAKEN, LoanAccount::TYPE_GIVEN]),
                'party_account_id' => [
                    'required',
                    'integer',
                    ValidationService::existsForCompany('party_accounts', $companyId),
                ],
                'account_id'       => [
                    'required',
                    'integer',
                    ValidationService::existsForCompany('accounts', $companyId),
                ],
                'principal_amount' => 'required|numeric|min:0.01',
                'interest_rate'    => ValidationService::amount(),
                'start_date'       => ValidationService::requiredDate(),
                'end_date'         => ValidationService::date(),
            ]);
        } else {
            $rules = array_merge($rules, [
                'loan_name' => ValidationService::requiredString(255),
            ]);
        }

        $request->validate($rules);
    }

    private function generateLoanNo(int $companyId): string
    {
        $year = now()->year;

        $last = LoanAccount::where('company_id', $companyId)
            ->latest('id')
            ->first();

        $next = 1;

        if ($last) {
            $parts = explode('-', $last->loan_no);
            $next = ((int) end($parts)) + 1;
        }

        return 'LOAN-'
            . $companyId
            . '-'
            . $year
            . '-'
            . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function filteredLoanQuery(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $query = LoanAccount::with([
            'partyAccount:id,company_id,name,phone',
            'account:id,company_id,account_name',
            'createdBy:id,name',
        ])->where('company_id', $companyId);

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('loan_no', 'like', '%' . $search . '%')
                    ->orWhere('loan_name', 'like', '%' . $search . '%')
                    ->orWhereHas('partyAccount', function ($partyQuery) use ($search) {
                        $partyQuery->where('name', 'like', '%' . $search . '%')
                            ->orWhere('phone', 'like', '%' . $search . '%');
                    });
            });
        }

        if ($request->filled('loan_no')) {
            $query->where('loan_no', 'like', '%' . $request->loan_no . '%');
        }

        if ($request->filled('party_account_id')) {
            $query->where('party_account_id', $request->party_account_id);
        }

        if ($request->filled('loan_type')) {
            $query->where('loan_type', $request->loan_type);
        }

        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        if ($request->filled('status')) {
            match ($request->status) {
                'active' => $query->where('status', LoanAccount::STATUS_ACTIVE)
                    ->where('remaining_principal', '>', 0),
                'closed' => $query->where('status', LoanAccount::STATUS_ACTIVE)
                    ->where('remaining_principal', '<=', 0),
                'cancelled' => $query->where('status', LoanAccount::STATUS_CANCELLED),
                default => null,
            };
        } else {
            $query->where('status', LoanAccount::STATUS_ACTIVE);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('start_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('start_date', '<=', $request->date_to);
        }

        return $query->latest('start_date')->latest('id');
    }

    public function index(Request $request)
    {
        $this->authorizeCompanyPermission('view_loan_account');

        $companyId = auth()->user()->company_id;

        $filteredQuery = $this->filteredLoanQuery($request);

        $summaryQuery = clone $filteredQuery;

        $financialSummary = (clone $summaryQuery)->where('status', LoanAccount::STATUS_ACTIVE);

        $totalLoans = (clone $summaryQuery)->count();
        $totalPrincipalAmount = (clone $financialSummary)->sum('principal_amount');
        $totalRemainingPrincipal = (clone $financialSummary)->sum('remaining_principal');
        $activeLoans = (clone $summaryQuery)
            ->where('status', LoanAccount::STATUS_ACTIVE)
            ->where('remaining_principal', '>', 0)
            ->count();
        $closedLoans = (clone $summaryQuery)
            ->where('status', LoanAccount::STATUS_ACTIVE)
            ->where('remaining_principal', '<=', 0)
            ->count();
        $cancelledLoans = (clone $summaryQuery)
            ->where('status', LoanAccount::STATUS_CANCELLED)
            ->count();

        $allowedPerPage = [10, 20, 50, 100, 200];
        $perPage = (int) $request->get('per_page', 10);

        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $loans = $filteredQuery
            ->paginate($perPage)
            ->withQueryString();

        $partyAccounts = PartyAccount::where('company_id', $companyId)
            ->where('status', PartyAccount::STATUS_ACTIVE)
            ->orderBy('name')
            ->get(['id', 'name']);

        $accounts = Account::where('company_id', $companyId)
            ->where('status', 1)
            ->orderBy('account_name')
            ->get(['id', 'account_name']);

        return view(
            'company.loan-account.index',
            compact(
                'loans',
                'perPage',
                'partyAccounts',
                'accounts',
                'totalLoans',
                'totalPrincipalAmount',
                'totalRemainingPrincipal',
                'activeLoans',
                'closedLoans',
                'cancelledLoans'
            )
        );
    }

    public function create()
    {
        $this->authorizeCompanyPermission('create_loan_account');

        $companyId = auth()->user()->company_id;

        $activeFy = FinancialYear::where('company_id', $companyId)
            ->where('is_active', 1)
            ->first();

        $partyAccounts = PartyAccount::where('company_id', $companyId)
            ->where('status', PartyAccount::STATUS_ACTIVE)
            ->orderBy('name')
            ->get(['id', 'name']);

        $accounts = Account::where('company_id', $companyId)
            ->where('status', 1)
            ->orderBy('account_name')
            ->get(['id', 'account_name', 'current_balance']);

        $loanNo = $this->generateLoanNo($companyId);

        return view(
            'company.loan-account.create',
            compact('loanNo', 'partyAccounts', 'accounts', 'activeFy')
        );
    }

    public function store(Request $request)
    {
        $this->authorizeCompanyPermission('create_loan_account');

        $companyId = auth()->user()->company_id;

        $this->validateLoanAccountRequest($request, $companyId);

        try {
            DB::transaction(function () use ($request, $companyId) {
                $activeFy = $this->assertActiveFinancialYear($companyId);

                if (LoanAccount::where('company_id', $companyId)->where('request_key', $request->request_key)->lockForUpdate()->exists()) {
                    throw new \Exception('This Loan request has already been processed.');
                }

                if ((int) $request->financial_year_id !== (int) $activeFy->id) {
                    throw new \Exception('Financial year must be the active financial year.');
                }

                $this->assertDateWithinFinancialYear(
                    $request->start_date,
                    $activeFy,
                    'Loan date must be inside the active financial year.'
                );

                $account = Account::where('company_id', $companyId)
                    ->lockForUpdate()
                    ->findOrFail($request->account_id);

                $party = PartyAccount::where('company_id', $companyId)
                    ->lockForUpdate()
                    ->findOrFail($request->party_account_id);

                $file = null;

                if ($request->hasFile('attachment')) {
                    $folder = 'companies/' . $companyId . '/loans';

                    if (!file_exists(public_path($folder))) {
                        mkdir(public_path($folder), 0777, true);
                    }

                    $name = time()
                        . '_'
                        . $request->file('attachment')->getClientOriginalName();

                    $request->file('attachment')->move(public_path($folder), $name);

                    $file = $folder . '/' . $name;
                }

                $loanNo = $this->generateLoanNo($companyId);

                if (LoanAccount::where('company_id', $companyId)
                    ->where('loan_no', $loanNo)
                    ->exists()) {
                    throw new \Exception('Loan number already exists. Please try again.');
                }

                $loan = LoanAccount::create([
                    'company_id'          => $companyId,
                    'financial_year_id'   => $activeFy->id,
                    'loan_no'             => $loanNo,
                    'request_key'         => $request->request_key,
                    'loan_name'           => $request->loan_name,
                    'loan_type'           => $request->loan_type,
                    'party_account_id'    => $request->party_account_id,
                    'account_id'          => $request->account_id,
                    'principal_amount'    => $request->principal_amount,
                    'interest_rate'       => $request->interest_rate ?? 0,
                    'remaining_principal' => $request->principal_amount,
                    'start_date'          => $request->start_date,
                    'end_date'            => $request->end_date,
                    'next_payment_date'   => $request->next_payment_date,
                    'attachment'          => $file,
                    'note'                => $request->note,
                    'created_by'          => auth()->id(),
                    'status'              => LoanAccount::STATUS_ACTIVE,
                ]);

                $principal = Money::normalize($request->principal_amount);

                if ($request->loan_type === LoanAccount::TYPE_TAKEN) {
                    $account->increment(
                        'current_balance',
                        $principal
                    );

                    $party->increment(
                        'current_balance',
                        $principal
                    );

                    AccountBalanceService::createTransaction([
                        'company_id'        => $companyId,
                        'financial_year_id' => $activeFy->id,
                        'account_id'        => $account->id,
                        'transaction_date'  => $request->start_date,
                        'voucher_no'        => $loanNo,
                        'reference_type'    => 'LoanAccount',
                        'reference_id'      => $loan->id,
                        'description'       => 'Loan Taken',
                        'debit'             => $principal,
                        'credit'            => 0,
                        'created_by'        => auth()->id(),
                    ], false);
                } else {
                    if (Money::compare($account->current_balance, $principal) < 0) {
                        throw new \Exception('Insufficient balance.');
                    }

                    $account->decrement(
                        'current_balance',
                        $principal
                    );

                    $party->increment(
                        'current_balance',
                        $principal
                    );

                    AccountBalanceService::createTransaction([
                        'company_id'        => $companyId,
                        'financial_year_id' => $activeFy->id,
                        'account_id'        => $account->id,
                        'transaction_date'  => $request->start_date,
                        'voucher_no'        => $loanNo,
                        'reference_type'    => 'LoanAccount',
                        'reference_id'      => $loan->id,
                        'description'       => 'Loan Given',
                        'debit'             => 0,
                        'credit'            => $principal,
                        'created_by'        => auth()->id(),
                    ], false);
                }

                app(LoanAccountingIntegrationService::class)->postLoanCreation($loan->fresh(['account']));
            });
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('company.loan-account.index')
            ->with('success', 'Loan created.');
    }

    public function show($id)
    {
        $this->authorizeCompanyPermission('view_loan_account');

        $loan = LoanAccount::with([
            'partyAccount',
            'account',
            'payments',
            'savingLedgers' => fn ($query) => $query->active()->orderBy('date')->orderBy('id'),
            'createdBy',
            'cancelledBy',
            'financialYear',
        ])
            ->where('company_id', auth()->user()->company_id)
            ->findOrFail($id);

        $totalSavingDeposit = $loan->savingLedgers()
            ->active()
            ->where('type', LoanSavingLedger::TYPE_DEPOSIT)
            ->sum('amount');

        $totalSavingWithdraw = $loan->savingLedgers()
            ->active()
            ->where('type', LoanSavingLedger::TYPE_WITHDRAW)
            ->sum('amount');

        $currentSavingBalance = $loan->savingLedgers()
            ->active()
            ->latest('id')
            ->value('balance_after') ?? 0;

        $canCancel = $this->loanCanBeCancelled($loan);

        return view(
            'company.loan-account.show',
            compact(
                'loan',
                'totalSavingDeposit',
                'totalSavingWithdraw',
                'currentSavingBalance',
                'canCancel'
            )
        );
    }

    public function edit($id)
    {
        $this->authorizeCompanyPermission('edit_loan_account');

        $loan = LoanAccount::where('company_id', auth()->user()->company_id)
            ->findOrFail($id);

        if ($loan->isCancelled()) {
            return redirect()
                ->route('company.loan-account.show', $loan->id)
                ->with('error', 'Cancelled loan cannot be edited.');
        }

        return view(
            'company.loan-account.edit',
            compact('loan')
        );
    }

    public function update(Request $request, $id)
    {
        $this->authorizeCompanyPermission('edit_loan_account');

        $companyId = auth()->user()->company_id;

        $this->validateLoanAccountRequest($request, $companyId, true);

        $loan = LoanAccount::where('company_id', $companyId)
            ->findOrFail($id);

        if ($loan->isCancelled()) {
            return back()->with('error', 'Cancelled loan cannot be edited.');
        }

        $data = [
            'loan_name'         => $request->loan_name,
            'note'              => $request->note,
            'end_date'          => $request->end_date,
            'next_payment_date' => $request->next_payment_date,
            'status'            => (int) ($loan->status),
            'updated_by'        => auth()->id(),
        ];

        if ($request->hasFile('attachment')) {
            if ($loan->attachment && file_exists(public_path($loan->attachment))) {
                unlink(public_path($loan->attachment));
            }

            $folder = 'companies/' . $companyId . '/loans';

            if (!file_exists(public_path($folder))) {
                mkdir(public_path($folder), 0777, true);
            }

            $name = time()
                . '_'
                . $request->file('attachment')->getClientOriginalName();

            $request->file('attachment')->move(public_path($folder), $name);

            $data['attachment'] = $folder . '/' . $name;
        }

        $loan->update($data);

        return redirect()
            ->route('company.loan-account.show', $loan->id)
            ->with('success', 'Loan updated.');
    }

    private function loanCanBeCancelled(LoanAccount $loan): bool
    {
        if ((int) $loan->status !== LoanAccount::STATUS_ACTIVE) {
            return false;
        }

        if ($loan->payments()->active()->exists()) {
            return false;
        }

        $savingBalance = $loan->savingLedgers()->active()->latest('id')->value('balance_after') ?? '0.00';
        if (Money::compare($savingBalance, '0.00') !== 0) {
            return false;
        }

        if (Money::compare($loan->remaining_principal, $loan->principal_amount) !== 0) {
            return false;
        }

        return true;
    }

    private function assertNoActiveLoanPayments(LoanAccount $loan): void
    {
        if ($loan->payments()->active()->exists()) {
            throw new \Exception(
                'Loan cannot be cancelled because active loan payments exist. Cancel all loan payments before cancelling the loan.'
            );
        }
    }

    private function assertLoanCanBeCancelled(LoanAccount $loan): void
    {
        $this->assertNoActiveLoanPayments($loan);

        if ((int) $loan->status !== LoanAccount::STATUS_ACTIVE) {
            throw new \Exception('Only active loans can be cancelled.');
        }

        $savingBalance = $loan->savingLedgers()->active()->latest('id')->value('balance_after') ?? '0.00';
        if (Money::compare($savingBalance, '0.00') !== 0) {
            throw new \Exception(
                'Loan cannot be cancelled because active saving ledger entries exist.'
            );
        }

        if (Money::compare($loan->remaining_principal, $loan->principal_amount) !== 0) {
            throw new \Exception(
                'Loan cannot be cancelled because the remaining principal differs from the original principal amount.'
            );
        }
    }

    private function reverseLoanBalances(LoanAccount $loan, int $companyId): void
    {
        $principal = Money::normalize($loan->principal_amount);

        $account = Account::where('company_id', $companyId)
            ->lockForUpdate()
            ->findOrFail($loan->account_id);

        $party = PartyAccount::where('company_id', $companyId)
            ->lockForUpdate()
            ->findOrFail($loan->party_account_id);

        if ($loan->loan_type === LoanAccount::TYPE_GIVEN) {
            $account->increment('current_balance', $principal);

            if (Money::compare($party->current_balance, $principal) < 0) {
                throw new \Exception('Insufficient party balance to cancel this loan.');
            }

            $party->decrement('current_balance', $principal);
        } else {
            if (Money::compare($account->current_balance, $principal) < 0) {
                throw new \Exception('Insufficient account balance to cancel this loan.');
            }

            $account->decrement('current_balance', $principal);
            $party->decrement('current_balance', $principal);
        }
    }

    public function cancel(Request $request, $id)
    {
        $this->authorizeCompanyPermission('cancel_loan_account');

        $companyId = auth()->user()->company_id;

        $request->validate([
            'cancel_date' => ValidationService::cancelDate(),
            'cancel_reason' => ValidationService::cancelReason(),
        ]);

        $loan = LoanAccount::where('company_id', $companyId)
            ->findOrFail($id);

        try {
            $this->assertLoanCanBeCancelled($loan);
        } catch (\Exception $exception) {
            return back()->with('error', $exception->getMessage());
        }

        try {
            DB::transaction(function () use ($request, $loan, $companyId) {
                $activeFy = $this->assertActiveFinancialYear($companyId);
                $this->assertDateWithinFinancialYear($request->cancel_date, $activeFy, 'Cancel date must belong to the active financial year.');
                $lockedLoan = LoanAccount::where('company_id', $companyId)
                    ->lockForUpdate()
                    ->findOrFail($loan->id);

                $this->assertLoanCanBeCancelled($lockedLoan);

                $this->assertTransactionFinancialYear($lockedLoan, $activeFy, 'Only a Loan in the active financial year may be cancelled.');

                $accountTransaction = $this->strictOriginalAccountTransaction($lockedLoan, $companyId);

                $this->reverseLoanBalances($lockedLoan, $companyId);

                AccountBalanceService::reverseTransaction(
                    $accountTransaction,
                    'loan_account_cancel',
                    'Loan Account Cancel',
                    $request->cancel_date,
                    (int) $activeFy->id
                );

                app(LoanAccountingIntegrationService::class)->reverse(
                    'loan_account', $lockedLoan->id, $companyId, (int) $activeFy->id,
                    $request->cancel_date, LoanAccount::EVENT_CREATED, $lockedLoan->loan_no, auth()->id()
                );

                $lockedLoan->update([
                    'status'       => LoanAccount::STATUS_CANCELLED,
                    'cancelled_by' => auth()->id(),
                    'cancelled_at' => $request->cancel_date,
                    'cancel_reason' => trim($request->cancel_reason),
                    'note' => trim(($lockedLoan->note ?? '') . ' [Cancelled: ' . trim($request->cancel_reason) . ']'),
                    'updated_by'   => auth()->id(),
                ]);
            });
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('company.loan-account.show', $loan->id)
            ->with('success', 'Loan cancelled.');
    }

    private function strictOriginalAccountTransaction(LoanAccount $loan, int $companyId): AccountTransaction
    {
        $transactions = AccountTransaction::where('company_id', $companyId)
            ->where('reference_type', 'LoanAccount')
            ->where('reference_id', $loan->id)
            ->where('status', 1)
            ->whereNull('reversed_transaction_id')
            ->lockForUpdate()
            ->get();

        if ($transactions->count() !== 1) {
            throw new \RuntimeException('Loan cancellation integrity error: exactly one active original Cash/Bank transaction is required.');
        }

        $transaction = $transactions->first();
        $amount = Money::normalize($loan->principal_amount);
        $expectedDebit = $loan->loan_type === LoanAccount::TYPE_TAKEN ? $amount : '0.00';
        $expectedCredit = $loan->loan_type === LoanAccount::TYPE_GIVEN ? $amount : '0.00';

        if ((int) $transaction->account_id !== (int) $loan->account_id
            || Money::compare($transaction->debit, $expectedDebit) !== 0
            || Money::compare($transaction->credit, $expectedCredit) !== 0) {
            throw new \RuntimeException('Loan cancellation integrity error: the original Cash/Bank transaction does not match the Loan source.');
        }

        return $transaction;
    }
}
