<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesCompanyPermission;
use App\Http\Controllers\Concerns\AuthorizesSubscriptionModule;
use Illuminate\Routing\Controllers\HasMiddleware;
use App\Models\CrmConfiguration;
use App\Models\CrmLead;
use App\Models\CrmMeeting;
use App\Models\CrmOpportunity;
use App\Models\EmployeeAccount;
use App\Services\CrmActivityService;
use App\Services\CrmConfigurationService;
use App\Services\CrmNumberService;
use App\Services\CrmStatusHistoryService;
use App\Services\CrmWorkflowService;
use App\Services\ValidationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CrmMeetingController extends Controller implements HasMiddleware
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
        private CrmConfigurationService $configurationService,
        private CrmStatusHistoryService $historyService,
        private CrmActivityService $activityService,
        private CrmWorkflowService $workflowService
    ) {
    }

    public function index(Request $request)
    {
        $this->authorizeCompanyPermission('view_crm_meeting');

        $companyId = auth()->user()->company_id;
        $inactiveStatuses = $this->configurationService->terminalKeys($companyId, CrmConfiguration::TYPE_MEETING_STATUS);

        $query = CrmMeeting::with(['lead.customer', 'assignedEmployee'])
            ->where('company_id', $companyId);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } elseif (!$request->has('status')) {
            $query->whereNotIn('status', $inactiveStatuses);
        }

        $meetings = $query->orderByDesc('meeting_date')->paginate(20)->withQueryString();
        $statusOptions = $this->configurationService->options($companyId, CrmConfiguration::TYPE_MEETING_STATUS);

        return view('company.crm-meetings.index', compact('meetings', 'statusOptions'));
    }

    public function create()
    {
        $this->authorizeCompanyPermission('create_crm_meeting');

        $companyId = auth()->user()->company_id;
        $employees = EmployeeAccount::where('company_id', $companyId)->active()->orderBy('first_name')->get();
        $leads = CrmLead::with('customer')->where('company_id', $companyId)->orderByDesc('lead_date')->get();
        $opportunities = CrmOpportunity::where('company_id', $companyId)->orderByDesc('id')->get();
        $statusOptions = $this->configurationService->options($companyId, CrmConfiguration::TYPE_MEETING_STATUS);
        $defaultStatus = $this->configurationService->requireKey($companyId, CrmConfiguration::TYPE_MEETING_STATUS, 'scheduled');

        return view('company.crm-meetings.create', compact('employees', 'leads', 'opportunities', 'statusOptions', 'defaultStatus'));
    }

    public function store(Request $request)
    {
        $this->authorizeCompanyPermission('create_crm_meeting');

        $companyId = auth()->user()->company_id;
        $statusKeys = $this->configurationService->keys($companyId, CrmConfiguration::TYPE_MEETING_STATUS);

        $request->validate([
            'crm_lead_id' => ['nullable', ValidationService::existsForCompany('crm_leads', $companyId)],
            'crm_opportunity_id' => ['nullable', ValidationService::existsForCompany('crm_opportunities', $companyId)],
            'meeting_date' => ValidationService::requiredDate(),
            'meeting_time' => 'nullable',
            'assigned_employee_id' => ['required', ValidationService::existsForCompany('employee_accounts', $companyId)],
            'location' => ValidationService::string(),
            'status' => ['required', Rule::in($statusKeys)],
            'remarks' => ValidationService::text(),
        ]);

        if (!$request->crm_lead_id && !$request->crm_opportunity_id) {
            return back()->withInput()->with('error', 'Customer Relationship or Opportunity reference is required.');
        }

        try {
            DB::transaction(function () use ($request, $companyId) {
                $activeFy = $this->activityService->validateBusinessDate(
                    $companyId,
                    $request->meeting_date,
                    'Meeting date must fall within the active financial year.'
                );

                $meeting = CrmMeeting::create([
                    'company_id' => $companyId,
                    'financial_year_id' => $activeFy->id,
                    'activity_no' => CrmNumberService::generateActivityNo($companyId, CrmMeeting::class),
                    'crm_lead_id' => $request->crm_lead_id,
                    'crm_opportunity_id' => $request->crm_opportunity_id,
                    'meeting_date' => $request->meeting_date,
                    'meeting_time' => $request->meeting_time,
                    'assigned_employee_id' => $request->assigned_employee_id,
                    'location' => $request->location,
                    'status' => $request->status,
                    'remarks' => $request->remarks,
                    'created_by' => auth()->id(),
                ]);

                $this->historyService->record($companyId, 'meeting', (int) $meeting->id, 'Meeting Scheduled', null, $meeting->status, $meeting->remarks);
            });

            return redirect()->route('company.crm-meetings.index')->with('success', 'Meeting scheduled successfully.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function edit($id)
    {
        $this->authorizeCompanyPermission('edit_crm_meeting');

        $companyId = auth()->user()->company_id;
        $terminalKeys = $this->configurationService->terminalKeys($companyId, CrmConfiguration::TYPE_MEETING_STATUS);
        $meeting = CrmMeeting::where('company_id', $companyId)->findOrFail($id);

        if ($meeting->isTerminal($terminalKeys)) {
            return redirect()->route('company.crm-meetings.index')->with('error', 'This meeting cannot be edited.');
        }

        $employees = EmployeeAccount::where('company_id', $companyId)->active()->orderBy('first_name')->get();
        $statusOptions = $this->configurationService->options($companyId, CrmConfiguration::TYPE_MEETING_STATUS);

        return view('company.crm-meetings.edit', compact('meeting', 'employees', 'statusOptions'));
    }

    public function update(Request $request, $id)
    {
        $this->authorizeCompanyPermission('edit_crm_meeting');

        $companyId = auth()->user()->company_id;
        $terminalKeys = $this->configurationService->terminalKeys($companyId, CrmConfiguration::TYPE_MEETING_STATUS);
        $meeting = CrmMeeting::where('company_id', $companyId)->findOrFail($id);

        if ($meeting->isTerminal($terminalKeys)) {
            return back()->with('error', 'This meeting cannot be edited.');
        }

        $statusKeys = $this->configurationService->keys($companyId, CrmConfiguration::TYPE_MEETING_STATUS);

        $request->validate([
            'meeting_date' => ValidationService::requiredDate(),
            'meeting_time' => 'nullable',
            'assigned_employee_id' => ['required', ValidationService::existsForCompany('employee_accounts', $companyId)],
            'location' => ValidationService::string(),
            'status' => ['required', Rule::in($statusKeys)],
            'remarks' => ValidationService::text(),
        ]);

        try {
            DB::transaction(function () use ($request, $meeting, $companyId) {
                $activeFy = $this->activityService->validateBusinessDate($companyId, $request->meeting_date);
                $previousStatus = $meeting->status;

                $meeting->update([
                    'financial_year_id' => $activeFy->id,
                    'meeting_date' => $request->meeting_date,
                    'meeting_time' => $request->meeting_time,
                    'assigned_employee_id' => $request->assigned_employee_id,
                    'location' => $request->location,
                    'status' => $request->status,
                    'remarks' => $request->remarks,
                    'updated_by' => auth()->id(),
                ]);

                $this->historyService->record($companyId, 'meeting', (int) $meeting->id, 'Meeting Updated', null, null, 'Meeting updated.');

                if ($previousStatus !== $meeting->status) {
                    $this->historyService->record($companyId, 'meeting', (int) $meeting->id, 'Status Changed', $previousStatus, $meeting->status);
                }
            });

            return redirect()->route('company.crm-meetings.index')->with('success', 'Meeting updated successfully.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function complete($id)
    {
        $this->authorizeCompanyPermission('edit_crm_meeting');

        try {
            $companyId = auth()->user()->company_id;
            $meeting = CrmMeeting::where('company_id', $companyId)->findOrFail($id);

            $this->workflowService->applyStatus(
                $meeting,
                $companyId,
                'meeting',
                'status',
                CrmConfiguration::TYPE_MEETING_STATUS,
                'completed',
                'Meeting Completed',
                null,
                ['completed_at' => now()]
            );

            return back()->with('success', 'Meeting marked as completed.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function archive(Request $request, $id)
    {
        $this->authorizeCompanyPermission('archive_crm_meeting');

        $request->validate(['archive_reason' => ValidationService::requiredString(500)]);

        try {
            $companyId = auth()->user()->company_id;
            $meeting = CrmMeeting::where('company_id', $companyId)->findOrFail($id);

            $this->workflowService->archive(
                $meeting,
                $companyId,
                'meeting',
                'status',
                CrmConfiguration::TYPE_MEETING_STATUS,
                $request->archive_reason,
                'Meeting Archived'
            );

            return back()->with('success', 'Meeting archived successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function cancel(Request $request, $id)
    {
        $this->authorizeCompanyPermission('cancel_crm_meeting');

        $request->validate(['cancel_reason' => ValidationService::requiredString(500)]);

        try {
            $companyId = auth()->user()->company_id;
            $meeting = CrmMeeting::where('company_id', $companyId)->findOrFail($id);

            $this->workflowService->cancel(
                $meeting,
                $companyId,
                'meeting',
                'status',
                CrmConfiguration::TYPE_MEETING_STATUS,
                $request->cancel_reason,
                'Meeting Cancelled'
            );

            return back()->with('success', 'Meeting cancelled successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
