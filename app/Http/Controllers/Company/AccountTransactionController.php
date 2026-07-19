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
        ->latest('id')
        ->get();

        $query = AccountTransaction::with(
            'account'
        )
        ->where(
            'company_id',
            $companyId
        );

        if ($request->search)
        {
            $search = $request->search;

            $query->where(function ($q) use ($search) {

                $q->where(
                    'voucher_no',
                    'like',
                    '%' . $search . '%'
                )

                ->orWhere(
                    'reference_no',
                    'like',
                    '%' . $search . '%'
                )

                ->orWhere(
                    'note',
                    'like',
                    '%' . $search . '%'
                );

            });
        }

        if ($request->account_id)
        {
            $query->where(
                'account_id',
                $request->account_id
            );
        }

        if (!$request->has('status'))
        {
            $query->where(
                'status',
                1
            );
        }
        elseif ($request->filled('status'))
        {
            $query->where(
                'status',
                $request->status
            );
        }

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
            else
            {
                $startDate = null;
                $endDate   = null;
            }
        }
        else
        {
            if ($request->financial_year_id)
            {
                $query->where(
                    'financial_year_id',
                    $request->financial_year_id
                );
            }

            $startDate = $request->start_date;
            $endDate   = $request->end_date;
        }

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

        $perPage = in_array((int) $request->per_page, [10, 25, 50, 100], true)
            ? (int) $request->per_page
            : 10;

        $transactions = $query
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $summary = [
            'total_debit' => (clone $query)->sum('debit'),
            'total_credit' => (clone $query)->sum('credit'),
            'total_transactions' => (clone $query)->count(),
        ];

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
                'activeFy',
                'startDate',
                'endDate',
                'perPage'
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
