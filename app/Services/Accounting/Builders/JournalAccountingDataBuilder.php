<?php

namespace App\Services\Accounting\Builders;

use App\Models\Account;
use App\Models\AccountingPeriodLock;
use App\Models\ChartAccount;
use App\Models\Company;
use App\Models\Customer;
use App\Models\FinancialYear;
use App\Models\Journal;
use App\Models\Supplier;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class JournalAccountingDataBuilder
{
    public function build(Journal $journal, int $companyId, int $actorId): array
    {
        if (! $journal->exists || (int) $journal->company_id !== $companyId) {
            throw new RuntimeException('The Journal does not belong to the supplied company.');
        }

        if ($journal->status !== Journal::STATUS_APPROVED || $journal->is_locked) {
            throw new RuntimeException('Only an unlocked Approved Journal may be posted.');
        }

        $financialYear = $this->validateFinancialContext($journal, $companyId);
        $items = $journal->items()->orderBy('line_number')->orderBy('id')->get();

        if ($items->count() < 2) {
            throw new RuntimeException('An accounting entry requires at least two Journal lines.');
        }

        return [
            'company_id' => $companyId,
            'financial_year_id' => (int) $financialYear->id,
            'journal_id' => (int) $journal->id,
            'journal_number' => $journal->journal_no,
            'reference_number' => $journal->reference_no ?: $journal->journal_no,
            'entry_date' => $journal->journal_date->format('Y-m-d'),
            'description' => $journal->description,
            'posted_by' => $actorId,
            'source_module' => 'journal',
            'source_type' => 'manual_journal',
            'source_id' => (int) $journal->id,
            'source_event' => 'posted',
            'source_key' => 'manual-journal:' . $journal->id . ':posted',
            'lines' => $items->map(function ($item): array {
                $this->validateLine($item, (int) $item->company_id);

                return [
                    'chart_account_id' => (int) $item->chart_account_id,
                    'account_id' => $item->account_id === null ? null : (int) $item->account_id,
                    'debit' => (string) $item->debit,
                    'credit' => (string) $item->credit,
                    'description' => $item->description,
                    'reference' => $item->reference,
                    'subledger_type' => $item->sub_ledger_type,
                    'subledger_id' => $item->sub_ledger_id === null ? null : (int) $item->sub_ledger_id,
                ];
            })->all(),
        ];
    }

    private function validateFinancialContext(Journal $journal, int $companyId): FinancialYear
    {
        $company = Company::whereKey($companyId)
            ->when(Schema::hasColumn('companies', 'status'), fn ($query) => $query->where('status', 'active'))
            ->first();

        if (! $company) {
            throw ValidationException::withMessages(['company_id' => 'The company must be active for Journal processing.']);
        }

        $financialYear = FinancialYear::where('company_id', $companyId)
            ->whereKey($journal->financial_year_id)
            ->where('is_active', 1)
            ->first();

        if (! $financialYear || (Schema::hasColumn('financial_years', 'is_closed') && $financialYear->is_closed) || (Schema::hasColumn('financial_years', 'is_locked') && $financialYear->is_locked)) {
            throw ValidationException::withMessages(['financial_year_id' => 'Select an active, open, unlocked company Financial Year.']);
        }

        $entryDate = CarbonImmutable::parse($journal->journal_date)->format('Y-m-d');

        if ($entryDate < $financialYear->start_date || $entryDate > $financialYear->end_date) {
            throw ValidationException::withMessages(['journal_date' => 'Business Date must be inside the selected Financial Year.']);
        }

        if (Schema::hasTable('accounting_period_locks') && AccountingPeriodLock::where('company_id', $companyId)
            ->where('financial_year_id', $financialYear->id)
            ->where('is_locked', 1)
            ->whereDate('date_from', '<=', $entryDate)
            ->whereDate('date_to', '>=', $entryDate)
            ->exists()) {
            throw ValidationException::withMessages(['journal_date' => 'Business Date belongs to a locked accounting period.']);
        }

        return $financialYear;
    }

    private function validateLine(object $item, int $companyId): void
    {
        if ((int) $item->company_id !== $companyId) {
            throw new RuntimeException('A Journal line does not belong to the Journal company.');
        }

        $chartAccount = ChartAccount::where('company_id', $companyId)->find($item->chart_account_id);
        if (! $chartAccount) {
            throw new RuntimeException('A Journal line Chart Account is invalid for this company.');
        }

        $hasType = $item->sub_ledger_type !== null;
        $hasId = $item->sub_ledger_id !== null;
        if ($hasType !== $hasId || ($hasType && ! in_array($item->sub_ledger_type, ['customer', 'supplier'], true))) {
            throw new RuntimeException('Journal subledger type and ID must be present together and must be supported.');
        }

        if ($hasType) {
            $requiredCode = $item->sub_ledger_type === 'customer' ? 'ACCOUNTS_RECEIVABLE' : 'ACCOUNTS_PAYABLE';
            if ($chartAccount->system_code !== $requiredCode) {
                throw new RuntimeException('Journal subledger control Chart Account is invalid.');
            }

            $model = $item->sub_ledger_type === 'customer' ? Customer::class : Supplier::class;
            if (! $model::where('company_id', $companyId)->where('status', 'active')->whereKey($item->sub_ledger_id)->exists()) {
                throw new RuntimeException('Journal subledger identity is invalid for this company.');
            }
        }

        if ($item->account_id === null) {
            return;
        }

        $account = Account::where('company_id', $companyId)->find($item->account_id);
        if (! $account || ! in_array($account->status, [1, 'active'], true)) {
            throw new RuntimeException('The operational Account is invalid for this company.');
        }

        $requiredCode = match ($account->account_type) {
            'Cash' => 'CASH_IN_HAND',
            'Bank', 'ATM', 'Wallet' => 'BANK_ACCOUNTS',
            default => throw new RuntimeException('The Journal operational Account type is not supported for accounting posting.'),
        };

        if ($chartAccount->system_code !== $requiredCode) {
            throw new RuntimeException('The Journal operational Account does not match the selected Chart Account.');
        }
    }
}
