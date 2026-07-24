<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesCompanyPermission;
use App\Http\Controllers\Concerns\AuthorizesSubscriptionModule;
use Illuminate\Routing\Controllers\HasMiddleware;
use App\Models\CrmNote;
use App\Services\CrmEntityResolverService;
use App\Services\CrmNoteService;
use App\Services\ValidationService;
use Illuminate\Http\Request;

class CrmNoteController extends Controller implements HasMiddleware
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
        private CrmNoteService $noteService,
        private CrmEntityResolverService $entityResolver
    ) {
    }

    public function store(Request $request)
    {
        $this->authorizeCompanyPermission('create_crm_note');

        $request->validate([
            'entity_type' => ValidationService::requiredString(50),
            'entity_id' => 'required|integer|min:1',
            'note' => ValidationService::requiredString(5000),
        ]);

        try {
            $companyId = auth()->user()->company_id;
            $this->entityResolver->resolve($companyId, $request->entity_type, (int) $request->entity_id);
            $this->noteService->create($companyId, $request->entity_type, (int) $request->entity_id, $request->note);

            return back()->with('success', 'Note added successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $this->authorizeCompanyPermission('edit_crm_note');

        $request->validate(['note' => ValidationService::requiredString(5000)]);

        try {
            $companyId = auth()->user()->company_id;
            $note = CrmNote::where('company_id', $companyId)->findOrFail($id);
            $this->noteService->update($note, $request->note);

            return back()->with('success', 'Note updated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function archive(Request $request, $id)
    {
        $this->authorizeCompanyPermission('archive_crm_note');

        $request->validate(['archive_reason' => ValidationService::requiredString(500)]);

        try {
            $companyId = auth()->user()->company_id;
            $note = CrmNote::where('company_id', $companyId)->findOrFail($id);
            $this->noteService->archive($note, $request->archive_reason);

            return back()->with('success', 'Note archived successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
