<?php

namespace App\Services;

use App\Models\CrmConfiguration;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CrmWorkflowService
{
    public function __construct(
        private CrmConfigurationService $configurationService,
        private CrmStatusHistoryService $historyService
    ) {
    }

    public function archive(
        Model $model,
        int $companyId,
        string $entityType,
        string $statusColumn,
        string $configType,
        string $reason,
        string $event = 'Record Archived'
    ): void {
        DB::transaction(function () use ($model, $companyId, $entityType, $statusColumn, $configType, $reason, $event) {
            $model->refresh();
            $archivedKey = $this->configurationService->requireKey($companyId, $configType, 'archived');
            $previous = $model->{$statusColumn};

            $model->update(array_merge([
                $statusColumn => $archivedKey,
                'archived_by' => auth()->id(),
                'archived_at' => now(),
                'archive_reason' => trim($reason),
                'updated_by' => auth()->id(),
            ], $this->updatedByPayload($model)));

            $this->historyService->record(
                $companyId,
                $entityType,
                (int) $model->id,
                $event,
                $previous,
                $archivedKey,
                trim($reason)
            );
        });
    }

    public function cancel(
        Model $model,
        int $companyId,
        string $entityType,
        string $statusColumn,
        string $configType,
        string $reason,
        string $event = 'Record Cancelled'
    ): void {
        DB::transaction(function () use ($model, $companyId, $entityType, $statusColumn, $configType, $reason, $event) {
            $model->refresh();
            $cancelledKey = $this->configurationService->requireKey($companyId, $configType, 'cancelled');
            $previous = $model->{$statusColumn};

            $model->update(array_merge([
                $statusColumn => $cancelledKey,
                'cancelled_by' => auth()->id(),
                'cancelled_at' => now(),
                'cancel_reason' => trim($reason),
                'updated_by' => auth()->id(),
            ], $this->updatedByPayload($model)));

            $this->historyService->record(
                $companyId,
                $entityType,
                (int) $model->id,
                $event,
                $previous,
                $cancelledKey,
                trim($reason)
            );
        });
    }

    public function applyStatus(
        Model $model,
        int $companyId,
        string $entityType,
        string $statusColumn,
        string $configType,
        string $targetKey,
        string $event,
        ?string $remarks = null,
        array $extra = []
    ): void {
        DB::transaction(function () use ($model, $companyId, $entityType, $statusColumn, $configType, $targetKey, $event, $remarks, $extra) {
            $model->refresh();
            $resolvedKey = $this->configurationService->requireKey($companyId, $configType, $targetKey);
            $previous = $model->{$statusColumn};

            $payload = array_merge([
                $statusColumn => $resolvedKey,
                'updated_by' => auth()->id(),
            ], $extra, $this->updatedByPayload($model));

            $model->update($payload);

            $this->historyService->record(
                $companyId,
                $entityType,
                (int) $model->id,
                $event,
                $previous,
                $resolvedKey,
                $remarks
            );
        });
    }

    public function guardEditable(Model $model, int $companyId, string $configType, string $statusColumn, string $message = 'This record cannot be modified.'): void
    {
        $terminalKeys = $this->configurationService->terminalKeys($companyId, $configType);

        if (in_array($model->{$statusColumn}, $terminalKeys, true)) {
            throw new \Exception($message);
        }

        if ($model->archived_at || $model->cancelled_at) {
            throw new \Exception($message);
        }
    }

    public function inactiveFilterValues(int $companyId, string $configType): array
    {
        return $this->configurationService->terminalKeys($companyId, $configType);
    }

    public function pendingTaskStatuses(int $companyId): array
    {
        $terminal = $this->configurationService->terminalKeys($companyId, CrmConfiguration::TYPE_TASK_STATUS);

        return array_values(array_diff(
            $this->configurationService->keys($companyId, CrmConfiguration::TYPE_TASK_STATUS),
            $terminal
        ));
    }

    private function updatedByPayload(Model $model): array
    {
        return in_array('updated_by', $model->getFillable(), true) ? [] : [];
    }
}
