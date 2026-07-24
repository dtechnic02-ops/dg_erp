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

use App\Models\CrmOpportunity;

use App\Models\CrmStatusHistory;

use App\Models\EmployeeAccount;

use App\Services\CrmConfigurationService;

use App\Services\CrmOpportunityService;

use App\Services\CrmStatusHistoryService;

use App\Services\ValidationService;

use Illuminate\Http\Request;

use Illuminate\Validation\Rule;



class CrmOpportunityController extends Controller implements HasMiddleware

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

        private CrmOpportunityService $opportunityService

    ) {

    }



    public function index(Request $request)

    {

        $this->authorizeCompanyPermission('view_crm_opportunity');



        $companyId = auth()->user()->company_id;

        $inactiveStatuses = $this->configurationService->terminalKeys($companyId, CrmConfiguration::TYPE_OPPORTUNITY_STATUS);



        $query = CrmOpportunity::with(['assignedEmployee', 'lead', 'customer'])

            ->where('company_id', $companyId);



        if ($request->filled('stage')) {

            $query->where('stage', $request->stage);

        }



        if ($request->filled('status')) {

            $query->where('status', $request->status);

        } elseif (!$request->has('status')) {

            $query->whereNotIn('status', $inactiveStatuses);

        }



        if ($request->filled('search')) {

            $search = trim($request->search);

            $query->where(function ($q) use ($search) {

                $q->where('opportunity_no', 'like', '%' . $search . '%')

                    ->orWhere('title', 'like', '%' . $search . '%')

                    ->orWhereHas('customer', function ($customerQuery) use ($search) {

                        $customerQuery->where('name', 'like', '%' . $search . '%')

                            ->orWhere('mobile', 'like', '%' . $search . '%')

                            ->orWhere('email', 'like', '%' . $search . '%');

                    })

                    ->orWhereHas('lead', function ($leadQuery) use ($search) {

                        $leadQuery->where('lead_no', 'like', '%' . $search . '%');

                    });

            });

        }



        $opportunities = $query->orderByDesc('id')->paginate(20)->withQueryString();

        $stageOptions = $this->configurationService->options($companyId, CrmConfiguration::TYPE_OPPORTUNITY_STAGE);

        $statusOptions = $this->configurationService->options($companyId, CrmConfiguration::TYPE_OPPORTUNITY_STATUS);



        return view('company.crm-opportunities.index', compact('opportunities', 'stageOptions', 'statusOptions'));

    }



    public function create(Request $request)

    {

        $this->authorizeCompanyPermission('create_crm_opportunity');



        $companyId = auth()->user()->company_id;



        try {

            $activeFy = $this->assertActiveFinancialYear($companyId);

        } catch (\Exception $e) {

            return redirect()->route('company.crm-opportunities.index')->with('error', $e->getMessage());

        }



        $employees = EmployeeAccount::where('company_id', $companyId)->active()->orderBy('first_name')->get();

        $leads = CrmLead::with('customer')

            ->where('company_id', $companyId)

            ->whereNull('cancelled_at')

            ->orderByDesc('lead_date')

            ->get();

        $stageOptions = $this->configurationService->options($companyId, CrmConfiguration::TYPE_OPPORTUNITY_STAGE);



        return view('company.crm-opportunities.create', compact('employees', 'leads', 'activeFy', 'stageOptions'));

    }



    public function store(Request $request)

    {

        $this->authorizeCompanyPermission('create_crm_opportunity');



        $companyId = auth()->user()->company_id;

        $validated = $this->validateOpportunity($request, $companyId);



        try {

            $this->opportunityService->create($companyId, $validated);



            return redirect()->route('company.crm-opportunities.index')->with('success', 'Opportunity created successfully.');

        } catch (\Exception $e) {

            return back()->withInput()->with('error', $e->getMessage());

        }

    }



    public function show($id)

    {

        $this->authorizeCompanyPermission('view_crm_opportunity');



        $companyId = auth()->user()->company_id;

        $terminalKeys = $this->configurationService->terminalKeys($companyId, CrmConfiguration::TYPE_OPPORTUNITY_STATUS);



        $opportunity = CrmOpportunity::with(['assignedEmployee', 'lead', 'customer', 'financialYear'])

            ->where('company_id', $companyId)

            ->findOrFail($id);



        $histories = CrmStatusHistory::with('changer')

            ->where('company_id', $companyId)

            ->where('entity_type', 'opportunity')

            ->where('entity_id', $opportunity->id)

            ->orderByDesc('changed_at')

            ->get();



        $notes = CrmNote::with('creator')

            ->where('company_id', $companyId)

            ->where('entity_type', 'opportunity')

            ->where('entity_id', $opportunity->id)

            ->whereNull('archived_at')

            ->orderByDesc('id')

            ->get();



        $attachments = CrmAttachment::with('creator')

            ->where('company_id', $companyId)

            ->where('entity_type', 'opportunity')

            ->where('entity_id', $opportunity->id)

            ->where('is_archived', false)

            ->orderByDesc('id')

            ->get();



        return view('company.crm-opportunities.show', compact('opportunity', 'histories', 'notes', 'attachments', 'terminalKeys'));

    }



    public function edit($id)

    {

        $this->authorizeCompanyPermission('edit_crm_opportunity');



        $companyId = auth()->user()->company_id;

        $terminalKeys = $this->configurationService->terminalKeys($companyId, CrmConfiguration::TYPE_OPPORTUNITY_STATUS);

        $opportunity = CrmOpportunity::where('company_id', $companyId)->findOrFail($id);



        if (!$opportunity->isEditable($terminalKeys)) {

            return redirect()->route('company.crm-opportunities.show', $opportunity->id)->with('error', 'This opportunity cannot be edited.');

        }



        $employees = EmployeeAccount::where('company_id', $companyId)->active()->orderBy('first_name')->get();

        $leads = CrmLead::with('customer')->where('company_id', $companyId)->orderByDesc('lead_date')->get();

        $stageOptions = $this->configurationService->options($companyId, CrmConfiguration::TYPE_OPPORTUNITY_STAGE);



        return view('company.crm-opportunities.edit', compact('opportunity', 'employees', 'leads', 'stageOptions'));

    }



    public function update(Request $request, $id)

    {

        $this->authorizeCompanyPermission('edit_crm_opportunity');



        $companyId = auth()->user()->company_id;

        $validated = $this->validateOpportunity($request, $companyId);



        try {

            $opportunity = CrmOpportunity::where('company_id', $companyId)->findOrFail($id);

            $this->opportunityService->update($opportunity, $validated);



            return redirect()->route('company.crm-opportunities.show', $opportunity->id)->with('success', 'Opportunity updated successfully.');

        } catch (\Exception $e) {

            return back()->withInput()->with('error', $e->getMessage());

        }

    }



    public function close(Request $request, $id)

    {

        $this->authorizeCompanyPermission('close_crm_opportunity');



        $request->validate(['close_reason' => ValidationService::requiredString(500)]);



        try {

            $opportunity = CrmOpportunity::where('company_id', auth()->user()->company_id)->findOrFail($id);

            $this->opportunityService->close($opportunity, $request->close_reason);



            return back()->with('success', 'Opportunity closed successfully.');

        } catch (\Exception $e) {

            return back()->with('error', $e->getMessage());

        }

    }



    public function won(Request $request, $id)

    {

        $this->authorizeCompanyPermission('close_crm_opportunity');



        $request->validate(['remarks' => ValidationService::text()]);



        try {

            $opportunity = CrmOpportunity::where('company_id', auth()->user()->company_id)->findOrFail($id);

            $this->opportunityService->markWon($opportunity, (string) $request->remarks);



            return back()->with('success', 'Opportunity marked as won.');

        } catch (\Exception $e) {

            return back()->with('error', $e->getMessage());

        }

    }



    public function lost(Request $request, $id)

    {

        $this->authorizeCompanyPermission('close_crm_opportunity');



        $request->validate(['close_reason' => ValidationService::requiredString(500)]);



        try {

            $opportunity = CrmOpportunity::where('company_id', auth()->user()->company_id)->findOrFail($id);

            $this->opportunityService->markLost($opportunity, $request->close_reason);



            return back()->with('success', 'Opportunity marked as lost.');

        } catch (\Exception $e) {

            return back()->with('error', $e->getMessage());

        }

    }



    public function archive(Request $request, $id)

    {

        $this->authorizeCompanyPermission('archive_crm_opportunity');



        $request->validate(['archive_reason' => ValidationService::requiredString(500)]);



        try {

            $opportunity = CrmOpportunity::where('company_id', auth()->user()->company_id)->findOrFail($id);

            $this->opportunityService->archive($opportunity, $request->archive_reason);



            return back()->with('success', 'Opportunity archived successfully.');

        } catch (\Exception $e) {

            return back()->with('error', $e->getMessage());

        }

    }



    public function cancel(Request $request, $id)

    {

        $this->authorizeCompanyPermission('cancel_crm_opportunity');



        $request->validate(['cancel_reason' => ValidationService::requiredString(500)]);



        try {

            $opportunity = CrmOpportunity::where('company_id', auth()->user()->company_id)->findOrFail($id);

            $this->opportunityService->cancel($opportunity, $request->cancel_reason);



            return back()->with('success', 'Opportunity cancelled successfully.');

        } catch (\Exception $e) {

            return back()->with('error', $e->getMessage());

        }

    }



    private function validateOpportunity(Request $request, int $companyId): array

    {

        $stageKeys = $this->configurationService->keys($companyId, CrmConfiguration::TYPE_OPPORTUNITY_STAGE);



        return $request->validate([

            'title' => ValidationService::requiredString(),

            'crm_lead_id' => ['required', ValidationService::existsForCompany('crm_leads', $companyId)],

            'assigned_employee_id' => ['required', ValidationService::existsForCompany('employee_accounts', $companyId)],

            'stage' => ['required', Rule::in($stageKeys)],

            'potential_value' => ValidationService::amount(),

            'expected_closing_date' => ValidationService::date(),

            'probability' => 'nullable|numeric|min:0|max:100',

            'remarks' => ValidationService::text(),

        ]);

    }

}


