<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Supplier;
use App\Models\SupplierTransaction;
use App\Models\FinancialYear;

class SupplierStatementController extends Controller
{
    public function index(Request $request)
    {
        $companyId =
            auth()->user()->company_id;

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

        $query = SupplierTransaction::with(
            'supplier'
        )
        ->where(
            'company_id',
            $companyId
        );

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

        if ($request->filled('supplier_id'))
        {
            $query->where(
                'supplier_id',
                $request->supplier_id
            );
        }

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

        $statementBalances = $this->buildStatementBalances(
            $companyId,
            $request,
            $query,
            $startDate
        );

        $openingBalance = $statementBalances['opening'];
        $closingBalance = $statementBalances['closing'];

        $summaryQuery = clone $query;

        $totalRecords =
            $summaryQuery->count();

        $totalDebit = $statementBalances['totalDebit'];
        $totalCredit = $statementBalances['totalCredit'];

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

        $suppliers = Supplier::where(
            'company_id',
            $companyId
        )
        ->active()
        ->orderBy('name')
        ->get();

        $financialYears = FinancialYear::where(
            'company_id',
            $companyId
        )
        ->orderByDesc('start_date')
        ->get();

        return view(

            'company.supplier-statement.index',

            compact(

                'transactions',

                'suppliers',

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

        $totalDebit = round((float) (clone $summaryQuery)->sum('debit'), 2);
        $totalCredit = round((float) (clone $summaryQuery)->sum('credit'), 2);

        $periodStarts = $this->getSupplierPeriodStartBalances(
            $companyId,
            $request,
            $startDate
        );

        $openingBalance = round(array_sum($periodStarts), 2);

        if ($request->filled('supplier_id')) {
            $openingBalance = round(
                $periodStarts[(int) $request->supplier_id] ?? 0,
                2
            );
        }

        $orderedTransactions = (clone $query)
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get(['id', 'supplier_id', 'debit', 'credit']);

        $supplierRunning = [];
        $balances = [];
        $lastRunningBalance = $openingBalance;

        foreach ($orderedTransactions as $transaction) {
            $supplierId = (int) $transaction->supplier_id;

            if (!array_key_exists($supplierId, $supplierRunning)) {
                $supplierRunning[$supplierId] = $periodStarts[$supplierId]
                    ?? $this->getSupplierPrePeriodLedgerNet(
                        $companyId,
                        $supplierId,
                        $request,
                        $startDate
                    );
            }

            $supplierRunning[$supplierId] = round(
                $supplierRunning[$supplierId]
                + (float) $transaction->debit
                - (float) $transaction->credit,
                2
            );

            $balances[$transaction->id] = $supplierRunning[$supplierId];
            $lastRunningBalance = $supplierRunning[$supplierId];
        }

        if ($request->filled('supplier_id')) {
            $closingBalance = $orderedTransactions->isEmpty()
                ? $openingBalance
                : round($lastRunningBalance, 2);
        } else {
            $closingBalance = round(
                $openingBalance + $totalDebit - $totalCredit,
                2
            );
        }

        return [
            'opening'      => $openingBalance,
            'closing'      => $closingBalance,
            'totalDebit'   => $totalDebit,
            'totalCredit'  => $totalCredit,
            'balances'     => $balances,
        ];
    }

    protected function getSupplierPeriodStartBalances(
        int $companyId,
        Request $request,
        ?string $startDate
    ): array {
        $suppliersQuery = Supplier::where('company_id', $companyId);

        if ($request->filled('supplier_id')) {
            $suppliersQuery->where('id', $request->supplier_id);
        }

        $periodStarts = [];

        foreach ($suppliersQuery->pluck('id') as $supplierId) {
            $supplierId = (int) $supplierId;

            if (empty($startDate)) {
                $periodStarts[$supplierId] = 0.0;

                continue;
            }

            $periodStarts[$supplierId] = $this->getSupplierPrePeriodLedgerNet(
                $companyId,
                $supplierId,
                $request,
                $startDate
            );
        }

        return $periodStarts;
    }

    protected function getSupplierPrePeriodLedgerNet(
        int $companyId,
        int $supplierId,
        Request $request,
        ?string $startDate
    ): float {
        if (empty($startDate)) {
            return 0;
        }

        $prePeriodQuery = SupplierTransaction::where('company_id', $companyId)
            ->where('supplier_id', $supplierId)
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
