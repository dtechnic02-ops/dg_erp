<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesCompanyPermission;
use App\Http\Controllers\Concerns\AuthorizesSubscriptionModule;
use Illuminate\Routing\Controllers\HasMiddleware;
use App\Models\CrmAttachment;
use App\Models\CrmConfiguration;
use App\Models\CrmContact;
use App\Models\CrmLead;
use App\Models\CrmNote;
use App\Models\CrmStatusHistory;
use App\Models\Customer;
use App\Models\EmployeeAccount;
use App\Models\FinancialYear;
use App\Services\CrmConfigurationService;
use App\Services\CrmContactService;
use App\Services\ValidationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CrmContactController extends Controller implements HasMiddleware
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
        private CrmContactService $contactService
    ) {
    }

    public function index(Request $request)
    {
        $this->authorizeCompanyPermission('view_crm_contact');

        $companyId = auth()->user()->company_id;
        $inactiveStatuses = $this->configurationService->terminalKeys($companyId, CrmConfiguration::TYPE_CONTACT_STATUS);

        $query = CrmContact::with(['assignedEmployee', 'lead', 'customer', 'financialYear'])
            ->where('company_id', $companyId);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('contact_no', 'like', '%' . $search . '%')
                    ->orWhere('name', 'like', '%' . $search . '%')
                    ->orWhere('mobile', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhereHas('customer', function ($customerQuery) use ($search) {
                        $customerQuery->where('name', 'like', '%' . $search . '%')
                            ->orWhere('mobile', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%');
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } elseif (!$request->has('status')) {
            $query->whereNotIn('status', $inactiveStatuses);
        }

        $contacts = $query->orderByDesc('contact_date')->paginate(20)->withQueryString();
        $statusOptions = $this->configurationService->options($companyId, CrmConfiguration::TYPE_CONTACT_STATUS);

        return view('company.crm-contacts.index', compact('contacts', 'statusOptions'));
    }

    public function create()
    {
        $this->authorizeCompanyPermission('create_crm_contact');

        $companyId = auth()->user()->company_id;
        $activeFy = FinancialYear::where('company_id', $companyId)->where('is_active', 1)->first();

        if (!$activeFy) {
            return redirect()->route('company.crm-contacts.index')->with('error', 'Please activate financial year first.');
        }

        $employees = EmployeeAccount::where('company_id', $companyId)->active()->orderBy('first_name')->get();
        $leads = CrmLead::with('customer')->where('company_id', $companyId)->orderByDesc('lead_date')->get();
        $customers = Customer::where('company_id', $companyId)->where('status', 'active')->orderBy('name')->get();
        $statusOptions = $this->configurationService->options($companyId, CrmConfiguration::TYPE_CONTACT_STATUS);
        $priorityOptions = $this->configurationService->options($companyId, CrmConfiguration::TYPE_PRIORITY);

        return view('company.crm-contacts.create', compact('employees', 'leads', 'customers', 'activeFy', 'statusOptions', 'priorityOptions'));
    }

    public function store(Request $request)
    {
        $this->authorizeCompanyPermission('create_crm_contact');

        $companyId = auth()->user()->company_id;
        $validated = $this->validateContact($request, $companyId);

        try {
            $contact = $this->contactService->create($companyId, $validated);

            return redirect()->route('company.crm-contacts.show', $contact->id)->with('success', 'Contact created successfully.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function show($id)
    {
        $this->authorizeCompanyPermission('view_crm_contact');

        $companyId = auth()->user()->company_id;
        $terminalKeys = $this->configurationService->terminalKeys($companyId, CrmConfiguration::TYPE_CONTACT_STATUS);

        $contact = CrmContact::with(['assignedEmployee', 'lead', 'customer', 'financialYear', 'creator'])
            ->where('company_id', $companyId)
            ->findOrFail($id);

        $histories = CrmStatusHistory::with('changer')
            ->where('company_id', $companyId)
            ->where('entity_type', 'contact')
            ->where('entity_id', $contact->id)
            ->orderByDesc('changed_at')
            ->get();

        $notes = CrmNote::with('creator')
            ->where('company_id', $companyId)
            ->where('entity_type', 'contact')
            ->where('entity_id', $contact->id)
            ->whereNull('archived_at')
            ->orderByDesc('id')
            ->get();

        $attachments = CrmAttachment::with('creator')
            ->where('company_id', $companyId)
            ->where('entity_type', 'contact')
            ->where('entity_id', $contact->id)
            ->where('is_archived', false)
            ->orderByDesc('id')
            ->get();

        return view('company.crm-contacts.show', compact('contact', 'histories', 'notes', 'attachments', 'terminalKeys'));
    }

    public function edit($id)
    {
        $this->authorizeCompanyPermission('edit_crm_contact');

        $companyId = auth()->user()->company_id;
        $terminalKeys = $this->configurationService->terminalKeys($companyId, CrmConfiguration::TYPE_CONTACT_STATUS);
        $contact = CrmContact::where('company_id', $companyId)->findOrFail($id);

        if (!$contact->isEditable($terminalKeys)) {
            return redirect()->route('company.crm-contacts.show', $contact->id)->with('error', 'This contact cannot be edited.');
        }

        $employees = EmployeeAccount::where('company_id', $companyId)->active()->orderBy('first_name')->get();
        $leads = CrmLead::with('customer')->where('company_id', $companyId)->orderByDesc('lead_date')->get();
        $customers = Customer::where('company_id', $companyId)->where('status', 'active')->orderBy('name')->get();
        $statusOptions = $this->configurationService->options($companyId, CrmConfiguration::TYPE_CONTACT_STATUS);
        $priorityOptions = $this->configurationService->options($companyId, CrmConfiguration::TYPE_PRIORITY);

        return view('company.crm-contacts.edit', compact('contact', 'employees', 'leads', 'customers', 'statusOptions', 'priorityOptions'));
    }

    public function update(Request $request, $id)
    {
        $this->authorizeCompanyPermission('edit_crm_contact');

        $companyId = auth()->user()->company_id;
        $contact = CrmContact::where('company_id', $companyId)->findOrFail($id);
        $validated = $this->validateContact($request, $companyId);

        try {
            $this->contactService->update($contact, $validated);

            return redirect()->route('company.crm-contacts.show', $contact->id)->with('success', 'Contact updated successfully.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function archive(Request $request, $id)
    {
        $this->authorizeCompanyPermission('archive_crm_contact');

        $request->validate(['archive_reason' => ValidationService::requiredString(500)]);

        try {
            $contact = CrmContact::where('company_id', auth()->user()->company_id)->findOrFail($id);
            $this->contactService->archive($contact, $request->archive_reason);

            return back()->with('success', 'Contact archived successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function cancel(Request $request, $id)
    {
        $this->authorizeCompanyPermission('cancel_crm_contact');

        $request->validate(['cancel_reason' => ValidationService::requiredString(500)]);

        try {
            $contact = CrmContact::where('company_id', auth()->user()->company_id)->findOrFail($id);
            $this->contactService->cancel($contact, $request->cancel_reason);

            return back()->with('success', 'Contact cancelled successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    private function validateContact(Request $request, int $companyId): array
    {
        $statusKeys = $this->configurationService->keys($companyId, CrmConfiguration::TYPE_CONTACT_STATUS);
        $priorityKeys = $this->configurationService->keys($companyId, CrmConfiguration::TYPE_PRIORITY);

        return $request->validate([
            'name' => ValidationService::requiredString(),
            'designation' => ValidationService::string(100),
            'department' => ValidationService::string(100),
            'mobile' => ValidationService::phone(),
            'phone' => ValidationService::phone(),
            'email' => ValidationService::email(),
            'crm_lead_id' => ['nullable', ValidationService::existsForCompany('crm_leads', $companyId)],
            'customer_id' => ['required', ValidationService::existsForCompany('customers', $companyId)],
            'assigned_employee_id' => ['required', ValidationService::existsForCompany('employee_accounts', $companyId)],
            'status' => ['required', Rule::in($statusKeys)],
            'priority' => ['required', Rule::in($priorityKeys)],
            'contact_date' => ValidationService::requiredDate(),
            'remarks' => ValidationService::text(),
        ]);
    }
}
