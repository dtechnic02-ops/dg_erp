<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesCompanyPermission;
use App\Http\Controllers\Concerns\AuthorizesSubscriptionModule;
use Illuminate\Routing\Controllers\HasMiddleware;
use App\Models\Account;
use App\Models\LoanAccount;
use App\Models\LoanSavingLedger;
use App\Models\PartyAccount;
use Illuminate\Http\Request;
use App\Services\Money;

class LoanSavingLedgerController extends Controller implements HasMiddleware
{
    use AuthorizesCompanyPermission;
    use AuthorizesSubscriptionModule;

    public static function middleware(): array
    {
        return self::subscriptionModuleMiddleware();
    }

    protected static function subscriptionModuleCode(): string
    {
        return 'loan';
    }

    private function filteredLedgerQuery(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $query = LoanSavingLedger::with([
            'loanAccount.partyAccount:id,company_id,name',
            'account:id,company_id,account_name',
            'createdBy:id,name',
        ])->where('company_id', $companyId);

        if ($request->status === 'cancelled') {
            $query->where('status', LoanSavingLedger::STATUS_INACTIVE);
        } elseif ($request->status !== 'all') {
            $query->active();
        }

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->whereHas('loanAccount', function ($loanQuery) use ($search) {
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

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        return $query->latest('date')->latest('id');
    }

    public function index(Request $request)
    {
        $this->authorizeCompanyPermission('view_loan_saving_ledger');

        $companyId = auth()->user()->company_id;

        $filteredQuery = $this->filteredLedgerQuery($request);
        $summaryQuery = (clone $filteredQuery)->active();

        $totalEntries = (clone $summaryQuery)->count();
        $totalDeposit = (clone $summaryQuery)->where('type', LoanSavingLedger::TYPE_DEPOSIT)->sum('amount');
        $totalWithdraw = (clone $summaryQuery)->whereIn('type', [LoanSavingLedger::TYPE_WITHDRAW, LoanSavingLedger::TYPE_LOAN_SETTLEMENT])->sum('amount');
        $latestBalances = LoanSavingLedger::where('company_id', $companyId)
            ->active()->orderBy('id')->get(['loan_account_id', 'balance_after'])
            ->groupBy('loan_account_id')->map(fn ($entries) => $entries->last()->balance_after);
        $netBalance = '0.00';
        foreach ($latestBalances as $balance) {
            $netBalance = Money::add($netBalance, $balance);
        }

        $allowedPerPage = [10, 20, 50, 100, 200];
        $perPage = (int) $request->get('per_page', 10);

        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $ledgers = $filteredQuery
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
            'company.loan-saving-ledger.index',
            compact(
                'ledgers',
                'perPage',
                'loanAccounts',
                'partyAccounts',
                'accounts',
                'totalEntries',
                'totalDeposit',
                'totalWithdraw',
                'netBalance'
            )
        );
    }

    public function show($id)
    {
        $this->authorizeCompanyPermission('view_loan_saving_ledger');

        $ledger = LoanSavingLedger::with([
            'loanAccount.partyAccount',
            'account',
            'loanPayment',
            'financialYear',
            'createdBy',
        ])
            ->where('company_id', auth()->user()->company_id)
            ->findOrFail($id);

        $currentSavingBalance = LoanSavingLedger::where('company_id', auth()->user()->company_id)
            ->where('loan_account_id', $ledger->loan_account_id)
            ->active()
            ->latest('id')
            ->value('balance_after') ?? 0;

        return view(
            'company.loan-saving-ledger.show',
            compact('ledger', 'currentSavingBalance')
        );
    }
}
