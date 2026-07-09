<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FinancialYear;
use App\Models\Account;
use App\Models\AccountTransaction;

class AccountTransactionController extends Controller
{
    public function index(Request $request)
{
    $companyId = auth()->user()->company_id;

    /*
    |--------------------------------------------------------------------------
    | ACTIVE FINANCIAL YEAR
    |--------------------------------------------------------------------------
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

    $financialYears = FinancialYear::where(
        'company_id',
        $companyId
    )
    ->orderByDesc(
        'start_date'
    )
    ->get();

    /*
    |--------------------------------------------------------------------------
    | QUERY
    |--------------------------------------------------------------------------
    */

    $query = AccountTransaction::with(
        'account'
    )
    ->where(
        'company_id',
        $companyId
    );

    /*
    |--------------------------------------------------------------------------
    | DEFAULT FILTER
    |--------------------------------------------------------------------------
    */

    $financialYearId =
        $request->financial_year_id
        ?? $activeFy?->id;

    $startDate =
        $activeFy?->start_date;

    $endDate =
        $activeFy?->end_date;

    /*
    |--------------------------------------------------------------------------
    | DATE RANGE OVERRIDE
    |--------------------------------------------------------------------------
    */

    if (
        $request->filled('start_date') ||
        $request->filled('end_date')
    )
    {
        $financialYearId = 'all';

        $startDate =
            $request->start_date;

        $endDate =
            $request->end_date;
    }

    /*
    |--------------------------------------------------------------------------
    | FINANCIAL YEAR FILTER
    |--------------------------------------------------------------------------
    */

    elseif (
        $request->filled('financial_year_id') &&
        $request->financial_year_id != 'all'
    )
    {
        $query->where(
            'financial_year_id',
            $request->financial_year_id
        );
    }

    /*
    |--------------------------------------------------------------------------
    | SEARCH
    |--------------------------------------------------------------------------
    */

    if (
        $request->filled('search')
    )
    {
        $query->where(function ($q) use ($request) {

            $q->where(
                'voucher_no',
                'like',
                '%' . $request->search . '%'
            )

            ->orWhere(
                'reference_no',
                'like',
                '%' . $request->search . '%'
            )

            ->orWhere(
                'note',
                'like',
                '%' . $request->search . '%'
            );

        });
    }

    /*
    |--------------------------------------------------------------------------
    | ACCOUNT FILTER
    |--------------------------------------------------------------------------
    */

    if (
        $request->filled('account_id')
    )
    {
        $query->where(
            'account_id',
            $request->account_id
        );
    }

    /*
    |--------------------------------------------------------------------------
    | STATUS FILTER
    |--------------------------------------------------------------------------
    */

    if (
        $request->filled('status')
    )
    {
        $query->where(
            'status',
            $request->status
        );
    }

    /*
    |--------------------------------------------------------------------------
    | DATE FILTER
    |--------------------------------------------------------------------------
    */

    if ($startDate)
    {
        $query->whereDate(
            'transaction_date',
            '>=',
            $startDate
        );
    }

    if ($endDate)
    {
        $query->whereDate(
            'transaction_date',
            '<=',
            $endDate
        );
    }

    /*
    |--------------------------------------------------------------------------
    | DATA
    |--------------------------------------------------------------------------
    */

    $transactions = $query
        ->latest()
        ->paginate(20)
        ->withQueryString();

    /*
    |--------------------------------------------------------------------------
    | SUMMARY
    |--------------------------------------------------------------------------
    */

    $summary = [
'total_debit' => $query->clone()
    ->sum('debit'),

        'total_credit' => $query->clone()
    ->sum('credit'),

       'total_transactions' =>
$query->clone()->count(),

    ];

    /*
    |--------------------------------------------------------------------------
    | ACCOUNTS
    |--------------------------------------------------------------------------
    */

    $accounts = Account::where(
        'company_id',
        $companyId
    )
    ->where(
        'status',
        1
    )
    ->orderBy(
        'account_name'
    )
    ->get();

    return view(
        'company.account-transaction.index',
        compact(
            'transactions',
            'accounts',
            'summary',
            'financialYears',
            'financialYearId',
            'startDate',
            'endDate'
        )
    );
}
   public function show($id)
{
    $transaction =

    AccountTransaction::where(

        'company_id',

        auth()->user()->company_id

    )

    ->findOrFail(

        $id

    );

    return view(

        'company.account-transaction.show',

        compact(

            'transaction'

        )

    );
}
}
