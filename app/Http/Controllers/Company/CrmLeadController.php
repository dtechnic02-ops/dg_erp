<?php



namespace App\Http\Controllers\Company;



use App\Http\Controllers\Controller;

use App\Http\Controllers\Concerns\AuthorizesCompanyPermission;

use App\Http\Controllers\Concerns\AuthorizesSubscriptionModule;

use App\Http\Controllers\Concerns\HandlesTransactionDocumentationEdit;

use Illuminate\Routing\Controllers\HasMiddleware;

use App\Models\CrmAttachment;

use App\Models\CrmConfiguration;

use App\Models\CrmLead;

use App\Models\CrmNote;

use App\Models\CrmStatusHistory;

use App\Models\Customer;

use App\Models\EmployeeAccount;

use App\Models\FinancialYear;

use App\Services\CrmConfigurationService;

use App\Services\CrmLeadService;

use App\Services\CrmNumberService;

use App\Services\CrmStatusHistoryService;

use App\Services\CrmWorkflowService;

use App\Services\ValidationService;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

use Illuminate\Validation\Rule;



class CrmLeadController extends Controller implements HasMiddleware

{

    use AuthorizesCompanyPermission;

    use AuthorizesSubscriptionModule;

    use HandlesTransactionDocumentationEdit;



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

        private CrmLeadService $leadService,

        private CrmWorkflowService $workflowService

    ) {

    }



    public function index(Request $request)

    {

        $this->authorizeCompanyPermission('view_crm_lead');



        $companyId = auth()->user()->company_id;

        $this->configurationService->ensureDefaults($companyId);

        $terminalKeys = $this->configurationService->terminalKeys($companyId, CrmConfiguration::TYPE_LEAD_STATUS);



        $activeFy = FinancialYear::where('company_id', $companyId)->where('is_active', 1)->first();

        $query = $this->buildIndexQuery($request, $companyId, $activeFy);



        $perPage = in_array((int) $request->per_page, [10, 20, 50, 100], true)

            ? (int) $request->per_page

            : 20;



        $leads = $query->paginate($perPage)->withQueryString();

        $employees = EmployeeAccount::where('company_id', $companyId)->active()->orderBy('first_name')->get();

        $financialYears = FinancialYear::where('company_id', $companyId)->orderByDesc('start_date')->get();

        $statusOptions = $this->configurationService->options($companyId, CrmConfiguration::TYPE_LEAD_STATUS);

        $priorityOptions = $this->configurationService->options($companyId, CrmConfiguration::TYPE_PRIORITY);



        return view('company.crm-leads.index', compact(

            'leads',

            'employees',

            'financialYears',

            'activeFy',

            'statusOptions',

            'priorityOptions',

            'perPage',

            'terminalKeys'

        ));

    }



    public function create()

    {

        $this->authorizeCompanyPermission('create_crm_lead');



        $companyId = auth()->user()->company_id;

        $this->configurationService->ensureDefaults($companyId);



        try {

            $activeFy = $this->assertActiveFinancialYear($companyId);

        } catch (\Exception $e) {

            return redirect()->route('company.crm-leads.index')->with('error', $e->getMessage());

        }



        $employees = EmployeeAccount::where('company_id', $companyId)->active()->orderBy('first_name')->get();

        $customers = $this->activeCustomers($companyId);

        $statusOptions = $this->configurationService->options($companyId, CrmConfiguration::TYPE_LEAD_STATUS);

        $priorityOptions = $this->configurationService->options($companyId, CrmConfiguration::TYPE_PRIORITY);

        $defaultStatus = $this->configurationService->resolveDefaultRelationshipStatus($companyId);



        return view('company.crm-leads.create', compact(

            'employees',

            'customers',

            'activeFy',

            'statusOptions',

            'priorityOptions',

            'defaultStatus'

        ));

    }



    public function store(Request $request)

    {

        $this->authorizeCompanyPermission('create_crm_lead');



        $companyId = auth()->user()->company_id;

        $validated = $this->validateRelationship($request, $companyId);



        try {

            DB::transaction(function () use ($validated, $companyId) {

                $activeFy = $this->assertActiveFinancialYear($companyId);

                $this->assertDateWithinFinancialYear(

                    $validated['lead_date'],

                    $activeFy,

                    'Relationship date must fall within the active financial year.'

                );



                $this->assertActiveCustomer($companyId, (int) $validated['customer_id']);

                EmployeeAccount::where('company_id', $companyId)->active()->findOrFail($validated['assigned_employee_id']);



                $lead = CrmLead::create([

                    'company_id' => $companyId,

                    'financial_year_id' => $activeFy->id,

                    'lead_no' => CrmNumberService::generateLeadNo($companyId),

                    'customer_id' => $validated['customer_id'],

                    'assigned_employee_id' => $validated['assigned_employee_id'],

                    'status' => $validated['status'],

                    'priority' => $validated['priority'],

                    'expected_value' => $validated['expected_value'] ?? 0,

                    'lead_date' => $validated['lead_date'],

                    'remarks' => $validated['remarks'] ?? null,

                    'created_by' => auth()->id(),

                ]);



                $this->historyService->record($companyId, 'lead', (int) $lead->id, 'Relationship Created', null, $lead->status, 'Customer relationship created.');

            });



            return redirect()->route('company.crm-leads.index')->with('success', 'Customer relationship created successfully.');

        } catch (\Exception $e) {

            return back()->withInput()->with('error', $e->getMessage());

        }

    }



    public function show($id)

    {

        $this->authorizeCompanyPermission('view_crm_lead');



        $companyId = auth()->user()->company_id;

        $terminalKeys = $this->configurationService->terminalKeys($companyId, CrmConfiguration::TYPE_LEAD_STATUS);



        $lead = CrmLead::with([

            'customer',

            'assignedEmployee',

            'financialYear',

            'creator',

            'followUps.assignedEmployee',

            'meetings.assignedEmployee',

            'tasks.assignedEmployee',

            'opportunities',

            'contacts',

        ])->where('company_id', $companyId)->findOrFail($id);



        $histories = CrmStatusHistory::with('changer')

            ->where('company_id', $companyId)

            ->where('entity_type', 'lead')

            ->where('entity_id', $lead->id)

            ->orderByDesc('changed_at')

            ->get();



        $notes = CrmNote::with('creator')

            ->where('company_id', $companyId)

            ->where('entity_type', 'lead')

            ->where('entity_id', $lead->id)

            ->whereNull('archived_at')

            ->orderByDesc('id')

            ->get();



        $attachments = CrmAttachment::with('creator')

            ->where('company_id', $companyId)

            ->where('entity_type', 'lead')

            ->where('entity_id', $lead->id)

            ->where('is_archived', false)

            ->orderByDesc('id')

            ->get();



        return view('company.crm-leads.show', compact('lead', 'histories', 'notes', 'attachments', 'terminalKeys'));

    }



    public function edit($id)

    {

        $this->authorizeCompanyPermission('edit_crm_lead');



        $companyId = auth()->user()->company_id;

        $terminalKeys = $this->configurationService->terminalKeys($companyId, CrmConfiguration::TYPE_LEAD_STATUS);

        $lead = CrmLead::where('company_id', $companyId)->findOrFail($id);



        if (!$lead->isEditable($terminalKeys)) {

            return redirect()->route('company.crm-leads.show', $lead->id)->with('error', 'This relationship cannot be edited.');

        }



        $employees = EmployeeAccount::where('company_id', $companyId)->active()->orderBy('first_name')->get();

        $customers = $this->activeCustomers($companyId);

        $statusOptions = $this->configurationService->options($companyId, CrmConfiguration::TYPE_LEAD_STATUS);

        $priorityOptions = $this->configurationService->options($companyId, CrmConfiguration::TYPE_PRIORITY);



        return view('company.crm-leads.edit', compact('lead', 'employees', 'customers', 'statusOptions', 'priorityOptions'));

    }



    public function update(Request $request, $id)

    {

        $this->authorizeCompanyPermission('edit_crm_lead');



        $companyId = auth()->user()->company_id;

        $terminalKeys = $this->configurationService->terminalKeys($companyId, CrmConfiguration::TYPE_LEAD_STATUS);

        $lead = CrmLead::where('company_id', $companyId)->findOrFail($id);



        if (!$lead->isEditable($terminalKeys)) {

            return back()->with('error', 'This relationship cannot be edited.');

        }



        $validated = $this->validateRelationship($request, $companyId);



        try {

            DB::transaction(function () use ($validated, $lead, $companyId) {

                $activeFy = FinancialYear::where('company_id', $companyId)->findOrFail($lead->financial_year_id);

                $this->assertDateWithinFinancialYear($validated['lead_date'], $activeFy);



                $this->assertActiveCustomer($companyId, (int) $validated['customer_id']);



                $previousStatus = $lead->status;

                $previousEmployee = (string) $lead->assigned_employee_id;



                $lead->update([

                    'customer_id' => $validated['customer_id'],

                    'assigned_employee_id' => $validated['assigned_employee_id'],

                    'status' => $validated['status'],

                    'priority' => $validated['priority'],

                    'expected_value' => $validated['expected_value'] ?? 0,

                    'lead_date' => $validated['lead_date'],

                    'remarks' => $validated['remarks'] ?? null,

                    'updated_by' => auth()->id(),

                ]);



                $this->historyService->record($companyId, 'lead', (int) $lead->id, 'Relationship Updated', null, null, 'Customer relationship updated.');



                if ($previousStatus !== $lead->status) {

                    $this->historyService->record($companyId, 'lead', (int) $lead->id, 'Status Changed', $previousStatus, $lead->status);

                }



                if ($previousEmployee !== (string) $lead->assigned_employee_id) {

                    $this->historyService->record($companyId, 'lead', (int) $lead->id, 'Relationship Assigned', $previousEmployee, (string) $lead->assigned_employee_id);

                }

            });



            return redirect()->route('company.crm-leads.show', $lead->id)->with('success', 'Customer relationship updated successfully.');

        } catch (\Exception $e) {

            return back()->withInput()->with('error', $e->getMessage());

        }

    }



    public function close(Request $request, $id)

    {

        $this->authorizeCompanyPermission('close_crm_lead');



        $request->validate(['close_reason' => ValidationService::requiredString(500)]);



        try {

            $companyId = auth()->user()->company_id;

            $lead = CrmLead::where('company_id', $companyId)->findOrFail($id);

            $this->leadService->close($lead, $companyId, $request->close_reason);



            return back()->with('success', 'Customer relationship closed successfully.');

        } catch (\Exception $e) {

            return back()->with('error', $e->getMessage());

        }

    }



    public function archive(Request $request, $id)

    {

        $this->authorizeCompanyPermission('archive_crm_lead');



        $request->validate(['archive_reason' => ValidationService::requiredString(500)]);



        try {

            $companyId = auth()->user()->company_id;

            $lead = CrmLead::where('company_id', $companyId)->findOrFail($id);



            $this->workflowService->archive(

                $lead,

                $companyId,

                'lead',

                'status',

                CrmConfiguration::TYPE_LEAD_STATUS,

                $request->archive_reason,

                'Relationship Archived'

            );



            return back()->with('success', 'Customer relationship archived successfully.');

        } catch (\Exception $e) {

            return back()->with('error', $e->getMessage());

        }

    }



    public function cancel(Request $request, $id)

    {

        $this->authorizeCompanyPermission('cancel_crm_lead');



        $request->validate(['cancel_reason' => ValidationService::requiredString(500)]);



        try {

            $companyId = auth()->user()->company_id;

            $lead = CrmLead::where('company_id', $companyId)->findOrFail($id);



            $this->workflowService->cancel(

                $lead,

                $companyId,

                'lead',

                'status',

                CrmConfiguration::TYPE_LEAD_STATUS,

                $request->cancel_reason,

                'Relationship Cancelled'

            );



            return back()->with('success', 'Customer relationship cancelled successfully.');

        } catch (\Exception $e) {

            return back()->with('error', $e->getMessage());

        }

    }



    private function buildIndexQuery(Request $request, int $companyId, ?FinancialYear $activeFy)

    {

        $inactiveStatuses = $this->configurationService->terminalKeys($companyId, CrmConfiguration::TYPE_LEAD_STATUS);



        $query = CrmLead::with(['assignedEmployee', 'financialYear', 'customer'])

            ->where('company_id', $companyId);



        if ($request->filled('financial_year_id')) {

            $query->where('financial_year_id', $request->financial_year_id);

        } elseif (!$request->has('financial_year_id') && $activeFy) {

            $query->where('financial_year_id', $activeFy->id);

        }



        if ($request->filled('search')) {

            $search = trim($request->search);

            $query->where(function ($q) use ($search) {

                $q->where('lead_no', 'like', '%' . $search . '%')

                    ->orWhereHas('customer', function ($customerQuery) use ($search) {

                        $customerQuery->where('name', 'like', '%' . $search . '%')

                            ->orWhere('mobile', 'like', '%' . $search . '%')

                            ->orWhere('email', 'like', '%' . $search . '%')

                            ->orWhere('authority_name', 'like', '%' . $search . '%');

                    });

            });

        }



        if ($request->filled('employee_id')) {

            $query->where('assigned_employee_id', $request->employee_id);

        }



        if ($request->filled('status')) {

            $query->where('status', $request->status);

        } elseif (!$request->has('status')) {

            $query->whereNotIn('status', $inactiveStatuses);

        }



        if ($request->filled('priority')) {

            $query->where('priority', $request->priority);

        }



        if ($request->filled('start_date')) {

            $query->whereDate('lead_date', '>=', $request->start_date);

        }



        if ($request->filled('end_date')) {

            $query->whereDate('lead_date', '<=', $request->end_date);

        }



        return $query->orderByDesc('lead_date')->orderByDesc('id');

    }



    private function validateRelationship(Request $request, int $companyId): array

    {

        $statusKeys = $this->configurationService->keys($companyId, CrmConfiguration::TYPE_LEAD_STATUS);

        $priorityKeys = $this->configurationService->keys($companyId, CrmConfiguration::TYPE_PRIORITY);



        return $request->validate([

            'customer_id' => ['required', ValidationService::existsForCompany('customers', $companyId)],

            'assigned_employee_id' => ['required', ValidationService::existsForCompany('employee_accounts', $companyId)],

            'status' => ['required', Rule::in($statusKeys)],

            'priority' => ['required', Rule::in($priorityKeys)],

            'expected_value' => ValidationService::amount(),

            'lead_date' => ValidationService::requiredDate(),

            'remarks' => ValidationService::text(),

        ]);

    }



    private function activeCustomers(int $companyId)

    {

        return Customer::where('company_id', $companyId)

            ->where('status', 'active')

            ->orderBy('name')

            ->get();

    }



    private function assertActiveCustomer(int $companyId, int $customerId): Customer

    {

        $customer = Customer::where('company_id', $companyId)

            ->where('id', $customerId)

            ->where('status', 'active')

            ->first();



        if (!$customer) {

            throw new \Exception('Selected customer is invalid or inactive.');

        }



        return $customer;

    }

}


