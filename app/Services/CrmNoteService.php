<?php

namespace App\Services;

use App\Models\CrmAttachment;
use App\Models\CrmConfiguration;
use App\Models\CrmNote;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CrmNoteService
{
    public function __construct(
        private CrmConfigurationService $configurationService,
        private CrmStatusHistoryService $historyService
    ) {
    }

    public function create(int $companyId, string $entityType, int $entityId, string $noteText): CrmNote
    {
        return DB::transaction(function () use ($companyId, $entityType, $entityId, $noteText) {
            $activeKey = $this->configurationService->requireKey(
                $companyId,
                CrmConfiguration::TYPE_NOTE_STATUS,
                'active'
            );

            $note = CrmNote::create([
                'company_id' => $companyId,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'note' => trim($noteText),
                'status' => $activeKey,
                'created_by' => auth()->id(),
            ]);

            $this->historyService->record(
                $companyId,
                $entityType,
                $entityId,
                'Note Added',
                null,
                (string) $note->id,
                Str::limit(trim($noteText), 250)
            );

            return $note;
        });
    }

    public function update(CrmNote $note, string $noteText): CrmNote
    {
        if ($note->archived_at) {
            throw new \Exception('Archived notes cannot be edited.');
        }

        return DB::transaction(function () use ($note, $noteText) {
            $note->update([
                'note' => trim($noteText),
                'updated_by' => auth()->id(),
            ]);

            $this->historyService->record(
                (int) $note->company_id,
                $note->entity_type,
                (int) $note->entity_id,
                'Note Updated',
                null,
                (string) $note->id,
                Str::limit(trim($noteText), 250)
            );

            return $note->fresh();
        });
    }

    public function archive(CrmNote $note, string $reason): void
    {
        DB::transaction(function () use ($note, $reason) {
            $companyId = (int) $note->company_id;
            $archivedKey = $this->configurationService->requireKey(
                $companyId,
                CrmConfiguration::TYPE_NOTE_STATUS,
                'archived'
            );

            $note->update([
                'status' => $archivedKey,
                'archived_by' => auth()->id(),
                'archived_at' => now(),
                'archive_reason' => trim($reason),
                'updated_by' => auth()->id(),
            ]);

            $this->historyService->record(
                $companyId,
                $note->entity_type,
                (int) $note->entity_id,
                'Note Archived',
                'active',
                $archivedKey,
                trim($reason)
            );
        });
    }
}
