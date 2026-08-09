<?php

namespace App\Services\Accounting;

use App\Models\ChartAccount;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OfficialAccountingReportService
{
    public function generalLedger(int $companyId, int $financialYearId, int $accountId, string $from, string $to): array
    {
        $account = ChartAccount::withTrashed()->forCompany($companyId)->findOrFail($accountId);
        $lines = $this->lines($companyId, $financialYearId, $to)->where('chart_account_id', $accountId);
        $opening = $this->openingLines($lines, $from)->sum(fn ($line) => $this->signed($line));
        $running = $account->normal_balance === 'credit' ? -$opening : $opening;
        $period = $this->periodLines($lines, $from, $to);
        $rows = $period->map(function ($line) use (&$running, $account): array {
            $debit = $this->minor($line->debit);
            $credit = $this->minor($line->credit);
            $running += $account->normal_balance === 'credit' ? $credit - $debit : $debit - $credit;

            return [
                'entry_id' => $line->accounting_entry_id,
                'entry_date' => $this->lineDate($line),
                'entry_number' => $line->entry_number,
                'reference' => $line->reference_number,
                'source' => $line->source_module,
                'description' => $line->line_description ?: $line->entry_description,
                'debit' => $this->decimal($debit),
                'credit' => $this->decimal($credit),
                'running_balance' => $this->decimal($running),
            ];
        })->values();

        $periodDebit = $period->sum(fn ($line) => $this->minor($line->debit));
        $periodCredit = $period->sum(fn ($line) => $this->minor($line->credit));

        return [
            'account' => $account,
            'rows' => $rows,
            'opening_balance' => $this->decimal($account->normal_balance === 'credit' ? -$opening : $opening),
            'period_debit' => $this->decimal($periodDebit),
            'period_credit' => $this->decimal($periodCredit),
            'closing_balance' => $this->decimal($running),
        ];
    }

    public function trialBalance(int $companyId, int $financialYearId, string $from, string $to, bool $showZero = false): array
    {
        $lines = $this->lines($companyId, $financialYearId, $to);
        $accounts = $this->reportAccounts($companyId);
        $totals = array_fill_keys(['opening_debit', 'opening_credit', 'period_debit', 'period_credit', 'closing_debit', 'closing_credit'], 0);

        $rows = $accounts->map(function ($account) use ($lines, $from, $to, &$totals): array {
            $accountLines = $lines->where('chart_account_id', $account->id);
            $openingNet = $this->openingLines($accountLines, $from)->sum(fn ($line) => $this->signed($line));
            $period = $this->periodLines($accountLines, $from, $to);
            $periodDebit = $period->sum(fn ($line) => $this->minor($line->debit));
            $periodCredit = $period->sum(fn ($line) => $this->minor($line->credit));
            $closingNet = $openingNet + $periodDebit - $periodCredit;
            $values = [
                'opening_debit' => max($openingNet, 0), 'opening_credit' => max(-$openingNet, 0),
                'period_debit' => $periodDebit, 'period_credit' => $periodCredit,
                'closing_debit' => max($closingNet, 0), 'closing_credit' => max(-$closingNet, 0),
            ];
            foreach ($values as $key => $value) $totals[$key] += $value;

            return ['account' => $account, ...array_map(fn ($value) => $this->decimal($value), $values), '_zero' => array_sum(array_map('abs', $values)) === 0];
        })->when(! $showZero, fn (Collection $rows) => $rows->reject(fn ($row) => $row['_zero']))->values();

        $integrity = $totals['opening_debit'] === $totals['opening_credit']
            && $totals['period_debit'] === $totals['period_credit']
            && $totals['closing_debit'] === $totals['closing_credit'];

        return ['rows' => $rows, 'totals' => array_map(fn ($value) => $this->decimal($value), $totals), 'integrity' => $integrity];
    }

    public function profitAndLoss(int $companyId, int $financialYearId, string $from, string $to, bool $showZero = false): array
    {
        $lines = $this->periodLines($this->lines($companyId, $financialYearId, $to), $from, $to);
        $rows = $this->reportAccounts($companyId)->whereIn('account_class', ['income', 'expense'])->map(function ($account) use ($lines): array {
            $accountLines = $lines->where('chart_account_id', $account->id);
            $debit = $accountLines->sum(fn ($line) => $this->minor($line->debit));
            $credit = $accountLines->sum(fn ($line) => $this->minor($line->credit));
            $amount = $account->account_class === 'income' ? $credit - $debit : $debit - $credit;
            return ['account' => $account, 'amount' => $this->decimal($amount), '_minor' => $amount];
        })->when(! $showZero, fn (Collection $rows) => $rows->reject(fn ($row) => $row['_minor'] === 0))->values();

        $income = $rows->where(fn ($row) => $row['account']->account_class === 'income')->sum('_minor');
        $expense = $rows->where(fn ($row) => $row['account']->account_class === 'expense')->sum('_minor');
        $net = $income - $expense;

        return ['rows' => $rows, 'income' => $this->decimal($income), 'expense' => $this->decimal($expense), 'net' => $this->decimal(abs($net)), 'result' => $net >= 0 ? 'Net Profit' : 'Net Loss', '_net_minor' => $net];
    }

    public function balanceSheet(int $companyId, int $financialYearId, string $from, string $to, bool $showZero = false): array
    {
        $lines = $this->lines($companyId, $financialYearId, $to);
        $accounts = $this->reportAccounts($companyId);
        $rows = $accounts->whereIn('account_class', ['asset', 'liability', 'equity'])->map(function ($account) use ($lines): array {
            $accountLines = $lines->where('chart_account_id', $account->id);
            $debit = $accountLines->sum(fn ($line) => $this->minor($line->debit));
            $credit = $accountLines->sum(fn ($line) => $this->minor($line->credit));
            $amount = $account->account_class === 'asset' ? $debit - $credit : $credit - $debit;
            return ['account' => $account, 'amount' => $this->decimal($amount), '_minor' => $amount];
        })->when(! $showZero, fn (Collection $rows) => $rows->reject(fn ($row) => $row['_minor'] === 0))->values();

        $assets = $rows->where(fn ($row) => $row['account']->account_class === 'asset')->sum('_minor');
        $liabilities = $rows->where(fn ($row) => $row['account']->account_class === 'liability')->sum('_minor');
        $equity = $rows->where(fn ($row) => $row['account']->account_class === 'equity')->sum('_minor');
        $pnl = $this->profitAndLoss($companyId, $financialYearId, $from, $to, true)['_net_minor'];
        $equityWithResult = $equity + $pnl;

        return [
            'rows' => $rows, 'assets' => $this->decimal($assets), 'liabilities' => $this->decimal($liabilities),
            'equity' => $this->decimal($equity), 'current_result' => $this->decimal(abs($pnl)),
            'current_result_label' => $pnl >= 0 ? 'Current Profit' : 'Current Loss',
            'equity_with_result' => $this->decimal($equityWithResult), 'integrity' => $assets === $liabilities + $equityWithResult,
        ];
    }

    private function lines(int $companyId, int $financialYearId, string $to): Collection
    {
        return DB::table('accounting_entry_lines as l')->join('accounting_entries as e', 'e.id', '=', 'l.accounting_entry_id')
            ->join('chart_accounts as c', 'c.id', '=', 'l.chart_account_id')
            ->where('e.company_id', $companyId)->where('c.company_id', $companyId)
            ->where('e.financial_year_id', $financialYearId)->whereDate('e.entry_date', '<=', $to)
            ->whereIn('e.status', ['posted', 'reversed'])
            ->orderBy('e.entry_date')->orderBy('e.id')->orderBy('l.line_number')->orderBy('l.id')
            ->select('l.*', 'l.description as line_description', 'e.entry_date', 'e.entry_number', 'e.reference_number', 'e.source_module', 'e.source_type', 'e.source_event', 'e.description as entry_description', 'e.reversal_of_id')->get();
    }

    private function openingLines(Collection $lines, string $from): Collection
    {
        return $lines->filter(fn ($line) => $this->isOpening($line) || (! $this->isOpening($line) && $this->lineDate($line) < $from));
    }

    private function periodLines(Collection $lines, string $from, string $to): Collection
    {
        return $lines->filter(fn ($line) => ! $this->isOpening($line) && $this->lineDate($line) >= $from && $this->lineDate($line) <= $to);
    }

    private function isOpening(object $line): bool
    {
        return $line->source_module === 'opening_balance' && $line->source_type === 'opening_balance'
            && $line->source_event === 'posted' && $line->reversal_of_id === null;
    }

    private function lineDate(object $line): string { return substr((string) $line->entry_date, 0, 10); }

    private function reportAccounts(int $companyId): Collection
    {
        return ChartAccount::withTrashed()->forCompany($companyId)->orderBy('sort_order')->orderBy('code')->get();
    }

    private function signed(object $line): int { return $this->minor($line->debit) - $this->minor($line->credit); }
    private function minor(string|int|float|null $value): int
    {
        $decimal = trim((string) ($value ?? '0'));
        if (! preg_match('/^(-?)(\d+)(?:\.(\d{1,4}))?$/', $decimal, $matches)) {
            throw new \UnexpectedValueException('Accounting amount is not a valid four-decimal value.');
        }
        $minor = ((int) $matches[2] * 10000) + (int) str_pad($matches[3] ?? '', 4, '0');
        return ($matches[1] ?? '') === '-' ? -$minor : $minor;
    }

    private function decimal(int $value): string
    {
        $sign = $value < 0 ? '-' : '';
        $absolute = abs($value);
        return $sign . intdiv($absolute, 10000) . '.' . str_pad((string) ($absolute % 10000), 4, '0', STR_PAD_LEFT);
    }
}
