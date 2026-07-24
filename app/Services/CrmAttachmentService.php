<?php

namespace App\Services;

use App\Models\CrmAttachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CrmAttachmentService
{
    /** @var array<string> */
    private const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
    ];

    private const MAX_FILE_SIZE = 5242880; // 5 MB

    public function __construct(
        private CrmStatusHistoryService $historyService
    ) {
    }

    public function upload(
        int $companyId,
        string $entityType,
        int $entityId,
        UploadedFile $file,
        string $documentType = 'attachment',
        ?string $remarks = null
    ): CrmAttachment {
        $this->validateFile($file);

        return DB::transaction(function () use ($companyId, $entityType, $entityId, $file, $documentType, $remarks) {
            $directory = $this->storageDirectory($companyId, $entityType, $entityId);
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            $storedName = $documentType . '_' . time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
            $file->move($directory, $storedName);

            $relativePath = $this->relativePath($companyId, $entityType, $entityId, $storedName);

            $attachment = CrmAttachment::create([
                'company_id' => $companyId,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'document_type' => $documentType,
                'file_path' => $relativePath,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
                'remarks' => $remarks,
                'created_by' => auth()->id(),
            ]);

            $this->historyService->record(
                $companyId,
                $entityType,
                $entityId,
                'Attachment Uploaded',
                null,
                $attachment->original_name,
                $documentType
            );

            return $attachment;
        });
    }

    public function archive(CrmAttachment $attachment, string $reason): void
    {
        DB::transaction(function () use ($attachment, $reason) {
            $attachment->update([
                'is_archived' => true,
                'archived_by' => auth()->id(),
                'archived_at' => now(),
                'archive_reason' => trim($reason),
            ]);

            $this->historyService->record(
                (int) $attachment->company_id,
                $attachment->entity_type,
                (int) $attachment->entity_id,
                'Attachment Archived',
                $attachment->original_name,
                'archived',
                trim($reason)
            );
        });
    }

    public function absolutePath(CrmAttachment $attachment): string
    {
        return storage_path('app/' . ltrim($attachment->file_path, '/'));
    }

    public function validateFile(UploadedFile $file): void
    {
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw new \Exception('Attachment exceeds the maximum allowed size of 5 MB.');
        }

        $mime = $file->getClientMimeType() ?: $file->getMimeType();

        if (!in_array($mime, self::ALLOWED_MIME_TYPES, true)) {
            throw new \Exception('This file type is not allowed for CRM attachments.');
        }
    }

    private function storageDirectory(int $companyId, string $entityType, int $entityId): string
    {
        return storage_path('app/crm/' . $companyId . '/' . $entityType . '/' . $entityId);
    }

    private function relativePath(int $companyId, string $entityType, int $entityId, string $filename): string
    {
        return 'crm/' . $companyId . '/' . $entityType . '/' . $entityId . '/' . $filename;
    }
}
