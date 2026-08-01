<?php

namespace App\Services;

use App\Models\AccountingPeriodLock;
use App\Models\Account;
use App\Models\ChartAccount;
use App\Models\FinancialYear;
use App\Models\Journal;
use App\Models\JournalAuditEvent;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class JournalService
{
    private const TRANSITIONS = [
        Journal::STATUS_DRAFT => [Journal::STATUS_SUBMITTED, Journal::STATUS_CANCELLED],
        Journal::STATUS_SUBMITTED => [Journal::STATUS_APPROVED, Journal::STATUS_REJECTED],
        Journal::STATUS_REJECTED => [Journal::STATUS_DRAFT],
        Journal::STATUS_APPROVED => [Journal::STATUS_POSTED, Journal::STATUS_CANCELLED],
        Journal::STATUS_POSTED => [Journal::STATUS_REVERSED],
        Journal::STATUS_CANCELLED => [],
        Journal::STATUS_REVERSED => [],
    ];

    public function createDraft(array $data, int $companyId, int $actorId): Journal
    {
        return DB::transaction(function () use ($data, $companyId, $actorId) {
            $fy = $this->validateFinancialContext($companyId, (int) $data['financial_year_id'], $data['journal_date']);
            $lines = $this->validateLines($data['lines'], $companyId);
            if (Journal::where('company_id', $companyId)->where('request_key', $data['request_key'])->exists()) {
                throw ValidationException::withMessages(['request_key' => 'This Journal request has already been submitted.']);
            }
            $journal = Journal::create([
                'company_id' => $companyId, 'financial_year_id' => $fy->id,
                'journal_no' => $this->nextNumber($companyId, $fy), 'journal_date' => $data['journal_date'],
                'journal_type' => $data['journal_type'], 'reference_no' => $data['reference_no'] ?? null,
                'description' => $data['description'], 'remarks' => $data['remarks'] ?? null,
                'note' => $data['description'], 'request_key' => $data['request_key'],
                'total_amount' => $this->totalDebit($lines), 'created_by' => $actorId,
                'status' => Journal::STATUS_DRAFT,
            ]);
            $this->replaceLines($journal, $lines);
            $this->audit($journal, 'created', null, Journal::STATUS_DRAFT, $actorId);
            return $journal->load(['items.chartAccount', 'auditEvents']);
        });
    }

    public function updateDraft(Journal $journal, array $data, int $actorId): Journal
    {
        return DB::transaction(function () use ($journal, $data, $actorId) {
            $journal = Journal::whereKey($journal->id)->where('company_id', $journal->company_id)->lockForUpdate()->firstOrFail();
            if (!$journal->isDraft() || $journal->is_locked) throw new RuntimeException('Only an unlocked Draft Journal may be edited.');
            $fy = $this->validateFinancialContext($journal->company_id, (int) $data['financial_year_id'], $data['journal_date']);
            if ((int) $fy->id !== (int) $journal->financial_year_id) throw new RuntimeException('A Journal Financial Year cannot be changed after numbering.');
            $lines = $this->validateLines($data['lines'], $journal->company_id);
            $journal->update([
                'journal_date' => $data['journal_date'], 'journal_type' => $data['journal_type'],
                'reference_no' => $data['reference_no'] ?? null, 'description' => $data['description'],
                'remarks' => $data['remarks'] ?? null, 'note' => $data['description'],
                'total_amount' => $this->totalDebit($lines), 'updated_by' => $actorId,
            ]);
            $journal->items()->delete();
            $this->replaceLines($journal, $lines);
            $this->audit($journal, 'updated_draft', Journal::STATUS_DRAFT, Journal::STATUS_DRAFT, $actorId);
            return $journal->load(['items.chartAccount', 'auditEvents']);
        });
    }

    public function assertTransition(string $from, string $to): void
    {
        if (!in_array($to, self::TRANSITIONS[$from] ?? [], true)) throw new RuntimeException("Invalid Journal status transition from {$from} to {$to}.");
    }

    private function validateFinancialContext(int $companyId, int $financialYearId, string $date): FinancialYear
    {
        $fy = FinancialYear::where('company_id', $companyId)->whereKey($financialYearId)->where('is_active', 1)->first();
        if (!$fy || (Schema::hasColumn('financial_years', 'is_closed') && $fy->is_closed) || (Schema::hasColumn('financial_years', 'is_locked') && $fy->is_locked)) {
            throw ValidationException::withMessages(['financial_year_id' => 'Select an active, open, unlocked company Financial Year.']);
        }
        $businessDate = CarbonImmutable::createFromFormat('Y-m-d', $date)->format('Y-m-d');
        if ($businessDate < $fy->start_date || $businessDate > $fy->end_date) throw ValidationException::withMessages(['journal_date' => 'Business Date must be inside the selected Financial Year.']);
        if (Schema::hasTable('accounting_period_locks') && AccountingPeriodLock::where('company_id', $companyId)->where('financial_year_id', $fy->id)->where('is_locked', 1)->whereDate('date_from', '<=', $businessDate)->whereDate('date_to', '>=', $businessDate)->exists()) {
            throw ValidationException::withMessages(['journal_date' => 'Business Date belongs to a locked accounting period.']);
        }
        return $fy;
    }

    private function validateLines(array $lines, int $companyId): array
    {
        if (count($lines) < 2) throw ValidationException::withMessages(['lines' => 'At least two Journal lines are required.']);
        $debit = 0; $credit = 0;
        foreach ($lines as $index => &$line) {
            $account = ChartAccount::where('company_id', $companyId)->whereKey($line['chart_account_id'])->where('level', 3)->where('allow_manual_entry', 1)->where('status', 'active')->when(Schema::hasColumn('chart_accounts', 'is_locked'), fn ($q) => $q->where('is_locked', 0))->first();
            if (!$account) throw ValidationException::withMessages(["lines.{$index}.chart_account_id" => 'Select an active, unlocked Level 3 posting Chart Account from this company.']);
            if (!empty($line['account_id']) && !Account::where('company_id', $companyId)->whereKey($line['account_id'])->where('status', 1)->exists()) throw ValidationException::withMessages(["lines.{$index}.account_id" => 'The operational Account is invalid for this company.']);
            $d = $this->scaled($line['debit']); $c = $this->scaled($line['credit']);
            if (($d > 0 && $c > 0) || ($d === 0 && $c === 0)) throw ValidationException::withMessages(["lines.{$index}" => 'Each line requires either Debit or Credit, never both.']);
            $debit += $d; $credit += $c; $line['_debit'] = $d; $line['_credit'] = $c;
        }
        unset($line);
        if ($debit !== $credit) throw ValidationException::withMessages(['lines' => 'Total Debit must equal Total Credit to four decimal places.']);
        return $lines;
    }

    private function replaceLines(Journal $journal, array $lines): void
    {
        foreach ($lines as $i => $line) {
            $debit = $this->decimal($line['_debit']); $credit = $this->decimal($line['_credit']);
            $journal->items()->create([
                'company_id' => $journal->company_id, 'chart_account_id' => $line['chart_account_id'],
                'account_id' => $line['account_id'] ?? null, 'debit' => $debit, 'credit' => $credit,
                'type' => $line['_debit'] > 0 ? 'debit' : 'credit', 'amount' => $line['_debit'] > 0 ? $debit : $credit,
                'description' => $line['description'] ?? null, 'reference' => $line['reference'] ?? null,
                'note' => $line['description'] ?? null, 'line_number' => $i + 1,
                'sub_ledger_type' => $line['subledger_type'] ?? null, 'sub_ledger_id' => $line['subledger_id'] ?? null, 'status' => 1,
            ]);
        }
    }

    private function nextNumber(int $companyId, FinancialYear $fy): string
    {
        FinancialYear::whereKey($fy->id)->lockForUpdate()->firstOrFail();
        $sequence = DB::table('journal_number_sequences')->where('company_id', $companyId)->where('financial_year_id', $fy->id)->lockForUpdate()->first();
        if (!$sequence) {
            DB::table('journal_number_sequences')->insert(['company_id' => $companyId, 'financial_year_id' => $fy->id, 'next_number' => 2, 'created_at' => now(), 'updated_at' => now()]);
            $number = 1;
        } else {
            $number = (int) $sequence->next_number;
            DB::table('journal_number_sequences')->where('company_id', $companyId)->where('financial_year_id', $fy->id)->update(['next_number' => $number + 1, 'updated_at' => now()]);
        }
        return 'JRN-' . $companyId . '-' . $fy->id . '-' . str_pad((string) $number, 8, '0', STR_PAD_LEFT);
    }

    private function audit(Journal $journal, string $event, ?string $previous, ?string $new, int $actorId): void
    {
        JournalAuditEvent::create(['company_id' => $journal->company_id, 'financial_year_id' => $journal->financial_year_id, 'journal_id' => $journal->id, 'event' => $event, 'previous_status' => $previous, 'new_status' => $new, 'actor_id' => $actorId, 'event_at' => now()]);
    }

    private function scaled(mixed $value): int
    {
        $text = trim((string) $value);
        if (!preg_match('/^\d{1,16}(?:\.(\d{1,4}))?$/', $text, $m)) throw ValidationException::withMessages(['lines' => 'Amounts must be non-negative with no more than four decimal places.']);
        [$whole, $fraction] = array_pad(explode('.', $text, 2), 2, '');
        return ((int) $whole * 10000) + (int) str_pad($fraction, 4, '0');
    }

    private function decimal(int $scaled): string { return intdiv($scaled, 10000) . '.' . str_pad((string) ($scaled % 10000), 4, '0', STR_PAD_LEFT); }
    private function totalDebit(array $lines): string { return $this->decimal(array_sum(array_column($lines, '_debit'))); }
}
