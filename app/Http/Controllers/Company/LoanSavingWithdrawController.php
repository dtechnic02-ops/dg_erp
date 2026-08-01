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
use App\Models\AccountTransaction;
use App\Services\AccountBalanceService;
use App\Services\Money;
use App\Services\ValidationService;
use App\Services\Accounting\Integrations\LoanAccountingIntegrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LoanSavingWithdrawController extends Controller implements HasMiddleware
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

    public function create($id)
    {
        $this->authorizeCompanyPermission('create_loan_saving_withdraw');

        $companyId = auth()->user()->company_id;

        $loan = LoanAccount::with('partyAccount')
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
            'company.loan-saving-withdraw.create',
            compact('loan', 'accounts', 'savingBalance', 'activeFy')
        );
    }

    public function store(Request $request)
    {
        $this->authorizeCompanyPermission('create_loan_saving_withdraw');

        $request->validate([
            'loan_account_id' => ['required', 'integer', ValidationService::existsForCompany('loan_accounts', auth()->user()->company_id)],
            'financial_year_id' => ['required', 'integer', ValidationService::existsForCompany('financial_years', auth()->user()->company_id)],
            'account_id' => ['required', 'integer', ValidationService::existsForCompany('accounts', auth()->user()->company_id)],
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'note' => ValidationService::text(),
            'request_key' => 'required|uuid',
        ]);

        try {
            DB::transaction(function () use ($request) {
                $companyId = auth()->user()->company_id;
                $activeFy = $this->assertActiveFinancialYear($companyId);

                if (LoanSavingLedger::where('company_id', $companyId)->where('request_key', $request->request_key)->lockForUpdate()->exists()) {
                    throw new \Exception('This saving withdrawal request has already been processed.');
                }

                if ((int) $request->financial_year_id !== (int) $activeFy->id) {
                    throw new \Exception('Financial year must be the active financial year.');
                }

                $this->assertDateWithinFinancialYear(
                    $request->date,
                    $activeFy,
                    'Withdraw date must be inside the active financial year.'
                );

                $loan = LoanAccount::where('company_id', $companyId)
                    ->lockForUpdate()
                    ->findOrFail($request->loan_account_id);

                if (! $loan->isActive() || $loan->loan_type !== LoanAccount::TYPE_TAKEN) {
                    throw new \Exception('Saving withdrawal is available only for an active Loan Taken.');
                }

                $account = Account::where('company_id', $companyId)
                    ->lockForUpdate()
                    ->findOrFail($request->account_id);

                $currentSaving = LoanSavingLedger::where('company_id', $companyId)
                    ->where('loan_account_id', $request->loan_account_id)
                    ->active()
                    ->latest('id')
                    ->value('balance_after') ?? 0;

                $amount = Money::normalize($request->amount);

                if (Money::compare($amount, $currentSaving) > 0) {
                    throw new \Exception('Insufficient saving balance.');
                }

                $newBalance = Money::subtract($currentSaving, $amount);

                $ledger = LoanSavingLedger::create([
                    'company_id' => $companyId,
                    'financial_year_id' => $activeFy->id,
                    'loan_account_id' => $request->loan_account_id,
                    'request_key' => $request->request_key,
                    'account_id' => $request->account_id,
                    'type' => LoanSavingLedger::TYPE_WITHDRAW,
                    'amount' => $amount,
                    'balance_after' => $newBalance,
                    'date' => $request->date,
                    'note' => $request->note,
                    'created_by' => auth()->id(),
                    'status' => LoanSavingLedger::STATUS_ACTIVE,
                ]);

                AccountBalanceService::createTransaction([
                    'company_id' => $companyId,
                    'financial_year_id' => $activeFy->id,
                    'account_id' => $account->id,
                    'transaction_date' => $request->date,
                    'voucher_no' => 'LSW-' . $ledger->id,
                    'reference_type' => 'LoanSavingWithdraw',
                    'reference_id' => $ledger->id,
                    'description' => 'Loan compulsory saving withdrawn into company account',
                    'debit' => $amount,
                    'credit' => '0.00',
                    'created_by' => auth()->id(),
                ], false);

                app(LoanAccountingIntegrationService::class)->postSavingWithdrawal($ledger->fresh(['loanAccount', 'account']));
            });
        } catch (\Exception $exception) {
            return back()
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('company.loan-payment.index')
            ->with('success', 'Saving withdrawn.');
    }

    public function cancel(Request $request, $id)
    {
        $this->authorizeCompanyPermission('cancel_loan_saving_withdraw');
        $request->validate([
            'cancel_date' => ValidationService::cancelDate(),
            'cancel_reason' => ValidationService::cancelReason(),
        ]);

        try {
            DB::transaction(function () use ($request, $id) {
                $companyId = auth()->user()->company_id;
                $activeFy = $this->assertActiveFinancialYear($companyId);
                $this->assertDateWithinFinancialYear($request->cancel_date, $activeFy);
                $ledger = LoanSavingLedger::where('company_id', $companyId)->lockForUpdate()->findOrFail($id);

                if ((int) $ledger->status !== LoanSavingLedger::STATUS_ACTIVE || $ledger->type !== LoanSavingLedger::TYPE_WITHDRAW) {
                    throw new \Exception('Only an active standalone saving withdrawal may be cancelled.');
                }
                if ((int) $ledger->financial_year_id !== (int) $activeFy->id) {
                    throw new \Exception('Only a withdrawal in the active financial year may be cancelled.');
                }

                $loan = LoanAccount::where('company_id', $companyId)->lockForUpdate()->findOrFail($ledger->loan_account_id);
                $currentSaving = LoanSavingLedger::where('company_id', $companyId)->where('loan_account_id', $loan->id)->active()->latest('id')->value('balance_after') ?? '0.00';
                $ledger->update(['status' => LoanSavingLedger::STATUS_INACTIVE, 'cancelled_by' => auth()->id(), 'cancelled_date' => $request->cancel_date, 'cancel_reason' => $request->cancel_reason]);

                LoanSavingLedger::create([
                    'company_id' => $companyId, 'financial_year_id' => $activeFy->id, 'loan_account_id' => $loan->id,
                    'account_id' => $ledger->account_id, 'type' => LoanSavingLedger::TYPE_REVERSAL, 'amount' => $ledger->amount,
                    'balance_after' => Money::add($currentSaving, $ledger->amount), 'date' => $request->cancel_date,
                    'note' => 'Withdrawal reversal: ' . $request->cancel_reason, 'created_by' => auth()->id(), 'status' => LoanSavingLedger::STATUS_ACTIVE,
                ]);

                $transactions = AccountTransaction::where('company_id', $companyId)
                    ->where('reference_type', 'LoanSavingWithdraw')
                    ->where('reference_id', $ledger->id)
                    ->where('status', 1)
                    ->whereNull('reversed_transaction_id')
                    ->lockForUpdate()
                    ->get();
                if ($transactions->count() !== 1) {
                    throw new \RuntimeException('Saving withdrawal cancellation integrity error: exactly one active original Cash/Bank transaction is required.');
                }
                $transaction = $transactions->first();
                if ((int) $transaction->account_id !== (int) $ledger->account_id
                    || Money::compare($transaction->debit, $ledger->amount) !== 0
                    || Money::compare($transaction->credit, '0.00') !== 0) {
                    throw new \RuntimeException('Saving withdrawal cancellation integrity error: the original Cash/Bank transaction does not match the withdrawal source.');
                }
                AccountBalanceService::reverseTransaction($transaction, 'LoanSavingWithdrawCancel', 'Saving withdrawal cancellation', $request->cancel_date, $activeFy->id);
                app(LoanAccountingIntegrationService::class)->reverse('loan_saving_withdrawal', $ledger->id, $companyId, $activeFy->id, $request->cancel_date, LoanSavingLedger::EVENT_WITHDRAWN, 'LSW-' . $ledger->id, auth()->id());
            });
        } catch (\Exception $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Saving withdrawal cancelled.');
    }
}
