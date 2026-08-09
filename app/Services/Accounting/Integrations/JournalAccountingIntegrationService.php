<?php

namespace App\Services\Accounting\Integrations;

use App\Models\AccountingEntry;
use App\Models\Journal;
use App\Services\Accounting\AccountingPostingService;
use App\Services\Accounting\Builders\JournalAccountingDataBuilder;
use App\Services\Accounting\Profiles\JournalPostingProfile;
use RuntimeException;

class JournalAccountingIntegrationService
{
    public function __construct(
        private readonly JournalAccountingDataBuilder $builder,
        private readonly JournalPostingProfile $profile,
        private readonly AccountingPostingService $postingService,
    ) {
    }

    public function postJournal(Journal $journal, int $companyId, int $actorId): AccountingEntry
    {
        return $this->postingService->post(
            $this->profile->build($this->builder->build($journal, $companyId, $actorId))
        );
    }

    public function reverseJournal(Journal $original, Journal $reversal, int $actorId): AccountingEntry
    {
        $entries = AccountingEntry::where('company_id', $original->company_id)
            ->where('source_type', 'manual_journal')
            ->where('source_id', $original->id)
            ->where('source_event', 'posted')
            ->lockForUpdate()
            ->get();

        if ($entries->count() !== 1 || $entries->first()->source_key !== $original->source_key) {
            throw new RuntimeException('Exactly one source-linked original Accounting Entry is required for reversal.');
        }

        return $this->postingService->reverseBySource([
            'company_id' => $original->company_id,
            'financial_year_id' => $original->financial_year_id,
            'entry_date' => $original->journal_date->format('Y-m-d'),
            'original_source_key' => $original->source_key,
            'reversal_source_key' => $reversal->source_key,
            'source_module' => 'journal',
            'source_type' => 'manual_journal_reversal',
            'original_source_types' => ['manual_journal'],
            'source_id' => $original->id,
            'source_event' => 'reversed',
            'original_source_event' => 'posted',
            'reference_number' => $reversal->journal_no,
            'description' => $reversal->remarks,
            'posted_by' => $actorId,
        ]);
    }
}
