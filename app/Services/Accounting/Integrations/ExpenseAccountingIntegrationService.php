<?php

namespace App\Services\Accounting\Integrations;

use App\Models\Expense;
use App\Models\AccountingEntry;
use App\Services\Accounting\AccountingPostingService;
use App\Services\Accounting\Builders\ExpenseAccountingDataBuilder;
use App\Services\Accounting\Profiles\ExpensePostingProfile;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class ExpenseAccountingIntegrationService
{
    public function __construct(
        private readonly ExpenseAccountingDataBuilder $builder,
        private readonly ExpensePostingProfile $profile,
        private readonly AccountingPostingService $postingService
    ) {
    }

    public function postExpense(Expense $expense): void
    {
        if (! $expense->exists) {
            throw new InvalidArgumentException('The expense must be saved before accounting can be posted.');
        }

        $this->postingService->post(
            $this->profile->build($this->builder->build($expense))
        );
    }

    public function reverseExpense(Expense $expense, string $date, ?int $postedBy = null): void
    {
        if (! $expense->exists) {
            throw new InvalidArgumentException('The expense must be saved before accounting can be reversed.');
        }

        $current = $this->currentPosting($expense);

        $this->postingService->reverseBySource([
            'company_id' => $expense->company_id,
            'entry_date' => $date,
            'original_source_key' => $current->source_key,
            'original_source_event' => $current->source_event,
            'original_source_types' => [Expense::class],
            'reversal_source_key' => 'expense_cancel:' . $expense->id . ':cancelled',
            'source_module' => 'expense',
            'source_type' => 'expense',
            'source_id' => $expense->id,
            'source_event' => 'cancelled',
            'reference_number' => $expense->expense_no,
            'description' => 'Expense cancellation - ' . $expense->expense_no,
            'posted_by' => $postedBy,
        ]);
    }

    public function syncExpenseEdit(Expense $expense, ?int $postedBy = null): void
    {
        if (! $expense->exists) {
            throw new InvalidArgumentException('The expense must be saved before accounting can be synchronized.');
        }

        DB::transaction(function () use ($expense, $postedBy): void {
            $posting = $this->profile->build($this->builder->build($expense));
            $current = $this->currentPosting($expense, true);

            if ($this->matchesPosting($current, $posting)) {
                throw new RuntimeException('The current expense accounting entry already represents the persisted expense.');
            }

            $sequence = AccountingEntry::query()
                ->forCompany((int) $expense->company_id)
                ->where('source_module', 'expense')
                ->where('source_id', $expense->id)
                ->where('source_event', 'like', 'edited_%')
                ->count() + 1;

            $this->postingService->reverseBySource([
                'company_id' => $expense->company_id,
                'entry_date' => $posting['entry_date'],
                'original_source_key' => $current->source_key,
                'original_source_event' => $current->source_event,
                'original_source_types' => [Expense::class],
                'reversal_source_key' => 'expense_edit_reversal:' . $expense->id . ':' . $sequence,
                'source_module' => 'expense',
                'source_type' => 'expense',
                'source_id' => $expense->id,
                'source_event' => 'edited_reversed_' . $sequence,
                'reference_number' => $expense->expense_no,
                'description' => 'Expense edit reversal - ' . $expense->expense_no,
                'posted_by' => $postedBy,
            ]);

            $posting['source_event'] = 'edited_' . $sequence;
            $posting['source_key'] = 'expense:' . $expense->id . ':edited:' . $sequence;
            $posting['description'] = 'Expense edit - ' . $expense->expense_no;
            $posting['posted_by'] = $postedBy;

            $this->postingService->post($posting);
        });
    }

    private function currentPosting(Expense $expense, bool $lock = false): AccountingEntry
    {
        $query = AccountingEntry::query()
            ->forCompany((int) $expense->company_id)
            ->where('source_module', 'expense')
            ->whereIn('source_type', ['expense', Expense::class])
            ->where('source_id', $expense->id)
            ->where('status', 'posted')
            ->whereNull('reversal_of_id')
            ->with('lines.chartAccount');

        if ($lock) {
            $query->lockForUpdate();
        }

        $entries = $query->get();

        if ($entries->count() !== 1) {
            throw new RuntimeException('The expense must have exactly one current posted accounting entry.');
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

            if (
                ! $line
                || $line->chartAccount?->system_code !== $expected['chart_account_system_code']
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
