<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesCompanyPermission;
use App\Http\Controllers\Concerns\AuthorizesSubscriptionModule;
use Illuminate\Routing\Controllers\HasMiddleware;
use App\Models\CrmConfiguration;
use App\Models\CrmLead;
use App\Models\CrmOpportunity;
use App\Models\CrmTask;
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

class CrmTaskController extends Controller implements HasMiddleware
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
        $this->authorizeCompanyPermission('view_crm_task');

        $companyId = auth()->user()->company_id;
        $pendingStatuses = $this->workflowService->pendingTaskStatuses($companyId);

        $query = CrmTask::with(['lead.customer', 'assignedEmployee'])
            ->where('company_id', $companyId);

        if ($request->filled('task_status')) {
            $query->where('task_status', $request->task_status);
        } elseif (!$request->has('task_status')) {
            $query->whereIn('task_status', $pendingStatuses);
        }

        $tasks = $query->orderBy('due_date')->paginate(20)->withQueryString();
        $statusOptions = $this->configurationService->options($companyId, CrmConfiguration::TYPE_TASK_STATUS);
        $typeOptions = $this->configurationService->options($companyId, CrmConfiguration::TYPE_TASK_TYPE);

        return view('company.crm-tasks.index', compact('tasks', 'statusOptions', 'typeOptions'));
    }

    public function create()
    {
        $this->authorizeCompanyPermission('create_crm_task');

        $companyId = auth()->user()->company_id;
        $employees = EmployeeAccount::where('company_id', $companyId)->active()->orderBy('first_name')->get();
        $leads = CrmLead::with('customer')->where('company_id', $companyId)->orderByDesc('lead_date')->get();
        $opportunities = CrmOpportunity::where('company_id', $companyId)->orderByDesc('id')->get();
        $statusOptions = $this->configurationService->options($companyId, CrmConfiguration::TYPE_TASK_STATUS);
        $typeOptions = $this->configurationService->options($companyId, CrmConfiguration::TYPE_TASK_TYPE);
        $priorityOptions = $this->configurationService->options($companyId, CrmConfiguration::TYPE_PRIORITY);
        $defaultStatus = $this->configurationService->requireKey($companyId, CrmConfiguration::TYPE_TASK_STATUS, 'pending');

        return view('company.crm-tasks.create', compact(
            'employees',
            'leads',
            'opportunities',
            'statusOptions',
            'typeOptions',
            'priorityOptions',
            'defaultStatus'
        ));
    }

    public function store(Request $request)
    {
        $this->authorizeCompanyPermission('create_crm_task');

        $companyId = auth()->user()->company_id;
        $typeKeys = $this->configurationService->keys($companyId, CrmConfiguration::TYPE_TASK_TYPE);
        $statusKeys = $this->configurationService->keys($companyId, CrmConfiguration::TYPE_TASK_STATUS);
        $priorityKeys = $this->configurationService->keys($companyId, CrmConfiguration::TYPE_PRIORITY);

        $request->validate([
            'crm_lead_id' => ['nullable', ValidationService::existsForCompany('crm_leads', $companyId)],
            'crm_opportunity_id' => ['nullable', ValidationService::existsForCompany('crm_opportunities', $companyId)],
            'task_type' => ['required', Rule::in($typeKeys)],
            'task_status' => ['required', Rule::in($statusKeys)],
            'priority' => ['required', Rule::in($priorityKeys)],
            'due_date' => ValidationService::requiredDate(),
            'assigned_employee_id' => ['required', ValidationService::existsForCompany('employee_accounts', $companyId)],
            'remarks' => ValidationService::text(),
        ]);

        if (!$request->crm_lead_id && !$request->crm_opportunity_id) {
            return back()->withInput()->with('error', 'Customer Relationship or Opportunity reference is required.');
        }

        try {
            DB::transaction(function () use ($request, $companyId) {
                $activeFy = $this->activityService->validateBusinessDate(
                    $companyId,
                    $request->due_date,
                    'Task due date must fall within the active financial year.'
                );

                $task = CrmTask::create([
                    'company_id' => $companyId,
                    'financial_year_id' => $activeFy->id,
                    'activity_no' => CrmNumberService::generateActivityNo($companyId, CrmTask::class),
                    'crm_lead_id' => $request->crm_lead_id,
                    'crm_opportunity_id' => $request->crm_opportunity_id,
                    'task_type' => $request->task_type,
                    'task_status' => $request->task_status,
                    'priority' => $request->priority,
                    'due_date' => $request->due_date,
                    'assigned_employee_id' => $request->assigned_employee_id,
                    'remarks' => $request->remarks,
                    'created_by' => auth()->id(),
                ]);

                $this->historyService->record($companyId, 'task', (int) $task->id, 'Task Created', null, $task->task_status, $task->remarks);
            });

            return redirect()->route('company.crm-tasks.index')->with('success', 'Task created successfully.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function edit($id)
    {
        $this->authorizeCompanyPermission('edit_crm_task');

        $companyId = auth()->user()->company_id;
        $terminalKeys = $this->configurationService->terminalKeys($companyId, CrmConfiguration::TYPE_TASK_STATUS);
        $task = CrmTask::where('company_id', $companyId)->findOrFail($id);

        if ($task->isTerminal($terminalKeys)) {
            return redirect()->route('company.crm-tasks.index')->with('error', 'This task cannot be edited.');
        }

        $employees = EmployeeAccount::where('company_id', $companyId)->active()->orderBy('first_name')->get();
        $statusOptions = $this->configurationService->options($companyId, CrmConfiguration::TYPE_TASK_STATUS);
        $typeOptions = $this->configurationService->options($companyId, CrmConfiguration::TYPE_TASK_TYPE);
        $priorityOptions = $this->configurationService->options($companyId, CrmConfiguration::TYPE_PRIORITY);

        return view('company.crm-tasks.edit', compact('task', 'employees', 'statusOptions', 'typeOptions', 'priorityOptions'));
    }

    public function update(Request $request, $id)
    {
        $this->authorizeCompanyPermission('edit_crm_task');

        $companyId = auth()->user()->company_id;
        $terminalKeys = $this->configurationService->terminalKeys($companyId, CrmConfiguration::TYPE_TASK_STATUS);
        $task = CrmTask::where('company_id', $companyId)->findOrFail($id);

        if ($task->isTerminal($terminalKeys)) {
            return back()->with('error', 'This task cannot be edited.');
        }

        $typeKeys = $this->configurationService->keys($companyId, CrmConfiguration::TYPE_TASK_TYPE);
        $statusKeys = $this->configurationService->keys($companyId, CrmConfiguration::TYPE_TASK_STATUS);
        $priorityKeys = $this->configurationService->keys($companyId, CrmConfiguration::TYPE_PRIORITY);

        $request->validate([
            'task_type' => ['required', Rule::in($typeKeys)],
            'task_status' => ['required', Rule::in($statusKeys)],
            'priority' => ['required', Rule::in($priorityKeys)],
            'due_date' => ValidationService::requiredDate(),
            'assigned_employee_id' => ['required', ValidationService::existsForCompany('employee_accounts', $companyId)],
            'remarks' => ValidationService::text(),
        ]);

        try {
            DB::transaction(function () use ($request, $task, $companyId) {
                $activeFy = $this->activityService->validateBusinessDate($companyId, $request->due_date);
                $previousStatus = $task->task_status;

                $task->update([
                    'financial_year_id' => $activeFy->id,
                    'task_type' => $request->task_type,
                    'task_status' => $request->task_status,
                    'priority' => $request->priority,
                    'due_date' => $request->due_date,
                    'assigned_employee_id' => $request->assigned_employee_id,
                    'remarks' => $request->remarks,
                    'updated_by' => auth()->id(),
                ]);

                $this->historyService->record($companyId, 'task', (int) $task->id, 'Task Updated', null, null, 'Task updated.');

                if ($previousStatus !== $task->task_status) {
                    $this->historyService->record($companyId, 'task', (int) $task->id, 'Status Changed', $previousStatus, $task->task_status);
                }
            });

            return redirect()->route('company.crm-tasks.index')->with('success', 'Task updated successfully.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function complete($id)
    {
        $this->authorizeCompanyPermission('edit_crm_task');

        try {
            $companyId = auth()->user()->company_id;
            $task = CrmTask::where('company_id', $companyId)->findOrFail($id);

            $this->workflowService->applyStatus(
                $task,
                $companyId,
                'task',
                'task_status',
                CrmConfiguration::TYPE_TASK_STATUS,
                'completed',
                'Task Completed',
                null,
                ['completed_at' => now()]
            );

            return back()->with('success', 'Task completed successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function archive(Request $request, $id)
    {
        $this->authorizeCompanyPermission('archive_crm_task');

        $request->validate(['archive_reason' => ValidationService::requiredString(500)]);

        try {
            $companyId = auth()->user()->company_id;
            $task = CrmTask::where('company_id', $companyId)->findOrFail($id);

            $this->workflowService->archive(
                $task,
                $companyId,
                'task',
                'task_status',
                CrmConfiguration::TYPE_TASK_STATUS,
                $request->archive_reason,
                'Task Archived'
            );

            return back()->with('success', 'Task archived successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function cancel(Request $request, $id)
    {
        $this->authorizeCompanyPermission('cancel_crm_task');

        $request->validate(['cancel_reason' => ValidationService::requiredString(500)]);

        try {
            $companyId = auth()->user()->company_id;
            $task = CrmTask::where('company_id', $companyId)->findOrFail($id);

            $this->workflowService->cancel(
                $task,
                $companyId,
                'task',
                'task_status',
                CrmConfiguration::TYPE_TASK_STATUS,
                $request->cancel_reason,
                'Task Cancelled'
            );

            return back()->with('success', 'Task cancelled successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
