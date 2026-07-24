<?php



namespace App\Services;



use App\Models\CrmConfiguration;

use App\Models\CrmContact;

use App\Models\CrmLead;

use App\Models\Customer;

use App\Models\EmployeeAccount;

use App\Models\FinancialYear;

use Illuminate\Support\Facades\DB;



class CrmContactService

{

    public function __construct(

        private CrmConfigurationService $configurationService,

        private CrmActivityService $activityService,

        private CrmStatusHistoryService $historyService,

        private CrmWorkflowService $workflowService

    ) {

    }



    public function create(int $companyId, array $data): CrmContact

    {

        return DB::transaction(function () use ($companyId, $data) {

            $activeFy = $this->activityService->validateBusinessDate(

                $companyId,

                $data['contact_date'],

                'Contact date must fall within the active financial year.'

            );



            EmployeeAccount::where('company_id', $companyId)->active()->findOrFail($data['assigned_employee_id']);



            $customerId = $this->resolveCustomerId($companyId, $data);



            $defaultStatus = $this->configurationService->requireKey(

                $companyId,

                CrmConfiguration::TYPE_CONTACT_STATUS,

                'active'

            );



            $contact = CrmContact::create([

                'company_id' => $companyId,

                'financial_year_id' => $activeFy->id,

                'contact_no' => CrmNumberService::generateContactNo($companyId),

                'crm_lead_id' => $data['crm_lead_id'] ?? null,

                'customer_id' => $customerId,

                'name' => $data['name'],

                'designation' => $data['designation'] ?? null,

                'department' => $data['department'] ?? null,

                'mobile' => $data['mobile'] ?? null,

                'phone' => $data['phone'] ?? null,

                'email' => $data['email'] ?? null,

                'assigned_employee_id' => $data['assigned_employee_id'],

                'status' => $data['status'] ?? $defaultStatus,

                'priority' => $data['priority'],

                'contact_date' => $data['contact_date'],

                'remarks' => $data['remarks'] ?? null,

                'created_by' => auth()->id(),

            ]);



            $this->historyService->record(

                $companyId,

                'contact',

                (int) $contact->id,

                'Contact Created',

                null,

                $contact->status,

                'Contact created.'

            );



            return $contact;

        });

    }



    public function update(CrmContact $contact, array $data): CrmContact

    {

        $companyId = (int) $contact->company_id;



        return DB::transaction(function () use ($contact, $companyId, $data) {

            $this->workflowService->guardEditable(

                $contact,

                $companyId,

                CrmConfiguration::TYPE_CONTACT_STATUS,

                'status',

                'This contact cannot be edited.'

            );



            $activeFy = FinancialYear::where('company_id', $companyId)->findOrFail($contact->financial_year_id);

            $this->configurationService->assertDateWithinActiveFinancialYear(

                $activeFy,

                $data['contact_date']

            );



            $customerId = $this->resolveCustomerId($companyId, $data);

            $previousStatus = $contact->status;



            $contact->update([

                'crm_lead_id' => $data['crm_lead_id'] ?? null,

                'customer_id' => $customerId,

                'name' => $data['name'],

                'designation' => $data['designation'] ?? null,

                'department' => $data['department'] ?? null,

                'mobile' => $data['mobile'] ?? null,

                'phone' => $data['phone'] ?? null,

                'email' => $data['email'] ?? null,

                'assigned_employee_id' => $data['assigned_employee_id'],

                'status' => $data['status'],

                'priority' => $data['priority'],

                'contact_date' => $data['contact_date'],

                'remarks' => $data['remarks'] ?? null,

                'updated_by' => auth()->id(),

            ]);



            $this->historyService->record($companyId, 'contact', (int) $contact->id, 'Contact Updated', null, null, 'Contact updated.');



            if ($previousStatus !== $contact->status) {

                $this->historyService->record(

                    $companyId,

                    'contact',

                    (int) $contact->id,

                    'Status Changed',

                    $previousStatus,

                    $contact->status

                );

            }



            return $contact->fresh(['customer']);

        });

    }



    public function close(CrmContact $contact, string $reason): void

    {

        $companyId = (int) $contact->company_id;



        $this->workflowService->guardEditable($contact, $companyId, CrmConfiguration::TYPE_CONTACT_STATUS, 'status');



        $this->workflowService->applyStatus(

            $contact,

            $companyId,

            'contact',

            'status',

            CrmConfiguration::TYPE_CONTACT_STATUS,

            'closed',

            'Contact Closed',

            $reason,

            [

                'closed_by' => auth()->id(),

                'closed_at' => now(),

                'close_reason' => trim($reason),

            ]

        );

    }



    public function archive(CrmContact $contact, string $reason): void

    {

        $this->workflowService->archive(

            $contact,

            (int) $contact->company_id,

            'contact',

            'status',

            CrmConfiguration::TYPE_CONTACT_STATUS,

            $reason,

            'Contact Archived'

        );

    }



    public function cancel(CrmContact $contact, string $reason): void

    {

        $this->workflowService->cancel(

            $contact,

            (int) $contact->company_id,

            'contact',

            'status',

            CrmConfiguration::TYPE_CONTACT_STATUS,

            $reason,

            'Contact Cancelled'

        );

    }



    private function resolveCustomerId(int $companyId, array $data): int

    {

        $customerId = (int) ($data['customer_id'] ?? 0);



        if (!empty($data['crm_lead_id'])) {

            $lead = CrmLead::where('company_id', $companyId)->findOrFail($data['crm_lead_id']);



            if (!$lead->customer_id) {

                throw new \Exception('Selected customer relationship must be linked to an active customer.');

            }



            if ($customerId && $customerId !== (int) $lead->customer_id) {

                throw new \Exception('Selected customer must match the related customer relationship.');

            }



            $customerId = (int) $lead->customer_id;

        }



        if (!$customerId) {

            throw new \Exception('Customer is required for contact persons.');

        }



        Customer::where('company_id', $companyId)

            ->where('status', 'active')

            ->findOrFail($customerId);



        return $customerId;

    }

}


