<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Customer;
use App\Models\CustomerTransaction;
use App\Models\FinancialYear;

class CustomerStatementController extends Controller
{
    /**
     * 🔥 CUSTOMER LIST
     */
    public function index(Request $request)
    {
        $companyId =
            auth()->user()->company_id;

        /**
         * -----------------------------------------
         * ACTIVE FINANCIAL YEAR
         * -----------------------------------------
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

        if (!$activeFy)
        {
            return back()->with(
                'error',
                'Active Financial Year not found.'
            );
        }

        /**
         * -----------------------------------------
         * QUERY
         * -----------------------------------------
         */

        $query = CustomerTransaction::with(
            'customer'
        )
        ->where(
            'company_id',
            $companyId
        );

        /**
         * -----------------------------------------
         * STATUS FILTER
         * -----------------------------------------
         */

        if (!$request->filled('status'))
        {
            $query->where(
                'status',
                1
            );
        }
        elseif ($request->status != 'all')
        {
            $query->where(
                'status',
                $request->status
            );
        }

        /**
         * -----------------------------------------
         * FINANCIAL YEAR FILTER
         * -----------------------------------------
         */

        $startDate = null;

        $endDate = null;

        if (!$request->has('financial_year_id'))
        {
            $query->where(
                'financial_year_id',
                $activeFy->id
            );

            $startDate =
                $activeFy->start_date;

            $endDate =
                $activeFy->end_date;
        }
        else
        {
            if (
                $request->filled(
                    'financial_year_id'
                )
                &&
                $request->financial_year_id
                != 'all'
            )
            {
                $query->where(
                    'financial_year_id',
                    $request->financial_year_id
                );

                $selectedFy = FinancialYear::find(
                    $request->financial_year_id
                );

                if ($selectedFy)
                {
                    $startDate =
                        $selectedFy->start_date;

                    $endDate =
                        $selectedFy->end_date;
                }
            }
            else
            {
                $startDate =
                    $request->start_date;

                $endDate =
                    $request->end_date;
            }
        }

        /**
         * -----------------------------------------
         * CUSTOMER FILTER
         * -----------------------------------------
         */

        if ($request->filled('customer_id'))
        {
            $query->where(
                'customer_id',
                $request->customer_id
            );
        }

        /**
         * -----------------------------------------
         * SEARCH
         * -----------------------------------------
         */

        if ($request->filled('search'))
        {
            $search = trim(
                $request->search
            );

            $query->where(function ($q)
                use ($search)
            {

                $q->where(
                    'voucher_no',
                    'like',
                    "%{$search}%"
                )

                ->orWhere(
                    'reference_no',
                    'like',
                    "%{$search}%"
                )

                ->orWhere(
                    'description',
                    'like',
                    "%{$search}%"
                );

            });
        }

        /**
         * -----------------------------------------
         * DATE FILTER
         * -----------------------------------------
         */

        if (!empty($startDate))
        {
            $query->whereDate(
                'transaction_date',
                '>=',
                $startDate
            );
        }

        if (!empty($endDate))
        {
            $query->whereDate(
                'transaction_date',
                '<=',
                $endDate
            );
        }

        /**
         * -----------------------------------------
         * OPENING BALANCE
         * -----------------------------------------
         */

        $openingBalance = 0;

        if (!empty($startDate))
        {
            $openingTransaction = CustomerTransaction::where(
                'company_id',
                $companyId
            )
            ->where(
                'status',
                1
            )

            ->when(
                $request->filled('customer_id'),
                function ($q) use ($request) {

                    $q->where(
                        'customer_id',
                        $request->customer_id
                    );

                }
            )

            ->whereDate(
                'transaction_date',
                '<',
                $startDate
            )

            ->orderByDesc('transaction_date')

            ->orderByDesc('id')

            ->first();

            if ($openingTransaction)
            {
                $openingBalance =
                    $openingTransaction->balance;
            }
        }

        /**
         * -----------------------------------------
         * SUMMARY
         * -----------------------------------------
         */

        $summaryQuery = clone $query;

        $totalRecords =
            $summaryQuery->count();

        $totalDebit =
            (clone $summaryQuery)
                ->sum('debit');

        $totalCredit =
            (clone $summaryQuery)
                ->sum('credit');

        /**
         * -----------------------------------------
         * RESULT
         * -----------------------------------------
         */

        $transactions = $query

            ->orderBy(
                'transaction_date'
            )

            ->orderBy(
                'id'
            )

            ->paginate(20)

            ->withQueryString();

        /**
         * -----------------------------------------
         * FILTER DATA
         * -----------------------------------------
         */

        $customers = Customer::where(
            'company_id',
            $companyId
        )
        ->orderBy('name')
        ->get();

        $financialYears = FinancialYear::where(
            'company_id',
            $companyId
        )
        ->orderByDesc('start_date')
        ->get();

        $closingBalance =
            optional(
                $transactions->last()
            )->balance ?? $openingBalance;

        /**
         * -----------------------------------------
         * VIEW
         * -----------------------------------------
         */

        return view(

            'company.customer-statement.index',

            compact(

                'transactions',

                'customers',

                'financialYears',

                'activeFy',

                'startDate',

                'endDate',

                'totalRecords',

                'totalDebit',

                'totalCredit',

                'openingBalance',

                'closingBalance'

            )
        );
    }
}      