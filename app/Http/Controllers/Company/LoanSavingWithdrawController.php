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
            'loan_account_id' => 'required',
            'financial_year_id' => 'required|exists:financial_years,id',
            'account_id' => 'required',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
        ]);

        try {
            DB::transaction(function () use ($request) {
                $companyId = auth()->user()->company_id;
                $activeFy = $this->assertActiveFinancialYear($companyId);

                if ((int) $request->financial_year_id !== (int) $activeFy->id) {
                    throw new \Exception('Financial year must be the active financial year.');
                }

                $this->assertDateWithinFinancialYear(
                    $request->date,
                    $activeFy,
                    'Withdraw date must be inside the active financial year.'
                );

                $account = Account::where('company_id', $companyId)
                    ->findOrFail($request->account_id);

                $currentSaving = LoanSavingLedger::where('company_id', $companyId)
                    ->where('loan_account_id', $request->loan_account_id)
                    ->active()
                    ->latest('id')
                    ->value('balance_after') ?? 0;

                if ((float) $request->amount > (float) $currentSaving) {
                    throw new \Exception('Insufficient saving balance.');
                }

                $newBalance = (float) $currentSaving - (float) $request->amount;

                if ((float) $account->current_balance < (float) $request->amount) {
                    throw new \Exception('Insufficient account balance.');
                }

                $account->decrement('current_balance', (float) $request->amount);

                LoanSavingLedger::create([
                    'company_id' => $companyId,
                    'financial_year_id' => $activeFy->id,
                    'loan_account_id' => $request->loan_account_id,
                    'account_id' => $request->account_id,
                    'type' => 'withdraw',
                    'amount' => $request->amount,
                    'balance_after' => $newBalance,
                    'date' => $request->date,
                    'note' => $request->note,
                    'created_by' => auth()->id(),
                    'status' => LoanSavingLedger::STATUS_ACTIVE,
                ]);
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
}
