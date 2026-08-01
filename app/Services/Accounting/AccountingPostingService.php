<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\AccountingEntry;
use App\Models\ChartAccount;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class AccountingPostingService
{
    public function post(array $data): AccountingEntry
    {
        $companyId = $this->positiveInteger($data['company_id'] ?? null, 'company_id');
        $financialYearId = $this->nullablePositiveInteger($data['financial_year_id'] ?? null, 'financial_year_id');
        $entryDate = $this->entryDate($data['entry_date'] ?? null);
        $sourceModule = $this->requiredString($data, 'source_module');
        $sourceType = $this->requiredString($data, 'source_type');
        $sourceKey = $this->requiredString($data, 'source_key');
        $sourceId = $this->nullablePositiveInteger($data['source_id'] ?? null, 'source_id');
        $sourceEvent = $this->requiredString($data, 'source_event');
        $sourceTypeAliases = $this->sourceTypeAliases($data['source_type_aliases'] ?? [], $sourceType);
        $postedBy = $this->nullablePositiveInteger($data['posted_by'] ?? null, 'posted_by');
        $lines = $data['lines'] ?? null;

        if (! is_array($lines) || count($lines) < 2) {
            throw new InvalidArgumentException('An accounting entry requires at least two lines.');
        }

        $normalizedLines = $this->normalizeLines($lines);

        return DB::transaction(function () use (
            $companyId,
            $financialYearId,
            $entryDate,
            $sourceModule,
            $sourceType,
            $sourceKey,
            $sourceId,
            $sourceEvent,
            $sourceTypeAliases,
            $postedBy,
            $data,
            $normalizedLines
        ): AccountingEntry {
            $duplicate = AccountingEntry::query()
                ->forCompany($companyId)
                ->where('source_key', $sourceKey)
                ->exists() || AccountingEntry::query()
                    ->forCompany($companyId)
                    ->whereIn('source_type', $sourceTypeAliases)
                    ->where('source_id', $sourceId)
                    ->where('source_event', $sourceEvent)
                    ->exists();

            if ($duplicate) {
                throw new RuntimeException('An accounting entry has already been posted for this source key.');
            }

            $chartAccountIds = $this->resolveChartAccounts($companyId, $normalizedLines);
            $this->resolveOperationalAccounts($companyId, $normalizedLines);

            $entryData = [
                'company_id' => $companyId,
                'entry_number' => 'TMP-' . Str::uuid()->toString(),
                'entry_date' => $entryDate,
                'reference_number' => $data['reference_number'] ?? null,
                'source_module' => $sourceModule,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'source_event' => $sourceEvent,
                'source_key' => $sourceKey,
                'description' => $data['description'] ?? null,
                'status' => 'posted',
                'posted_at' => now(),
                'posted_by' => $postedBy,
            ];
            if ($financialYearId !== null && Schema::hasColumn('accounting_entries', 'financial_year_id')) {
                $entryData['financial_year_id'] = $financialYearId;
            }
            $entry = AccountingEntry::create($entryData);

            foreach ($normalizedLines as $lineNumber => $line) {
                $entry->lines()->create([
                    'chart_account_id' => $chartAccountIds[$lineNumber],
                    'operational_account_id' => $line['operational_account_id'],
                    'line_number' => $lineNumber + 1,
                    'description' => $line['description'],
                    'debit' => $line['debit'],
                    'credit' => $line['credit'],
                    'subledger_type' => $line['subledger_type'],
                    'subledger_id' => $line['subledger_id'],
                ]);
            }

            return $entry;
        });
    }

    public function reverseBySource(array $data): AccountingEntry
    {
        $companyId = $this->positiveInteger($data['company_id'] ?? null, 'company_id');
        $financialYearId = $this->nullablePositiveInteger($data['financial_year_id'] ?? null, 'financial_year_id');
        $entryDate = $this->entryDate($data['entry_date'] ?? null);
        $originalSourceKey = $this->requiredString($data, 'original_source_key');
        $reversalSourceKey = $this->requiredString($data, 'reversal_source_key');
        $sourceModule = $this->requiredString($data, 'source_module');
        $sourceType = $this->requiredString($data, 'source_type');
        $sourceId = $this->nullablePositiveInteger($data['source_id'] ?? null, 'source_id');
        $sourceEvent = $this->requiredString($data, 'source_event');
        $originalSourceEvent = $this->requiredString($data, 'original_source_event');
        $originalSourceTypes = $this->sourceTypeAliases($data['original_source_types'] ?? [], $sourceType);
        $postedBy = $this->nullablePositiveInteger($data['posted_by'] ?? null, 'posted_by');

        return DB::transaction(function () use ($companyId, $financialYearId, $entryDate, $originalSourceKey, $reversalSourceKey, $sourceModule, $sourceType, $sourceId, $sourceEvent, $originalSourceEvent, $originalSourceTypes, $postedBy, $data): AccountingEntry {
            $original = AccountingEntry::query()
                ->forCompany($companyId)
                ->where('source_key', $originalSourceKey)
                ->whereIn('source_type', $originalSourceTypes)
                ->where('source_id', $sourceId)
                ->where('source_event', $originalSourceEvent)
                ->with('lines')
                ->lockForUpdate()
                ->first();

            if (! $original || $original->status !== 'posted') {
                throw new RuntimeException('The original posted accounting entry could not be resolved for reversal.');
            }

            if (AccountingEntry::query()->forCompany($companyId)->where('source_key', $reversalSourceKey)->exists()
                || AccountingEntry::query()->forCompany($companyId)->where('source_type', $sourceType)->where('source_id', $sourceId)->where('source_event', $sourceEvent)->exists()
                || AccountingEntry::query()->forCompany($companyId)->where('reversal_of_id', $original->id)->exists()) {
                throw new RuntimeException('This accounting entry has already been reversed.');
            }

            if ($original->lines->isEmpty()) {
                throw new RuntimeException('The original accounting entry has no lines to reverse.');
            }

            $reversalData = [
                'company_id' => $companyId,
                'entry_number' => 'TMP-' . Str::uuid()->toString(),
                'entry_date' => $entryDate,
                'reference_number' => $data['reference_number'] ?? null,
                'source_module' => $sourceModule,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'source_event' => $sourceEvent,
                'source_key' => $reversalSourceKey,
                'description' => $data['description'] ?? null,
                'status' => 'posted',
                'reversal_of_id' => $original->id,
                'posted_at' => now(),
                'posted_by' => $postedBy,
            ];
            if ($financialYearId !== null && Schema::hasColumn('accounting_entries', 'financial_year_id')) {
                $reversalData['financial_year_id'] = $financialYearId;
            }
            $reversal = AccountingEntry::create($reversalData);

            foreach ($original->lines as $line) {
                $reversal->lines()->create([
                    'chart_account_id' => $line->chart_account_id,
                    'operational_account_id' => $line->operational_account_id,
                    'line_number' => $line->line_number,
                    'description' => 'Reversal: ' . ($line->description ?? ''),
                    'debit' => $line->credit,
                    'credit' => $line->debit,
                    'subledger_type' => $line->subledger_type,
                    'subledger_id' => $line->subledger_id,
                ]);
            }

            $original->update(['status' => 'reversed']);

            return $reversal;
        });
    }

    private function normalizeLines(array $lines): array
    {
        $normalizedLines = [];
        $totalDebit = '0.0000';
        $totalCredit = '0.0000';

        foreach ($lines as $line) {
            if (! is_array($line)) {
                throw new InvalidArgumentException('Every accounting entry line must be an array.');
            }

            $debit = $this->normalizeAmount($line['debit'] ?? 0, 'debit');
            $credit = $this->normalizeAmount($line['credit'] ?? 0, 'credit');

            if ($this->isZero($debit) && $this->isZero($credit)) {
                throw new InvalidArgumentException('Zero-value accounting entry lines are not allowed.');
            }

            if (! $this->isZero($debit) && ! $this->isZero($credit)) {
                throw new InvalidArgumentException('An accounting entry line cannot contain both debit and credit.');
            }

            $chartAccountId = array_key_exists('chart_account_id', $line)
                ? $this->positiveInteger($line['chart_account_id'], 'chart_account_id')
                : null;
            $chartAccountSystemCode = array_key_exists('chart_account_system_code', $line)
                ? $this->requiredString($line, 'chart_account_system_code')
                : null;

            if ($chartAccountId === null && $chartAccountSystemCode === null) {
                throw new InvalidArgumentException('Every accounting entry line requires chart_account_id or chart_account_system_code.');
            }

            $normalizedLines[] = [
                'chart_account_id' => $chartAccountId,
                'chart_account_system_code' => $chartAccountSystemCode,
                'operational_account_id' => $this->nullablePositiveInteger(
                    $line['operational_account_id'] ?? null,
                    'operational_account_id'
                ),
                'description' => $line['description'] ?? null,
                'debit' => $debit,
                'credit' => $credit,
                'subledger_type' => $line['subledger_type'] ?? null,
                'subledger_id' => $this->nullablePositiveInteger(
                    $line['subledger_id'] ?? null,
                    'subledger_id'
                ),
            ];

            $totalDebit = $this->addAmounts($totalDebit, $debit);
            $totalCredit = $this->addAmounts($totalCredit, $credit);
        }

        if ($totalDebit !== $totalCredit) {
            throw new InvalidArgumentException('Total debit must equal total credit.');
        }

        return $normalizedLines;
    }

    private function resolveChartAccounts(int $companyId, array $lines): array
    {
        $explicitIds = array_values(array_unique(array_filter(
            array_column($lines, 'chart_account_id'),
            fn (?int $chartAccountId): bool => $chartAccountId !== null
        )));
        $systemCodes = array_values(array_unique(array_filter(array_column($lines, 'chart_account_system_code'))));

        $explicitAccounts = ChartAccount::query()
            ->forCompany($companyId)
            ->where('status', 'active')
            ->whereIn('id', $explicitIds)
            ->get()
            ->keyBy('id');

        if ($explicitAccounts->count() !== count($explicitIds)) {
            throw new RuntimeException('One or more explicit chart accounts could not be resolved for this company.');
        }

        $accounts = ChartAccount::query()
            ->forCompany($companyId)
            ->where('status', 'active')
            ->whereIn('system_code', $systemCodes)
            ->get()
            ->groupBy('system_code');

        $resolvedBySystemCode = [];

        foreach ($systemCodes as $systemCode) {
            $matches = $accounts->get($systemCode);

            if (! $matches || $matches->count() !== 1) {
                throw new RuntimeException("Chart account system code [{$systemCode}] could not be resolved for this company.");
            }

            $resolvedBySystemCode[$systemCode] = $matches->first()->id;
        }

        $resolved = [];

        foreach ($lines as $lineNumber => $line) {
            $explicitId = $line['chart_account_id'];
            $systemCode = $line['chart_account_system_code'];
            $systemCodeId = $systemCode === null ? null : $resolvedBySystemCode[$systemCode];

            if ($explicitId !== null && $systemCodeId !== null && $explicitId !== $systemCodeId) {
                throw new RuntimeException('The explicit chart account conflicts with chart_account_system_code.');
            }

            $resolved[$lineNumber] = $explicitId ?? $systemCodeId;
        }

        return $resolved;
    }

    private function resolveOperationalAccounts(int $companyId, array $lines): void
    {
        $operationalAccountIds = array_values(array_unique(array_filter(
            array_column($lines, 'operational_account_id'),
            fn (?int $accountId): bool => $accountId !== null
        )));

        if ($operationalAccountIds === []) {
            return;
        }

        $resolvedAccountIds = Account::query()
            ->where('company_id', $companyId)
            ->whereIn('id', $operationalAccountIds)
            ->pluck('id')
            ->map(fn ($accountId): int => (int) $accountId)
            ->all();

        if (count($resolvedAccountIds) !== count($operationalAccountIds)) {
            throw new RuntimeException('One or more operational accounts could not be resolved for this company.');
        }
    }

    private function requiredString(array $data, string $key): string
    {
        $value = $data[$key] ?? null;

        if (! is_string($value) || trim($value) === '') {
            throw new InvalidArgumentException("The {$key} value is required.");
        }

        return trim($value);
    }

    private function sourceTypeAliases(mixed $aliases, string $canonical): array
    {
        if (! is_array($aliases)) {
            throw new InvalidArgumentException('source_type_aliases must be an array.');
        }

        $types = [$canonical];

        foreach ($aliases as $alias) {
            if (! is_string($alias) || trim($alias) === '') {
                throw new InvalidArgumentException('Every source_type alias must be a non-empty string.');
            }

            $types[] = trim($alias);
        }

        return array_values(array_unique($types));
    }

    private function positiveInteger(mixed $value, string $key): int
    {
        if (filter_var($value, FILTER_VALIDATE_INT) === false || (int) $value < 1) {
            throw new InvalidArgumentException("The {$key} value must be a positive integer.");
        }

        return (int) $value;
    }

    private function nullablePositiveInteger(mixed $value, string $key): ?int
    {
        if ($value === null) {
            return null;
        }

        return $this->positiveInteger($value, $key);
    }

    private function entryDate(mixed $value): string
    {
        if (! is_string($value) || trim($value) === '') {
            throw new InvalidArgumentException('The entry_date value is required.');
        }

        $value = trim($value);
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        $errors = DateTimeImmutable::getLastErrors();

        if (
            ! $date ||
            ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) ||
            $date->format('Y-m-d') !== $value
        ) {
            throw new InvalidArgumentException('The entry_date value must be a valid Y-m-d date.');
        }

        return $date->format('Y-m-d');
    }

    private function normalizeAmount(mixed $value, string $field): string
    {
        if (is_int($value)) {
            $value = (string) $value;
        }

        if (! is_string($value)) {
            throw new InvalidArgumentException("The {$field} value must be a non-negative decimal amount.");
        }

        $value = trim($value);

        if (str_starts_with($value, '-')) {
            throw new InvalidArgumentException("The {$field} value cannot be negative.");
        }

        if (! preg_match('/^\d+(?:\.\d{1,4})?$/', $value)) {
            throw new InvalidArgumentException("The {$field} value must have no more than four decimal places.");
        }

        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');

        return (ltrim($whole, '0') ?: '0') . '.' . str_pad($fraction, 4, '0');
    }

    private function isZero(string $amount): bool
    {
        return $this->scaledAmount($amount) === '0';
    }

    private function addAmounts(string $left, string $right): string
    {
        $sum = $this->addUnsignedIntegers(
            $this->scaledAmount($left),
            $this->scaledAmount($right)
        );

        return $this->decimalAmount($sum);
    }

    private function scaledAmount(string $amount): string
    {
        [$whole, $fraction] = explode('.', $amount, 2);

        return ltrim($whole . $fraction, '0') ?: '0';
    }

    private function decimalAmount(string $scaledAmount): string
    {
        $scaledAmount = str_pad($scaledAmount, 5, '0', STR_PAD_LEFT);

        return substr($scaledAmount, 0, -4) . '.' . substr($scaledAmount, -4);
    }

    private function addUnsignedIntegers(string $left, string $right): string
    {
        $carry = 0;
        $result = '';
        $leftIndex = strlen($left) - 1;
        $rightIndex = strlen($right) - 1;

        while ($leftIndex >= 0 || $rightIndex >= 0 || $carry > 0) {
            $leftDigit = $leftIndex >= 0 ? (int) $left[$leftIndex--] : 0;
            $rightDigit = $rightIndex >= 0 ? (int) $right[$rightIndex--] : 0;
            $sum = $leftDigit + $rightDigit + $carry;

            $result = ($sum % 10) . $result;
            $carry = intdiv($sum, 10);
        }

        return ltrim($result, '0') ?: '0';
    }
}
