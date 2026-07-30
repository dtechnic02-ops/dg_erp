<?php

namespace App\Services\Accounting\Integrations;

use App\Models\AccountingEntry;
use App\Models\Income;
use App\Services\Accounting\AccountingPostingService;
use App\Services\Accounting\Builders\IncomeAccountingDataBuilder;
use App\Services\Accounting\Profiles\IncomePostingProfile;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class IncomeAccountingIntegrationService
{
    public function __construct(
        private readonly IncomeAccountingDataBuilder $builder,
        private readonly IncomePostingProfile $profile,
        private readonly AccountingPostingService $postingService
    ) {
    }

    public function postIncome(Income $income): void
    {
        if (! $income->exists) {
            throw new InvalidArgumentException('The income must be saved before accounting can be posted.');
        }

        $this->postingService->post(
            $this->profile->build($this->builder->build($income))
        );
    }

    public function reverseIncome(Income $income, string $date, ?int $postedBy = null): void
    {
        if (! $income->exists) {
            throw new InvalidArgumentException('The income must be saved before accounting can be reversed.');
        }

        $current = $this->currentPosting($income);

        $this->postingService->reverseBySource([
            'company_id' => $income->company_id,
            'entry_date' => $date,
            'original_source_key' => $current->source_key,
            'original_source_event' => $current->source_event,
            'original_source_types' => [Income::class],
            'reversal_source_key' => 'income_cancel:' . $income->id . ':cancelled',
            'source_module' => 'income',
            'source_type' => 'income',
            'source_id' => $income->id,
            'source_event' => 'cancelled',
            'reference_number' => $income->income_no,
            'description' => 'Income cancellation - ' . $income->income_no,
            'posted_by' => $postedBy,
        ]);
    }

    public function syncIncomeEdit(Income $income, ?int $postedBy = null): void
    {
        if (! $income->exists) {
            throw new InvalidArgumentException('The income must be saved before accounting can be synchronized.');
        }

        DB::transaction(function () use ($income, $postedBy): void {
            $posting = $this->profile->build($this->builder->build($income));
            $current = $this->currentPosting($income, true);

            if ($this->matchesPosting($current, $posting)) {
                throw new RuntimeException('The current income accounting entry already represents the persisted income.');
            }

            $sequence = AccountingEntry::query()
                ->forCompany((int) $income->company_id)
                ->where('source_module', 'income')
                ->where('source_id', $income->id)
                ->where('source_event', 'like', 'edited_%')
                ->where('source_event', 'not like', 'edited_reversed_%')
                ->count() + 1;

            $this->postingService->reverseBySource([
                'company_id' => $income->company_id,
                'entry_date' => $posting['entry_date'],
                'original_source_key' => $current->source_key,
                'original_source_event' => $current->source_event,
                'original_source_types' => [Income::class],
                'reversal_source_key' => 'income_edit_reversal:' . $income->id . ':' . $sequence,
                'source_module' => 'income',
                'source_type' => 'income',
                'source_id' => $income->id,
                'source_event' => 'edited_reversed_' . $sequence,
                'reference_number' => $income->income_no,
                'description' => 'Income edit reversal - ' . $income->income_no,
                'posted_by' => $postedBy,
            ]);

            $posting['source_event'] = 'edited_' . $sequence;
            $posting['source_key'] = 'income:' . $income->id . ':edited:' . $sequence;
            $posting['description'] = 'Income edit - ' . $income->income_no;
            $posting['posted_by'] = $postedBy;

            $this->postingService->post($posting);
        });
    }

    private function currentPosting(Income $income, bool $lock = false): AccountingEntry
    {
        $query = AccountingEntry::query()
            ->forCompany((int) $income->company_id)
            ->where('source_module', 'income')
            ->whereIn('source_type', ['income', Income::class])
            ->where('source_id', $income->id)
            ->where('status', 'posted')
            ->whereNull('reversal_of_id')
            ->with('lines.chartAccount');

        if ($lock) {
            $query->lockForUpdate();
        }

        $entries = $query->get();

        if ($entries->count() !== 1) {
            throw new RuntimeException('The income must have exactly one current posted accounting entry.');
        }

        return $entries->first();
    }

    private function matchesPosting(AccountingEntry $entry, array $posting): bool
    {
        if ($entry->lines->count() !== count($posting['lines'])) {
            return false;
        }

        foreach ($posting['lines'] as $index => $expected) {
            $line = $entry->lines->sortBy('line_number')->values()->get($index);
            $expectedChartAccountId = $expected['chart_account_id'] ?? null;
            $expectedSystemCode = $expected['chart_account_system_code'] ?? null;

            if (
                ! $line
                || ($expectedChartAccountId !== null && $line->chart_account_id !== $expectedChartAccountId)
                || ($expectedSystemCode !== null && $line->chartAccount?->system_code !== $expectedSystemCode)
                || $line->operational_account_id !== $expected['operational_account_id']
                || $line->debit !== $expected['debit']
                || $line->credit !== $expected['credit']
                || $line->subledger_type !== $expected['subledger_type']
                || $line->subledger_id !== $expected['subledger_id']
            ) {
                return false;
            }
        }

        return true;
    }
}
