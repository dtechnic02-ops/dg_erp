<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\ChartAccount;
use App\Models\FinancialYear;
use App\Services\Accounting\OfficialAccountingReportService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AccountingReportController extends Controller
{
    public function __construct(private readonly OfficialAccountingReportService $reports) {}

    public function generalLedger(Request $request)
    {
        [$companyId, $financialYear, $from, $to, $showZero] = $this->scope($request);
        $accounts = ChartAccount::withTrashed()->forCompany($companyId)->orderBy('sort_order')->orderBy('code')->get();
        $accountId = (int) ($request->input('chart_account_id') ?: ($accounts->first()?->id ?? 0));
        if (! $accounts->contains('id', $accountId)) throw ValidationException::withMessages(['chart_account_id' => 'Selected Chart Account does not belong to this company.']);
        $report = $this->reports->generalLedger($companyId, $financialYear->id, $accountId, $from, $to);
        return view('company.accounting-reports.general-ledger', compact('financialYear', 'from', 'to', 'showZero', 'accounts', 'accountId', 'report') + $this->options($companyId));
    }

    public function trialBalance(Request $request)
    {
        [$companyId, $financialYear, $from, $to, $showZero] = $this->scope($request);
        $report = $this->reports->trialBalance($companyId, $financialYear->id, $from, $to, $showZero);
        return view('company.accounting-reports.trial-balance', compact('financialYear', 'from', 'to', 'showZero', 'report') + $this->options($companyId));
    }

    public function profitLoss(Request $request)
    {
        [$companyId, $financialYear, $from, $to, $showZero] = $this->scope($request);
        $report = $this->reports->profitAndLoss($companyId, $financialYear->id, $from, $to, $showZero);
        return view('company.accounting-reports.profit-loss', compact('financialYear', 'from', 'to', 'showZero', 'report') + $this->options($companyId));
    }

    public function balanceSheet(Request $request)
    {
        [$companyId, $financialYear, $from, $to, $showZero] = $this->scope($request);
        $report = $this->reports->balanceSheet($companyId, $financialYear->id, $financialYear->start_date, $to, $showZero);
        return view('company.accounting-reports.balance-sheet', compact('financialYear', 'from', 'to', 'showZero', 'report') + $this->options($companyId));
    }

    private function scope(Request $request): array
    {
        $companyId = (int) $request->user()->company_id;
        $request->validate([
            'financial_year_id' => ['nullable', 'integer'], 'from_date' => ['nullable', 'date_format:Y-m-d'],
            'to_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from_date'], 'show_zero' => ['nullable', 'boolean'],
        ]);
        $financialYear = $request->filled('financial_year_id')
            ? FinancialYear::where('company_id', $companyId)->find($request->integer('financial_year_id'))
            : FinancialYear::where('company_id', $companyId)->where('is_active', true)->first();
        if (! $financialYear) throw ValidationException::withMessages(['financial_year_id' => 'A valid company Financial Year is required.']);
        $from = $request->input('from_date', $financialYear->start_date);
        $to = $request->input('to_date', $financialYear->end_date);
        if ($from < $financialYear->start_date || $to > $financialYear->end_date) {
            throw ValidationException::withMessages(['from_date' => 'Report dates must remain inside the selected Financial Year.']);
        }
        return [$companyId, $financialYear, $from, $to, $request->boolean('show_zero')];
    }

    private function options(int $companyId): array
    {
        return ['financialYears' => FinancialYear::where('company_id', $companyId)->orderByDesc('start_date')->get()];
    }
}
