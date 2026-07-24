<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesCompanyPermission;
use App\Http\Controllers\Concerns\AuthorizesSubscriptionModule;
use Illuminate\Routing\Controllers\HasMiddleware;
use App\Models\CrmConfiguration;
use App\Models\CrmFollowUp;
use App\Models\CrmLead;
use App\Models\CrmOpportunity;
use App\Models\CrmStatusHistory;
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

class CrmFollowUpController extends Controller implements HasMiddleware
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
        $this->authorizeCompanyPermission('view_crm_follow_up');

        $companyId = auth()->user()->company_id;
        $inactiveStatuses = $this->configurationService->terminalKeys($companyId, CrmConfiguration::TYPE_FOLLOW_UP_STATUS);

        $query = CrmFollowUp::with(['lead.customer', 'opportunity', 'assignedEmployee'])
            ->where('company_id', $companyId);

        if ($request->filled('crm_lead_id')) {
            $query->where('crm_lead_id', $request->crm_lead_id);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('follow_up_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('follow_up_date', '<=', $request->end_date);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } elseif (!$request->has('status')) {
            $query->whereNotIn('status', $inactiveStatuses);
        }

        $followUps = $query->orderByDesc('follow_up_date')->paginate(20)->withQueryString();
        $statusOptions = $this->configurationService->options($companyId, CrmConfiguration::TYPE_FOLLOW_UP_STATUS);

        return view('company.crm-follow-ups.index', compact('followUps', 'statusOptions'));
    }

    public function create(Request $request)
    {
        $this->authorizeCompanyPermission('create_crm_follow_up');

        $companyId = auth()->user()->company_id;
        $employees = EmployeeAccount::where('company_id', $companyId)->active()->orderBy('first_name')->get();
        $leads = CrmLead::with('customer')->where('company_id', $companyId)->orderByDesc('lead_date')->get();
        $opportunities = CrmOpportunity::where('company_id', $companyId)->orderByDesc('id')->get();
        $priorityOptions = $this->configurationService->options($companyId, CrmConfiguration::TYPE_PRIORITY);
        $statusOptions = $this->configurationService->options($companyId, CrmConfiguration::TYPE_FOLLOW_UP_STATUS);
        $defaultStatus = $this->configurationService->requireKey($companyId, CrmConfiguration::TYPE_FOLLOW_UP_STATUS, 'pending');

        return view('company.crm-follow-ups.create', compact(
            'employees',
            'leads',
            'opportunities',
            'priorityOptions',
            'statusOptions',
            'defaultStatus'
        ));
    }

    public function store(Request $request)
    {
        $this->authorizeCompanyPermission('create_crm_follow_up');

        $companyId = auth()->user()->company_id;
        $priorityKeys = $this->configurationService->keys($companyId, CrmConfiguration::TYPE_PRIORITY);
        $statusKeys = $this->configurationService->keys($companyId, CrmConfiguration::TYPE_FOLLOW_UP_STATUS);

        $request->validate([
            'crm_lead_id' => ['nullable', ValidationService::existsForCompany('crm_leads', $companyId)],
            'crm_opportunity_id' => ['nullable', ValidationService::existsForCompany('crm_opportunities', $companyId)],
            'follow_up_date' => ValidationService::requiredDate(),
            'next_follow_up_date' => ValidationService::date(),
            'assigned_employee_id' => ['required', ValidationService::existsForCompany('employee_accounts', $companyId)],
            'priority' => ['required', Rule::in($priorityKeys)],
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
                    $request->follow_up_date,
                    'Follow-up date must fall within the active financial year.'
                );

                if ($request->next_follow_up_date) {
                    $this->configurationService->assertDateWithinActiveFinancialYear(
                        $activeFy,
                        $request->next_follow_up_date
                    );
                }

                $followUp = CrmFollowUp::create([
                    'company_id' => $companyId,
                    'financial_year_id' => $activeFy->id,
                    'activity_no' => CrmNumberService::generateActivityNo($companyId, CrmFollowUp::class),
                    'crm_lead_id' => $request->crm_lead_id,
                    'crm_opportunity_id' => $request->crm_opportunity_id,
                    'follow_up_date' => $request->follow_up_date,
                    'next_follow_up_date' => $request->next_follow_up_date,
                    'assigned_employee_id' => $request->assigned_employee_id,
                    'priority' => $request->priority,
                    'status' => $request->status,
                    'remarks' => $request->remarks,
                    'created_by' => auth()->id(),
                ]);

                $this->historyService->record($companyId, 'follow_up', (int) $followUp->id, 'Follow-up Added', null, $followUp->status, $followUp->remarks);
            });

            return redirect()->route('company.crm-follow-ups.index')->with('success', 'Follow-up created successfully.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function edit($id)
    {
        $this->authorizeCompanyPermission('edit_crm_follow_up');

        $companyId = auth()->user()->company_id;
        $terminalKeys = $this->configurationService->terminalKeys($companyId, CrmConfiguration::TYPE_FOLLOW_UP_STATUS);
        $followUp = CrmFollowUp::where('company_id', $companyId)->findOrFail($id);

        if ($followUp->isTerminal($terminalKeys)) {
            return redirect()->route('company.crm-follow-ups.index')->with('error', 'This follow-up cannot be edited.');
        }

        $employees = EmployeeAccount::where('company_id', $companyId)->active()->orderBy('first_name')->get();
        $priorityOptions = $this->configurationService->options($companyId, CrmConfiguration::TYPE_PRIORITY);
        $statusOptions = $this->configurationService->options($companyId, CrmConfiguration::TYPE_FOLLOW_UP_STATUS);

        return view('company.crm-follow-ups.edit', compact('followUp', 'employees', 'priorityOptions', 'statusOptions'));
    }

    public function update(Request $request, $id)
    {
        $this->authorizeCompanyPermission('edit_crm_follow_up');

        $companyId = auth()->user()->company_id;
        $terminalKeys = $this->configurationService->terminalKeys($companyId, CrmConfiguration::TYPE_FOLLOW_UP_STATUS);
        $followUp = CrmFollowUp::where('company_id', $companyId)->findOrFail($id);

        if ($followUp->isTerminal($terminalKeys)) {
            return back()->with('error', 'This follow-up cannot be edited.');
        }

        $priorityKeys = $this->configurationService->keys($companyId, CrmConfiguration::TYPE_PRIORITY);
        $statusKeys = $this->configurationService->keys($companyId, CrmConfiguration::TYPE_FOLLOW_UP_STATUS);

        $request->validate([
            'follow_up_date' => ValidationService::requiredDate(),
            'next_follow_up_date' => ValidationService::date(),
            'assigned_employee_id' => ['required', ValidationService::existsForCompany('employee_accounts', $companyId)],
            'priority' => ['required', Rule::in($priorityKeys)],
            'status' => ['required', Rule::in($statusKeys)],
            'remarks' => ValidationService::text(),
        ]);

        try {
            DB::transaction(function () use ($request, $followUp, $companyId) {
                $activeFy = $this->activityService->validateBusinessDate($companyId, $request->follow_up_date);

                if ($request->next_follow_up_date) {
                    $this->configurationService->assertDateWithinActiveFinancialYear($activeFy, $request->next_follow_up_date);
                }

                $previousStatus = $followUp->status;

                $followUp->update([
                    'financial_year_id' => $activeFy->id,
                    'follow_up_date' => $request->follow_up_date,
                    'next_follow_up_date' => $request->next_follow_up_date,
                    'assigned_employee_id' => $request->assigned_employee_id,
                    'priority' => $request->priority,
                    'status' => $request->status,
                    'remarks' => $request->remarks,
                    'updated_by' => auth()->id(),
                ]);

                $this->historyService->record($companyId, 'follow_up', (int) $followUp->id, 'Follow-up Updated', null, null, 'Follow-up updated.');

                if ($previousStatus !== $followUp->status) {
                    $this->historyService->record($companyId, 'follow_up', (int) $followUp->id, 'Status Changed', $previousStatus, $followUp->status);
                }
            });

            return redirect()->route('company.crm-follow-ups.index')->with('success', 'Follow-up updated successfully.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function archive(Request $request, $id)
    {
        $this->authorizeCompanyPermission('archive_crm_follow_up');

        $request->validate(['archive_reason' => ValidationService::requiredString(500)]);

        try {
            $companyId = auth()->user()->company_id;
            $followUp = CrmFollowUp::where('company_id', $companyId)->findOrFail($id);

            $this->workflowService->archive(
                $followUp,
                $companyId,
                'follow_up',
                'status',
                CrmConfiguration::TYPE_FOLLOW_UP_STATUS,
                $request->archive_reason,
                'Follow-up Archived'
            );

            return back()->with('success', 'Follow-up archived successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function cancel(Request $request, $id)
    {
        $this->authorizeCompanyPermission('cancel_crm_follow_up');

        $request->validate(['cancel_reason' => ValidationService::requiredString(500)]);

        try {
            $companyId = auth()->user()->company_id;
            $followUp = CrmFollowUp::where('company_id', $companyId)->findOrFail($id);

            $this->workflowService->cancel(
                $followUp,
                $companyId,
                'follow_up',
                'status',
                CrmConfiguration::TYPE_FOLLOW_UP_STATUS,
                $request->cancel_reason,
                'Follow-up Cancelled'
            );

            return back()->with('success', 'Follow-up cancelled successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
