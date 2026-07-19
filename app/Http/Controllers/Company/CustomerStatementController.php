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
         * OPENING BALANCE + RUNNING BALANCE
         * -----------------------------------------
         */

        $statementBalances = $this->buildStatementBalances(
            $companyId,
            $request,
            $query,
            $startDate
        );

        $openingBalance = $statementBalances['opening'];
        $closingBalance = $statementBalances['closing'];

        /**
         * -----------------------------------------
         * SUMMARY
         * -----------------------------------------
         */

        $summaryQuery = clone $query;

        $totalRecords =
            $summaryQuery->count();

        $totalDebit = $statementBalances['totalDebit'];
        $totalCredit = $statementBalances['totalCredit'];

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

        foreach ($transactions as $transaction) {
            $transaction->balance = $statementBalances['balances'][$transaction->id]
                ?? $openingBalance;
        }

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

    protected function buildStatementBalances(
        int $companyId,
        Request $request,
        $query,
        ?string $startDate
    ): array {
        $summaryQuery = clone $query;

        $transactionTotalsQuery = (clone $summaryQuery)->where(function ($q) {
            $q->whereNull('reference_type')
                ->orWhere('reference_type', '!=', 'opening_balance');
        });

        $totalDebit = round((float) (clone $transactionTotalsQuery)->sum('debit'), 2);
        $totalCredit = round((float) (clone $transactionTotalsQuery)->sum('credit'), 2);

        $periodStarts = $this->getCustomerPeriodStartBalances(
            $companyId,
            $request,
            $startDate
        );

        $openingBalance = round(array_sum($periodStarts), 2);

        if ($request->filled('customer_id')) {
            $openingBalance = round(
                $periodStarts[(int) $request->customer_id] ?? 0,
                2
            );
        }

        $orderedTransactions = (clone $query)
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get(['id', 'customer_id', 'debit', 'credit']);

        $customerRunning = [];
        $balances = [];

        foreach ($orderedTransactions as $transaction) {
            $customerId = (int) $transaction->customer_id;

            if (!array_key_exists($customerId, $customerRunning)) {
                $customerRunning[$customerId] = $this->getCustomerPrePeriodLedgerNet(
                    $companyId,
                    $customerId,
                    $request,
                    $startDate
                );
            }

            $customerRunning[$customerId] = round(
                $customerRunning[$customerId]
                + (float) $transaction->debit
                - (float) $transaction->credit,
                2
            );

            $balances[$transaction->id] = $customerRunning[$customerId];
        }

        $closingBalance = round(
            $openingBalance + $totalDebit - $totalCredit,
            2
        );

        return [
            'opening'      => $openingBalance,
            'closing'      => $closingBalance,
            'totalDebit'   => $totalDebit,
            'totalCredit'  => $totalCredit,
            'balances'     => $balances,
        ];
    }

    protected function getCustomerPeriodStartBalances(
        int $companyId,
        Request $request,
        ?string $startDate
    ): array {
        $customersQuery = Customer::where('company_id', $companyId);

        if ($request->filled('customer_id')) {
            $customersQuery->where('id', $request->customer_id);
        }

        $customers = $customersQuery->get(['id', 'opening_balance']);
        $periodStarts = [];

        foreach ($customers as $customer) {
            $periodStarts[(int) $customer->id] = round(
                (float) $customer->opening_balance,
                2
            );
        }

        if (empty($startDate)) {
            return $periodStarts;
        }

        foreach ($customers as $customer) {
            $prePeriodQuery = CustomerTransaction::where('company_id', $companyId)
                ->where('customer_id', $customer->id)
                ->whereDate('transaction_date', '<', $startDate);

            $this->applyStatusFilter($prePeriodQuery, $request);

            $prePeriodNet = round(
                (float) $prePeriodQuery
                    ->selectRaw('COALESCE(SUM(debit), 0) - COALESCE(SUM(credit), 0) as net')
                    ->value('net'),
                2
            );

            $periodStarts[(int) $customer->id] = round(
                ($periodStarts[(int) $customer->id] ?? 0) + $prePeriodNet,
                2
            );
        }

        return $periodStarts;
    }

    protected function getCustomerPrePeriodLedgerNet(
        int $companyId,
        int $customerId,
        Request $request,
        ?string $startDate
    ): float {
        if (empty($startDate)) {
            return 0;
        }

        $prePeriodQuery = CustomerTransaction::where('company_id', $companyId)
            ->where('customer_id', $customerId)
            ->whereDate('transaction_date', '<', $startDate);

        $this->applyStatusFilter($prePeriodQuery, $request);

        return round(
            (float) $prePeriodQuery
                ->selectRaw('COALESCE(SUM(debit), 0) - COALESCE(SUM(credit), 0) as net')
                ->value('net'),
            2
        );
    }

    protected function applyStatusFilter($query, Request $request): void
    {
        if (!$request->filled('status')) {
            $query->where('status', 1);

            return;
        }

        if ($request->status != 'all') {
            $query->where('status', $request->status);
        }
    }
}      