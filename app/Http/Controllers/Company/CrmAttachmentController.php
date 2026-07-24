<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesCompanyPermission;
use App\Http\Controllers\Concerns\AuthorizesSubscriptionModule;
use Illuminate\Routing\Controllers\HasMiddleware;
use App\Models\CrmAttachment;
use App\Services\CrmAttachmentService;
use App\Services\CrmEntityResolverService;
use App\Services\ValidationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class CrmAttachmentController extends Controller implements HasMiddleware
{
    use AuthorizesCompanyPermission;
    use AuthorizesSubscriptionModule;

    public static function middleware(): array
    {
        return self::subscriptionModuleMiddleware();
    }

    protected static function subscriptionModuleCode(): string
    {
        return 'crm';
    }

    public function __construct(
        private CrmAttachmentService $attachmentService,
        private CrmEntityResolverService $entityResolver
    ) {
    }

    public function store(Request $request)
    {
        $this->authorizeCompanyPermission('create_crm_attachment');

        $request->validate([
            'entity_type' => ValidationService::requiredString(50),
            'entity_id' => 'required|integer|min:1',
            'document_type' => ValidationService::string(50),
            'remarks' => ValidationService::text(),
            'file' => 'required|file|max:5120',
        ]);

        try {
            $companyId = auth()->user()->company_id;
            $this->entityResolver->resolve($companyId, $request->entity_type, (int) $request->entity_id);

            $this->attachmentService->upload(
                $companyId,
                $request->entity_type,
                (int) $request->entity_id,
                $request->file('file'),
                $request->document_type ?: 'attachment',
                $request->remarks
            );

            return back()->with('success', 'Attachment uploaded successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function download($id)
    {
        $this->authorizeCompanyPermission('view_crm_attachment');

        $companyId = auth()->user()->company_id;
        $attachment = CrmAttachment::where('company_id', $companyId)->findOrFail($id);
        $path = $this->attachmentService->absolutePath($attachment);

        if (!is_file($path)) {
            abort(404, 'Attachment file not found.');
        }

        return Response::download($path, $attachment->original_name ?: basename($path));
    }

    public function preview($id)
    {
        $this->authorizeCompanyPermission('view_crm_attachment');

        $companyId = auth()->user()->company_id;
        $attachment = CrmAttachment::where('company_id', $companyId)->findOrFail($id);
        $path = $this->attachmentService->absolutePath($attachment);

        if (!is_file($path)) {
            abort(404, 'Attachment file not found.');
        }

        return Response::file($path, [
            'Content-Type' => $attachment->mime_type ?: 'application/octet-stream',
        ]);
    }

    public function archive(Request $request, $id)
    {
        $this->authorizeCompanyPermission('archive_crm_attachment');

        $request->validate(['archive_reason' => ValidationService::requiredString(500)]);

        try {
            $companyId = auth()->user()->company_id;
            $attachment = CrmAttachment::where('company_id', $companyId)->findOrFail($id);
            $this->attachmentService->archive($attachment, $request->archive_reason);

            return back()->with('success', 'Attachment archived successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
