<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\AccountingEntry;
use App\Models\ChartAccount;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerTransaction;
use App\Models\FinancialYear;
use App\Models\Journal;
use App\Models\JournalItem;
use App\Models\OpeningBalance;
use App\Models\OpeningBalanceAuditEvent;
use App\Models\OpeningBalanceLine;
use App\Models\Supplier;
use App\Models\SupplierTransaction;
use App\Services\Accounting\AccountingPostingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class OpeningBalanceService
{
    public function __construct(private readonly AccountingPostingService $accounting) {}

    public function create(int $companyId, int $userId, array $data): OpeningBalance
    {
        return DB::transaction(function () use ($companyId, $userId, $data) {
            $this->assertActor($companyId, $userId);
            if (OpeningBalance::where('company_id', $companyId)->where('request_key', $data['request_key'])->lockForUpdate()->exists()) {
                throw ValidationException::withMessages(['request_key' => 'This Opening Balance request has already been submitted.']);
            }
            $this->assertContext($companyId, (int) $data['financial_year_id'], $data['business_date']);
            $lines = $this->prepareLines($companyId, $data);
            $activeKey = $this->activeKeyFor((string) $data['type'], $lines);
            $this->assertActiveIdentityAvailable($companyId, (int) $data['financial_year_id'], $activeKey);
            $opening = OpeningBalance::create([
                'company_id' => $companyId, 'financial_year_id' => $data['financial_year_id'],
                'business_date' => $data['business_date'], 'type' => $data['type'],
                'reference_number' => trim($data['reference_number']), 'remarks' => $data['remarks'] ?? null,
                'status' => OpeningBalance::STATUS_DRAFT, 'active_key' => $activeKey, 'request_key' => $data['request_key'], 'created_by' => $userId,
            ]);
            foreach ($lines as $index => $line) {
                $opening->lines()->create($line + ['line_number' => $index + 1]);
            }
            $this->audit($opening, 'created', null, OpeningBalance::STATUS_DRAFT, $userId);
            return $opening->load('lines');
        });
    }

    public function postedOperationalBalances(int $companyId, iterable $accountIds): \Illuminate\Support\Collection
    {
        $ids = collect($accountIds)->map(fn ($id): int => (int) $id)->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return OpeningBalanceLine::query()
            ->whereIn('operational_account_id', $ids)
            ->whereHas('openingBalance', fn ($query) => $query
                ->where('company_id', $companyId)
                ->where('status', OpeningBalance::STATUS_POSTED))
            ->get(['operational_account_id', 'debit', 'credit'])
            ->groupBy('operational_account_id')
            ->map(function ($lines): string {
                $balance = 0;
                foreach ($lines as $line) {
                    $balance += $this->scaled($line->debit);
                    $balance -= $this->scaled($line->credit);
                }
                $sign = $balance < 0 ? '-' : '';
                return $sign . $this->decimal(abs($balance));
            });
    }

    public function update(OpeningBalance $opening, int $companyId, int $userId, array $data): OpeningBalance
    {
        return DB::transaction(function () use ($opening, $companyId, $userId, $data) {
            $this->assertActor($companyId, $userId);
            $opening = $this->lock($opening, $companyId);
            $this->assertEditable($opening);
            $this->assertContext($companyId, (int) $data['financial_year_id'], $data['business_date']);
            $lines = $this->prepareLines($companyId, $data);
            $activeKey = $this->activeKeyFor((string) $data['type'], $lines);
            $this->assertActiveIdentityAvailable($companyId, (int) $data['financial_year_id'], $activeKey, $opening->id);
            $opening->update([
                'financial_year_id' => $data['financial_year_id'], 'business_date' => $data['business_date'],
                'type' => $data['type'], 'reference_number' => trim($data['reference_number']),
                'remarks' => $data['remarks'] ?? null, 'active_key' => $activeKey,
            ]);
            $opening->lines()->delete();
            foreach ($lines as $index => $line) {
                $opening->lines()->create($line + ['line_number' => $index + 1]);
            }
            $this->audit($opening, 'updated', $opening->status, $opening->status, $userId);
            return $opening->load('lines');
        });
    }

    public function submit(OpeningBalance $opening, int $companyId, int $userId): OpeningBalance
    {
        return $this->transition($opening, $companyId, $userId, OpeningBalance::STATUS_DRAFT, OpeningBalance::STATUS_SUBMITTED, 'submitted');
    }

    public function approve(OpeningBalance $opening, int $companyId, int $userId): OpeningBalance
    {
        return DB::transaction(function () use ($opening, $companyId, $userId) {
            $this->assertActor($companyId, $userId);
            $opening = $this->lock($opening, $companyId);
            if ($opening->status !== OpeningBalance::STATUS_SUBMITTED) throw new RuntimeException('Only a submitted Opening Balance may be approved.');
            if ((int) $opening->created_by === $userId) throw new RuntimeException('The maker cannot approve their own Opening Balance.');
            $this->assertContext($companyId, $opening->financial_year_id, $opening->business_date->format('Y-m-d'));
            $this->validateDocumentLines($companyId, $opening->type, $opening->lines->toArray(), $opening->active_key);
            $opening->update(['status' => OpeningBalance::STATUS_APPROVED, 'approved_by' => $userId, 'approved_at' => now()]);
            $this->audit($opening, 'approved', OpeningBalance::STATUS_SUBMITTED, OpeningBalance::STATUS_APPROVED, $userId);
            return $opening;
        });
    }

    public function post(OpeningBalance $opening, int $companyId, int $userId): OpeningBalance
    {
        return DB::transaction(function () use ($opening, $companyId, $userId) {
            $this->assertActor($companyId, $userId);
            $opening = $this->lock($opening, $companyId);
            if ($opening->status !== OpeningBalance::STATUS_APPROVED || $opening->is_locked) throw new RuntimeException('Only an unlocked approved Opening Balance may be posted.');
            $this->assertContext($companyId, $opening->financial_year_id, $opening->business_date->format('Y-m-d'));
            $lines = $this->validateDocumentLines($companyId, $opening->type, $opening->lines->toArray(), $opening->active_key);
            if ($opening->journal_id || $opening->accounting_entry_id) throw new RuntimeException('Opening Balance posting identity is inconsistent or already used.');
            $sourceKey = 'opening-balance:' . $opening->id . ':posted';
            $journal = $this->createJournal($opening, $lines, $userId, $sourceKey);
            $entry = $this->accounting->post([
                'company_id' => $companyId, 'financial_year_id' => $opening->financial_year_id,
                'entry_date' => $opening->business_date->format('Y-m-d'), 'reference_number' => $opening->reference_number,
                'source_module' => 'opening_balance', 'source_type' => 'opening_balance', 'source_id' => $opening->id,
                'source_event' => 'posted', 'source_key' => $sourceKey, 'description' => $opening->remarks,
                'posted_by' => $userId, 'lines' => array_map(fn ($line) => [
                    'chart_account_id' => $line['chart_account_id'], 'operational_account_id' => $line['operational_account_id'],
                    'description' => $line['description'], 'debit' => $line['debit'], 'credit' => $line['credit'],
                    'subledger_type' => $line['subledger_type'], 'subledger_id' => $line['subledger_id'],
                ], $lines),
            ]);
            $this->createAuxiliaryEffects($opening, $lines, $journal, $userId);
            $opening->update(['status' => OpeningBalance::STATUS_POSTED, 'journal_id' => $journal->id,
                'accounting_entry_id' => $entry->id, 'posted_by' => $userId, 'posted_at' => now()]);
            $this->audit($opening, 'posted', OpeningBalance::STATUS_APPROVED, OpeningBalance::STATUS_POSTED, $userId,
                null, ['journal_id' => $journal->id, 'accounting_entry_id' => $entry->id]);
            return $opening->fresh(['lines', 'journal', 'accountingEntry']);
        });
    }

    public function cancel(OpeningBalance $opening, int $companyId, int $userId, string $reason): OpeningBalance
    {
        if (trim($reason) === '') throw ValidationException::withMessages(['reason' => 'Cancellation reason is required.']);
        return DB::transaction(function () use ($opening, $companyId, $userId, $reason) {
            $this->assertActor($companyId, $userId);
            $opening = $this->lock($opening, $companyId);
            if (! in_array($opening->status, [OpeningBalance::STATUS_DRAFT, OpeningBalance::STATUS_SUBMITTED, OpeningBalance::STATUS_APPROVED], true)) {
                throw new RuntimeException('Only an unposted Opening Balance may be cancelled. Posted records require reversal.');
            }
            $this->assertContext($companyId, $opening->financial_year_id, $opening->business_date->format('Y-m-d'));
            $previous = $opening->status;
            $opening->update(['status' => OpeningBalance::STATUS_CANCELLED, 'cancelled_by' => $userId,
                'active_key' => null, 'cancelled_at' => now(), 'cancellation_reason' => trim($reason)]);
            $this->audit($opening, 'cancelled', $previous, OpeningBalance::STATUS_CANCELLED, $userId, $reason);
            return $opening;
        });
    }

    public function reverse(OpeningBalance $opening, int $companyId, int $userId, string $reason): OpeningBalance
    {
        if (trim($reason) === '') throw ValidationException::withMessages(['reason' => 'Reversal reason is required.']);
        return DB::transaction(function () use ($opening, $companyId, $userId, $reason) {
            $this->assertActor($companyId, $userId);
            $opening = $this->lock($opening, $companyId);
            if ($opening->status !== OpeningBalance::STATUS_POSTED || $opening->is_locked) throw new RuntimeException('Only an unlocked posted Opening Balance may be reversed.');
            $this->assertContext($companyId, $opening->financial_year_id, $opening->business_date->format('Y-m-d'));
            $journal = Journal::where('company_id', $companyId)->with('items')->lockForUpdate()->find($opening->journal_id);
            $entry = AccountingEntry::where('company_id', $companyId)->lockForUpdate()->find($opening->accounting_entry_id);
            if (! $journal || ! $entry || ! $journal->isPosted() || $entry->status !== 'posted') throw new RuntimeException('Required original Journal or Accounting Entry is missing or inconsistent.');
            $reversalKey = 'opening-balance:' . $opening->id . ':reversed';
            if (Journal::where('company_id', $companyId)->where('reversal_of_journal_id', $journal->id)->exists()) throw new RuntimeException('This Opening Balance has already been reversed.');
            $reversalJournal = $this->reverseJournal($opening, $journal, $userId, $reversalKey, $reason);
            $this->reverseAuxiliaryEffects($opening, $journal, $reversalJournal, $userId);
            $this->accounting->reverseBySource([
                'company_id' => $companyId, 'financial_year_id' => $opening->financial_year_id,
                'entry_date' => $opening->business_date->format('Y-m-d'), 'original_source_key' => 'opening-balance:' . $opening->id . ':posted',
                'reversal_source_key' => $reversalKey, 'source_module' => 'opening_balance', 'source_type' => 'opening_balance',
                'source_id' => $opening->id, 'source_event' => 'reversed', 'original_source_event' => 'posted',
                'reference_number' => 'REV-' . $opening->reference_number, 'description' => $reason, 'posted_by' => $userId,
            ]);
            $journal->update(['status' => Journal::STATUS_REVERSED, 'reversed_by' => $userId, 'reversed_at' => now()]);
            $opening->update(['status' => OpeningBalance::STATUS_REVERSED, 'reversed_by' => $userId,
                'active_key' => null, 'reversed_at' => now(), 'reversal_reason' => trim($reason)]);
            $this->audit($opening, 'reversed', OpeningBalance::STATUS_POSTED, OpeningBalance::STATUS_REVERSED, $userId,
                $reason, ['reversal_journal_id' => $reversalJournal->id]);
            return $opening;
        });
    }

    public function setLock(OpeningBalance $opening, int $companyId, int $userId, bool $locked, string $reason): OpeningBalance
    {
        if (trim($reason) === '') throw ValidationException::withMessages(['reason' => 'Lock or unlock reason is required.']);
        return DB::transaction(function () use ($opening, $companyId, $userId, $locked, $reason) {
            $this->assertActor($companyId, $userId);
            $opening = $this->lock($opening, $companyId);
            if ($opening->is_locked === $locked) throw new RuntimeException($locked ? 'Opening Balance is already locked.' : 'Opening Balance is not locked.');
            $opening->update(['is_locked' => $locked, 'locked_by' => $locked ? $userId : null,
                'locked_at' => $locked ? now() : null, 'lock_reason' => trim($reason)]);
            $this->audit($opening, $locked ? 'locked' : 'unlocked', $opening->status, $opening->status, $userId, $reason);
            return $opening;
        });
    }

    private function validateLines(int $companyId, array $lines): array
    {
        if (count($lines) < 2) throw ValidationException::withMessages(['lines' => 'At least two Opening Balance lines are required.']);
        $debits = 0; $credits = 0; $validated = [];
        foreach ($lines as $index => $line) {
            $account = ChartAccount::where('company_id', $companyId)->whereKey($line['chart_account_id'] ?? 0)->first();
            if (! $account || $account->status !== 'active'
                || (\Illuminate\Support\Facades\Schema::hasColumn('chart_accounts', 'is_locked') && $account->is_locked)
                || (int) $account->level !== 3 || ! in_array($account->account_class, ['asset', 'liability', 'equity'], true)) {
                throw ValidationException::withMessages(["lines.{$index}.chart_account_id" => 'Only active company Level 3 Asset, Liability, or Equity posting accounts are allowed.']);
            }
            if (! $account->is_control && ! $account->allow_manual_entry && $account->system_code !== 'OPENING_BALANCE_EQUITY') throw ValidationException::withMessages(["lines.{$index}.chart_account_id" => 'This Chart Account does not allow Opening Balance posting.']);
            if ($account->is_control && empty($line['subledger_type'])) throw ValidationException::withMessages(["lines.{$index}.subledger_type" => 'A control account requires a valid subledger.']);
            $debit = $this->scaled($line['debit'] ?? 0); $credit = $this->scaled($line['credit'] ?? 0);
            if (($debit <= 0 && $credit <= 0) || ($debit > 0 && $credit > 0)) throw ValidationException::withMessages(["lines.{$index}" => 'Each line must contain one positive debit or credit amount.']);
            $subledgerType = $line['subledger_type'] ?? null; $subledgerId = isset($line['subledger_id']) ? (int) $line['subledger_id'] : null;
            $this->assertSubledger($companyId, $account, $subledgerType, $subledgerId, $debit, $credit, $index);
            $operationalId = isset($line['operational_account_id']) ? (int) $line['operational_account_id'] : null;
            if ($operationalId) {
                $operational = Account::where('company_id', $companyId)->where('status', 'active')->lockForUpdate()->find($operationalId);
                if (! $operational || $account->account_class !== 'asset' || $debit <= 0) throw ValidationException::withMessages(["lines.{$index}.operational_account_id" => 'Cash/Bank opening requires an active company account and an Asset debit line.']);
            }
            $debits += $debit; $credits += $credit;
            $validated[] = [
                'chart_account_id' => $account->id, 'operational_account_id' => $operationalId,
                'debit' => $this->decimal($debit), 'credit' => $this->decimal($credit),
                'base_debit' => $this->decimal($debit), 'base_credit' => $this->decimal($credit),
                'subledger_type' => $subledgerType, 'subledger_id' => $subledgerId,
                'description' => $line['description'] ?? null, 'line_reference' => $line['line_reference'] ?? null,
                'currency' => $line['currency'] ?? null, 'exchange_rate' => $line['exchange_rate'] ?? null,
            ];
        }
        if ($debits !== $credits) throw ValidationException::withMessages(['lines' => 'Total debit must equal total credit.']);
        return $validated;
    }

    private function assertSubledger(int $companyId, ChartAccount $account, ?string $type, ?int $id, int $debit, int $credit, int $index): void
    {
        if ($type === null && $id === null) return;
        if (! in_array($type, ['customer', 'supplier'], true) || ! $id) throw ValidationException::withMessages(["lines.{$index}.subledger_id" => 'A valid Customer or Supplier subledger is required.']);
        if ($type === 'customer') {
            if (! Customer::where('company_id', $companyId)->whereKey($id)->exists() || $account->system_code !== 'ACCOUNTS_RECEIVABLE' || $debit <= 0) throw ValidationException::withMessages(["lines.{$index}" => 'Customer opening balances require a company Customer and debit to Accounts Receivable.']);
        } else {
            if (! Supplier::where('company_id', $companyId)->whereKey($id)->exists() || $account->system_code !== 'ACCOUNTS_PAYABLE' || $credit <= 0) throw ValidationException::withMessages(["lines.{$index}" => 'Supplier opening balances require a company Supplier and credit to Accounts Payable.']);
        }
    }

    private function assertContext(int $companyId, int $financialYearId, string $date): FinancialYear
    {
        $company = Company::whereKey($companyId)->first();
        if (! $company || $company->status !== 'active') throw new RuntimeException('The company is not active for financial posting.');
        $fy = FinancialYear::where('company_id', $companyId)->where('is_active', 1)->find($financialYearId);
        if (! $fy || (\Illuminate\Support\Facades\Schema::hasColumn('financial_years', 'is_closed') && $fy->is_closed)
            || (\Illuminate\Support\Facades\Schema::hasColumn('financial_years', 'is_locked') && $fy->is_locked)
            || $date < $fy->start_date || $date > $fy->end_date) {
            throw ValidationException::withMessages(['business_date' => 'Business Date must belong to an active, open, unlocked Financial Year.']);
        }
        if (\Illuminate\Support\Facades\Schema::hasTable('accounting_period_locks')
            && \App\Models\AccountingPeriodLock::where('company_id', $companyId)->where('financial_year_id', $financialYearId)
                ->where('is_locked', 1)->whereDate('date_from', '<=', $date)->whereDate('date_to', '>=', $date)->exists()) {
            throw ValidationException::withMessages(['business_date' => 'Business Date belongs to a locked accounting period.']);
        }
        return $fy;
    }

    private function prepareLines(int $companyId, array $data): array
    {
        if (($data['type'] ?? null) !== 'new_account') {
            return $this->validateLines($companyId, $this->resolveAccountPickerLines($companyId, $data['lines'] ?? []));
        }

        $operationalAccount = Account::where('company_id', $companyId)
            ->where('status', 'active')
            ->lockForUpdate()
            ->find($data['new_account_id'] ?? 0);

        $systemCode = match ($operationalAccount?->account_type) {
            'Cash' => 'CASH_IN_HAND',
            'Bank', 'ATM', 'Wallet' => 'BANK_ACCOUNTS',
            default => null,
        };

        if (! $operationalAccount || $systemCode === null) {
            throw ValidationException::withMessages([
                'new_account_id' => 'New Account Opening Balance requires an active company Cash, Bank, ATM, or Wallet account.',
            ]);
        }

        $mappedChartAccount = $this->systemChartAccount($companyId, $systemCode, 'new_account_id');
        $openingBalanceEquity = $this->systemChartAccount($companyId, 'OPENING_BALANCE_EQUITY', 'new_account_id');
        $amount = $this->decimal($this->scaled($data['new_account_amount'] ?? 0));

        if ($this->scaled($amount) <= 0) {
            throw ValidationException::withMessages(['new_account_amount' => 'New Account Opening Balance amount must be greater than zero.']);
        }

        $description = trim((string) ($data['remarks'] ?? ''));
        $description = $description !== '' ? $description : 'New account opening balance - ' . $operationalAccount->account_name;

        return $this->validateDocumentLines($companyId, 'new_account', [
            [
                'chart_account_id' => $mappedChartAccount->id,
                'operational_account_id' => $operationalAccount->id,
                'debit' => $amount,
                'credit' => '0.0000',
                'description' => $description,
            ],
            [
                'chart_account_id' => $openingBalanceEquity->id,
                'debit' => '0.0000',
                'credit' => $amount,
                'description' => $description . ' - Opening Balance Equity offset',
            ],
        ]);
    }

    private function resolveAccountPickerLines(int $companyId, array $lines): array
    {
        return array_map(function (array $line, int $index) use ($companyId): array {
            $picker = trim((string) ($line['account_picker'] ?? ''));

            if ($picker === '') {
                return $line;
            }

            if (! preg_match('/^(operational|customer|supplier|chart):([1-9][0-9]*)$/', $picker, $matches)) {
                throw ValidationException::withMessages(["lines.{$index}.account_picker" => 'Select a valid Opening Balance account.']);
            }

            [$token, $kind, $id] = $matches;
            $id = (int) $id;
            unset($line['account_picker'], $line['chart_account_id'], $line['operational_account_id'], $line['subledger_type'], $line['subledger_id']);

            if ($kind === 'operational') {
                $account = Account::where('company_id', $companyId)->where('status', 'active')->find($id);
                $systemCode = match ($account?->account_type) {
                    'Cash' => 'CASH_IN_HAND',
                    'Bank', 'ATM', 'Wallet' => 'BANK_ACCOUNTS',
                    default => null,
                };
                if (! $account || $systemCode === null) {
                    throw ValidationException::withMessages(["lines.{$index}.account_picker" => 'Select an active company Cash, Bank, ATM, or Wallet account.']);
                }
                $line['chart_account_id'] = $this->systemChartAccount($companyId, $systemCode, "lines.{$index}.account_picker")->id;
                $line['operational_account_id'] = $account->id;
            } elseif ($kind === 'customer') {
                $customer = Customer::where('company_id', $companyId)->where('status', 'active')->find($id);
                if (! $customer) throw ValidationException::withMessages(["lines.{$index}.account_picker" => 'Select an active company Customer.']);
                $line['chart_account_id'] = $this->systemChartAccount($companyId, 'ACCOUNTS_RECEIVABLE', "lines.{$index}.account_picker")->id;
                $line['subledger_type'] = 'customer';
                $line['subledger_id'] = $customer->id;
            } elseif ($kind === 'supplier') {
                $supplier = Supplier::where('company_id', $companyId)->where('status', 'active')->find($id);
                if (! $supplier) throw ValidationException::withMessages(["lines.{$index}.account_picker" => 'Select an active company Supplier.']);
                $line['chart_account_id'] = $this->systemChartAccount($companyId, 'ACCOUNTS_PAYABLE', "lines.{$index}.account_picker")->id;
                $line['subledger_type'] = 'supplier';
                $line['subledger_id'] = $supplier->id;
            } else {
                $chartAccount = ChartAccount::where('company_id', $companyId)->where('status', 'active')->find($id);
                if (! $chartAccount) throw ValidationException::withMessages(["lines.{$index}.account_picker" => 'Select an active company Chart Account.']);
                $line['chart_account_id'] = $chartAccount->id;
            }

            return $line;
        }, $lines, array_keys($lines));
    }

    private function validateDocumentLines(int $companyId, string $type, array $lines, ?string $activeKey = null): array
    {
        $validated = $this->validateLines($companyId, $lines);

        if ($type !== 'new_account') {
            return $validated;
        }

        if (count($validated) !== 2) {
            throw ValidationException::withMessages(['lines' => 'New Account Opening Balance requires exactly one operational debit and one Opening Balance Equity credit.']);
        }

        $operationalLines = array_values(array_filter($validated, fn (array $line): bool => $line['operational_account_id'] !== null));
        $equityLines = array_values(array_filter($validated, function (array $line) use ($companyId): bool {
            return ChartAccount::where('company_id', $companyId)
                ->whereKey($line['chart_account_id'])
                ->where('system_code', 'OPENING_BALANCE_EQUITY')
                ->exists();
        }));

        if (count($operationalLines) !== 1 || count($equityLines) !== 1) {
            throw ValidationException::withMessages(['lines' => 'New Account Opening Balance requires one operational account line and one Opening Balance Equity line.']);
        }

        $operationalLine = $operationalLines[0];
        $equityLine = $equityLines[0];
        $operationalAccount = Account::where('company_id', $companyId)->where('status', 'active')->find($operationalLine['operational_account_id']);
        $expectedCode = match ($operationalAccount?->account_type) {
            'Cash' => 'CASH_IN_HAND',
            'Bank', 'ATM', 'Wallet' => 'BANK_ACCOUNTS',
            default => null,
        };
        $operationalChart = ChartAccount::where('company_id', $companyId)->find($operationalLine['chart_account_id']);

        if ($expectedCode === null || $operationalChart?->system_code !== $expectedCode
            || $this->scaled($operationalLine['debit']) <= 0 || $this->scaled($operationalLine['credit']) !== 0
            || $this->scaled($equityLine['debit']) !== 0
            || $this->scaled($equityLine['credit']) !== $this->scaled($operationalLine['debit'])
            || $operationalLine['subledger_type'] !== null || $equityLine['subledger_type'] !== null) {
            throw ValidationException::withMessages(['lines' => 'New Account Opening Balance supports only a mapped operational debit with an equal Opening Balance Equity credit.']);
        }

        if ($activeKey !== null && $activeKey !== $this->newAccountActiveKey($operationalAccount->id)) {
            throw new RuntimeException('New Account Opening Balance identity does not match its operational account.');
        }

        return $validated;
    }

    private function activeKeyFor(string $type, array $lines): string
    {
        if ($type !== 'new_account') {
            return 'active';
        }

        $operationalLine = collect($lines)->first(fn (array $line): bool => $line['operational_account_id'] !== null);

        if (! $operationalLine) {
            throw ValidationException::withMessages(['new_account_id' => 'New Account Opening Balance operational account is required.']);
        }

        return $this->newAccountActiveKey((int) $operationalLine['operational_account_id']);
    }

    private function newAccountActiveKey(int $accountId): string
    {
        return 'na:' . str_pad(dechex($accountId), 16, '0', STR_PAD_LEFT);
    }

    private function assertActiveIdentityAvailable(int $companyId, int $financialYearId, string $activeKey, ?int $ignoreId = null): void
    {
        $exists = OpeningBalance::where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->where('active_key', $activeKey)
            ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->lockForUpdate()
            ->exists();

        if ($exists) {
            $field = str_starts_with($activeKey, 'na:') ? 'new_account_id' : 'financial_year_id';
            $message = str_starts_with($activeKey, 'na:')
                ? 'An active Opening Balance already exists for this operational Account and Financial Year.'
                : 'An active official Opening Balance already exists for this company and Financial Year.';
            throw ValidationException::withMessages([$field => $message]);
        }
    }

    private function systemChartAccount(int $companyId, string $systemCode, string $field): ChartAccount
    {
        $matches = ChartAccount::where('company_id', $companyId)
            ->where('status', 'active')
            ->where('level', 3)
            ->where('system_code', $systemCode)
            ->when(Schema::hasColumn('chart_accounts', 'is_locked'), fn ($query) => $query->where('is_locked', 0))
            ->get();

        if ($matches->count() !== 1) {
            throw ValidationException::withMessages([$field => "Required company Chart Account {$systemCode} is missing, duplicated, inactive, or locked."]);
        }

        return $matches->first();
    }

    private function createJournal(OpeningBalance $opening, array $lines, int $userId, string $sourceKey): Journal
    {
        $total = array_reduce($lines, fn ($sum, $line) => $sum + $this->scaled($line['debit']), 0);
        $journal = Journal::create(['company_id' => $opening->company_id, 'financial_year_id' => $opening->financial_year_id,
            'journal_no' => 'OB-' . $opening->id, 'journal_date' => $opening->business_date,
            ...Schema::hasColumn('journals', 'journal_type') ? ['journal_type' => Journal::TYPE_OPENING] : [],
            'reference_no' => $opening->reference_number,
            'source_module' => 'opening_balance', 'source_type' => 'opening_balance', 'source_id' => $opening->id,
            'source_key' => $sourceKey, 'total_amount' => $this->decimal($total), 'note' => $opening->remarks,
            'created_by' => $userId, 'posted_by' => $userId, 'posted_at' => now(), 'status' => Journal::STATUS_POSTED]);
        foreach ($lines as $line) $journal->items()->create(['company_id' => $opening->company_id,
            'account_id' => $line['operational_account_id'], 'chart_account_id' => $line['chart_account_id'],
            'sub_ledger_type' => $line['subledger_type'], 'sub_ledger_id' => $line['subledger_id'],
            'type' => $this->scaled($line['debit']) > 0 ? 'debit' : 'credit',
            'amount' => $this->scaled($line['debit']) > 0 ? $line['debit'] : $line['credit'], 'note' => $line['description'], 'status' => 1]);
        return $journal->load('items');
    }

    private function createAuxiliaryEffects(OpeningBalance $opening, array $lines, Journal $journal, int $userId): void
    {
        foreach ($lines as $index => $line) {
            $item = $journal->items[$index];
            $common = ['company_id' => $opening->company_id, 'financial_year_id' => $opening->financial_year_id,
                'transaction_date' => $opening->business_date->format('Y-m-d'), 'voucher_no' => $journal->journal_no,
                'reference_type' => 'opening_balance', 'reference_id' => $opening->id, 'reference_no' => $opening->reference_number,
                'journal_item_id' => $item->id, 'description' => $line['description'], 'debit' => $line['debit'],
                'credit' => $line['credit'], 'created_by' => $userId, 'status' => 1];
            if ($line['subledger_type'] === 'customer') CustomerTransactionService::createTransaction($common + ['customer_id' => $line['subledger_id']]);
            if ($line['subledger_type'] === 'supplier') SupplierTransactionService::createTransaction($common + ['supplier_id' => $line['subledger_id']]);
            if ($line['operational_account_id']) AccountBalanceService::createTransaction($common + ['account_id' => $line['operational_account_id']], false);
        }
    }

    private function reverseJournal(OpeningBalance $opening, Journal $original, int $userId, string $sourceKey, string $reason): Journal
    {
        $journal = Journal::create(['company_id' => $opening->company_id, 'financial_year_id' => $opening->financial_year_id,
            'journal_no' => 'REV-OB-' . $opening->id, 'journal_date' => $opening->business_date,
            ...Schema::hasColumn('journals', 'journal_type') ? ['journal_type' => Journal::TYPE_REVERSAL] : [],
            'reference_no' => 'REV-' . $opening->reference_number, 'source_module' => 'opening_balance',
            'source_type' => 'opening_balance_reversal', 'source_id' => $opening->id, 'source_key' => $sourceKey,
            'total_amount' => $original->total_amount, 'note' => $reason,
            'created_by' => $userId, 'posted_by' => $userId, 'posted_at' => now(),
            'reversal_of_journal_id' => $original->id, 'status' => Journal::STATUS_POSTED]);
        foreach ($original->items as $item) $journal->items()->create(['company_id' => $opening->company_id,
            'account_id' => $item->account_id, 'chart_account_id' => $item->chart_account_id,
            'sub_ledger_type' => $item->sub_ledger_type, 'sub_ledger_id' => $item->sub_ledger_id,
            'type' => $item->type === 'debit' ? 'credit' : 'debit', 'amount' => $item->amount,
            'note' => 'Reversal: ' . $item->note, 'status' => 1]);
        return $journal->load('items');
    }

    private function reverseAuxiliaryEffects(OpeningBalance $opening, Journal $original, Journal $reversal, int $userId): void
    {
        foreach ($original->items as $index => $item) {
            $reverseItem = $reversal->items[$index];
            if ($item->sub_ledger_type === 'customer') {
                $source = CustomerTransaction::where('company_id', $opening->company_id)->where('journal_item_id', $item->id)->where('status', 1)->lockForUpdate()->get();
                if ($source->count() !== 1 || CustomerTransaction::where('reversed_transaction_id', $source->first()?->id)->exists()) throw new RuntimeException('Customer Opening Balance source transaction is missing, duplicated, or already reversed.');
                $tx = CustomerTransactionService::createTransaction($this->reversalData($opening, $source->first(), $reverseItem, $userId) + ['customer_id' => $source->first()->customer_id]);
                $tx->update(['reversed_transaction_id' => $source->first()->id]);
            }
            if ($item->sub_ledger_type === 'supplier') {
                $source = SupplierTransaction::where('company_id', $opening->company_id)->where('journal_item_id', $item->id)->where('status', 1)->lockForUpdate()->get();
                if ($source->count() !== 1 || SupplierTransaction::where('reversed_transaction_id', $source->first()?->id)->exists()) throw new RuntimeException('Supplier Opening Balance source transaction is missing, duplicated, or already reversed.');
                $tx = SupplierTransactionService::createTransaction($this->reversalData($opening, $source->first(), $reverseItem, $userId) + ['supplier_id' => $source->first()->supplier_id]);
                $tx->update(['reversed_transaction_id' => $source->first()->id]);
            }
            if ($item->account_id) {
                $source = AccountTransaction::where('company_id', $opening->company_id)->where('journal_item_id', $item->id)->where('status', 1)->lockForUpdate()->get();
                if ($source->count() !== 1 || AccountTransaction::where('reversed_transaction_id', $source->first()?->id)->exists()) throw new RuntimeException('Cash/Bank Opening Balance source transaction is missing, duplicated, or already reversed.');
                AccountBalanceService::createTransaction($this->reversalData($opening, $source->first(), $reverseItem, $userId) + ['account_id' => $source->first()->account_id], false);
            }
        }
    }

    private function reversalData(OpeningBalance $opening, object $source, JournalItem $item, int $userId): array
    {
        return ['company_id' => $opening->company_id, 'financial_year_id' => $opening->financial_year_id,
            'transaction_date' => $opening->business_date->format('Y-m-d'), 'voucher_no' => 'REV-OB-' . $opening->id,
            'reference_type' => 'opening_balance_reversal', 'reference_id' => $opening->id,
            'reference_no' => 'REV-' . $opening->reference_number, 'journal_item_id' => $item->id,
            'reversed_transaction_id' => $source->id, 'description' => 'Opening Balance reversal',
            'debit' => $source->credit, 'credit' => $source->debit, 'created_by' => $userId, 'status' => 1];
    }

    private function transition(OpeningBalance $opening, int $companyId, int $userId, string $from, string $to, string $event): OpeningBalance
    {
        return DB::transaction(function () use ($opening, $companyId, $userId, $from, $to, $event) {
            $this->assertActor($companyId, $userId);
            $opening = $this->lock($opening, $companyId);
            if ($opening->status !== $from || $opening->is_locked) throw new RuntimeException("Only an unlocked {$from} Opening Balance may be {$event}.");
            $this->assertContext($companyId, $opening->financial_year_id, $opening->business_date->format('Y-m-d'));
            $this->validateDocumentLines($companyId, $opening->type, $opening->lines->toArray(), $opening->active_key);
            $opening->update(['status' => $to, $event . '_by' => $userId, $event . '_at' => now()]);
            $this->audit($opening, $event, $from, $to, $userId);
            return $opening;
        });
    }

    private function lock(OpeningBalance $opening, int $companyId): OpeningBalance
    {
        return OpeningBalance::where('company_id', $companyId)->with('lines')->lockForUpdate()->findOrFail($opening->id);
    }
    private function assertEditable(OpeningBalance $opening): void
    {
        if ($opening->status !== OpeningBalance::STATUS_DRAFT || $opening->is_locked) throw new RuntimeException('Only an unlocked draft Opening Balance may be edited.');
    }
    private function assertActor(int $companyId, int $userId): void
    {
        $query = \App\Models\User::where('company_id', $companyId)->whereKey($userId);
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'account_status')) $query->where('account_status', 'active');
        if (! $query->exists()) throw new RuntimeException('The user is not active in this company.');
    }
    private function audit(OpeningBalance $opening, string $event, ?string $previous, ?string $new, int $userId, ?string $reason = null, array $metadata = []): void
    {
        OpeningBalanceAuditEvent::create(['company_id' => $opening->company_id, 'financial_year_id' => $opening->financial_year_id,
            'opening_balance_id' => $opening->id, 'event' => $event, 'previous_status' => $previous,
            'new_status' => $new, 'user_id' => $userId, 'reason' => $reason,
            'metadata' => $metadata ?: null, 'occurred_at' => now()]);
    }
    private function scaled(mixed $value): int
    {
        $value = trim((string) ($value ?? '0'));
        if (! preg_match('/^\d+(?:\.\d{1,4})?$/', $value)) throw ValidationException::withMessages(['lines' => 'Amounts must be non-negative decimals with no more than four decimal places.']);
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');
        return ((int) $whole * 10000) + (int) str_pad($fraction, 4, '0');
    }
    private function decimal(int $scaled): string { return intdiv($scaled, 10000) . '.' . str_pad((string) ($scaled % 10000), 4, '0', STR_PAD_LEFT); }
}
