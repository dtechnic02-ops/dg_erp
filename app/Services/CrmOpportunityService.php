<?php



namespace App\Services;



use App\Models\CrmConfiguration;

use App\Models\CrmLead;

use App\Models\CrmOpportunity;

use App\Models\Customer;

use App\Models\EmployeeAccount;

use App\Models\FinancialYear;

use Illuminate\Support\Facades\DB;



class CrmOpportunityService

{

    public function __construct(

        private CrmConfigurationService $configurationService,

        private CrmActivityService $activityService,

        private CrmStatusHistoryService $historyService,

        private CrmWorkflowService $workflowService

    ) {

    }



    public function create(int $companyId, array $data): CrmOpportunity

    {

        return DB::transaction(function () use ($companyId, $data) {

            $activeFy = $this->activityService->validateBusinessDate(

                $companyId,

                $data['expected_closing_date'] ?? now()->toDateString(),

                'Expected closing date must fall within the active financial year.'

            );



            EmployeeAccount::where('company_id', $companyId)->active()->findOrFail($data['assigned_employee_id']);



            $customerId = $this->resolveCustomerIdFromRelationship($companyId, (int) $data['crm_lead_id']);

            $synced = $this->synchronizeFromStage($companyId, $data['stage']);



            $opportunity = CrmOpportunity::create([

                'company_id' => $companyId,

                'financial_year_id' => $activeFy->id,

                'opportunity_no' => CrmNumberService::generateOpportunityNo($companyId),

                'crm_lead_id' => $data['crm_lead_id'],

                'customer_id' => $customerId,

                'title' => $data['title'],

                'potential_value' => $data['potential_value'] ?? 0,

                'expected_closing_date' => $data['expected_closing_date'] ?? null,

                'probability' => $data['probability'] ?? 0,

                'stage' => $synced['stage'],

                'assigned_employee_id' => $data['assigned_employee_id'],

                'status' => $synced['status'],

                'remarks' => $data['remarks'] ?? null,

                'created_by' => auth()->id(),

            ]);



            $this->historyService->record(

                $companyId,

                'opportunity',

                (int) $opportunity->id,

                'Opportunity Created',

                null,

                $opportunity->stage

            );



            return $opportunity;

        });

    }



    public function update(CrmOpportunity $opportunity, array $data): CrmOpportunity

    {

        $companyId = (int) $opportunity->company_id;



        return DB::transaction(function () use ($opportunity, $companyId, $data) {

            $this->workflowService->guardEditable(

                $opportunity,

                $companyId,

                CrmConfiguration::TYPE_OPPORTUNITY_STATUS,

                'status',

                'This opportunity cannot be edited.'

            );



            $activeFy = FinancialYear::where('company_id', $companyId)->findOrFail($opportunity->financial_year_id);



            if (!empty($data['expected_closing_date'])) {

                $this->configurationService->assertDateWithinActiveFinancialYear(

                    $activeFy,

                    $data['expected_closing_date']

                );

            }



            $customerId = $this->resolveCustomerIdFromRelationship($companyId, (int) $data['crm_lead_id']);

            $synced = $this->synchronizeFromStage($companyId, $data['stage'], $opportunity->status);



            $previousStage = $opportunity->stage;

            $previousStatus = $opportunity->status;



            $opportunity->update([

                'crm_lead_id' => $data['crm_lead_id'],

                'customer_id' => $customerId,

                'title' => $data['title'],

                'potential_value' => $data['potential_value'] ?? 0,

                'expected_closing_date' => $data['expected_closing_date'] ?? null,

                'probability' => $data['probability'] ?? 0,

                'stage' => $synced['stage'],

                'status' => $synced['status'],

                'assigned_employee_id' => $data['assigned_employee_id'],

                'remarks' => $data['remarks'] ?? null,

                'updated_by' => auth()->id(),

            ]);



            $this->historyService->record($companyId, 'opportunity', (int) $opportunity->id, 'Opportunity Updated', null, null, 'Opportunity updated.');



            if ($previousStage !== $opportunity->stage) {

                $this->historyService->record($companyId, 'opportunity', (int) $opportunity->id, 'Stage Changed', $previousStage, $opportunity->stage);

            }



            if ($previousStatus !== $opportunity->status) {

                $this->historyService->record($companyId, 'opportunity', (int) $opportunity->id, 'Status Changed', $previousStatus, $opportunity->status);

            }



            return $opportunity->fresh(['customer', 'lead.customer']);

        });

    }



    public function markWon(CrmOpportunity $opportunity, string $remarks = ''): void

    {

        $companyId = (int) $opportunity->company_id;

        $synced = $this->synchronizeFromStatus($companyId, 'won', $opportunity->stage);



        $this->workflowService->applyStatus(

            $opportunity,

            $companyId,

            'opportunity',

            'status',

            CrmConfiguration::TYPE_OPPORTUNITY_STATUS,

            'won',

            'Opportunity Won',

            $remarks,

            [

                'stage' => $synced['stage'],

                'closed_by' => auth()->id(),

                'closed_at' => now(),

                'close_reason' => trim($remarks) ?: 'Opportunity won.',

            ]

        );

    }



    public function markLost(CrmOpportunity $opportunity, string $reason): void

    {

        $companyId = (int) $opportunity->company_id;

        $synced = $this->synchronizeFromStatus($companyId, 'lost', $opportunity->stage);



        $this->workflowService->applyStatus(

            $opportunity,

            $companyId,

            'opportunity',

            'status',

            CrmConfiguration::TYPE_OPPORTUNITY_STATUS,

            'lost',

            'Opportunity Lost',

            $reason,

            [

                'stage' => $synced['stage'],

                'closed_by' => auth()->id(),

                'closed_at' => now(),

                'close_reason' => trim($reason),

            ]

        );

    }



    public function close(CrmOpportunity $opportunity, string $reason): void

    {

        $this->workflowService->guardEditable(

            $opportunity,

            (int) $opportunity->company_id,

            CrmConfiguration::TYPE_OPPORTUNITY_STATUS,

            'status'

        );



        $this->workflowService->applyStatus(

            $opportunity,

            (int) $opportunity->company_id,

            'opportunity',

            'status',

            CrmConfiguration::TYPE_OPPORTUNITY_STATUS,

            'closed',

            'Opportunity Closed',

            $reason,

            [

                'closed_by' => auth()->id(),

                'closed_at' => now(),

                'close_reason' => trim($reason),

            ]

        );

    }



    public function archive(CrmOpportunity $opportunity, string $reason): void

    {

        $this->workflowService->archive(

            $opportunity,

            (int) $opportunity->company_id,

            'opportunity',

            'status',

            CrmConfiguration::TYPE_OPPORTUNITY_STATUS,

            $reason,

            'Opportunity Archived'

        );

    }



    public function cancel(CrmOpportunity $opportunity, string $reason): void

    {

        $this->workflowService->cancel(

            $opportunity,

            (int) $opportunity->company_id,

            'opportunity',

            'status',

            CrmConfiguration::TYPE_OPPORTUNITY_STATUS,

            $reason,

            'Opportunity Cancelled'

        );

    }



    public function resolveOpenStatus(int $companyId): string

    {

        return $this->configurationService->requireKey($companyId, CrmConfiguration::TYPE_OPPORTUNITY_STATUS, 'open');

    }



    public function resolveCustomerIdFromRelationship(int $companyId, int $crmLeadId): int

    {

        $lead = CrmLead::where('company_id', $companyId)->findOrFail($crmLeadId);



        if (!$lead->customer_id) {

            throw new \Exception('Selected customer relationship must be linked to an active customer.');

        }



        Customer::where('company_id', $companyId)

            ->where('status', 'active')

            ->findOrFail($lead->customer_id);



        return (int) $lead->customer_id;

    }



    /**

     * @return array{stage:string,status:string}

     */

    public function synchronizeFromStage(int $companyId, string $stage, ?string $currentStatus = null): array

    {

        $wonStage = $this->configurationService->requireKey($companyId, CrmConfiguration::TYPE_OPPORTUNITY_STAGE, 'won');

        $lostStage = $this->configurationService->requireKey($companyId, CrmConfiguration::TYPE_OPPORTUNITY_STAGE, 'lost');



        if ($stage === $wonStage) {

            return [

                'stage' => $wonStage,

                'status' => $this->configurationService->requireKey($companyId, CrmConfiguration::TYPE_OPPORTUNITY_STATUS, 'won'),

            ];

        }



        if ($stage === $lostStage) {

            return [

                'stage' => $lostStage,

                'status' => $this->configurationService->requireKey($companyId, CrmConfiguration::TYPE_OPPORTUNITY_STATUS, 'lost'),

            ];

        }



        $lockedStatuses = [

            $this->configurationService->requireKey($companyId, CrmConfiguration::TYPE_OPPORTUNITY_STATUS, 'closed'),

            $this->configurationService->requireKey($companyId, CrmConfiguration::TYPE_OPPORTUNITY_STATUS, 'archived'),

            $this->configurationService->requireKey($companyId, CrmConfiguration::TYPE_OPPORTUNITY_STATUS, 'cancelled'),

        ];



        if ($currentStatus && in_array($currentStatus, $lockedStatuses, true)) {

            return [

                'stage' => $stage,

                'status' => $currentStatus,

            ];

        }



        return [

            'stage' => $stage,

            'status' => $this->resolveOpenStatus($companyId),

        ];

    }



    /**

     * @return array{stage:string,status:string}

     */

    public function synchronizeFromStatus(int $companyId, string $statusKey, ?string $currentStage = null): array

    {

        $resolvedStatus = $this->configurationService->requireKey($companyId, CrmConfiguration::TYPE_OPPORTUNITY_STATUS, $statusKey);

        $wonStage = $this->configurationService->requireKey($companyId, CrmConfiguration::TYPE_OPPORTUNITY_STAGE, 'won');

        $lostStage = $this->configurationService->requireKey($companyId, CrmConfiguration::TYPE_OPPORTUNITY_STAGE, 'lost');

        $discoveryStage = $this->configurationService->requireKey($companyId, CrmConfiguration::TYPE_OPPORTUNITY_STAGE, 'discovery');

        $wonStatus = $this->configurationService->requireKey($companyId, CrmConfiguration::TYPE_OPPORTUNITY_STATUS, 'won');

        $lostStatus = $this->configurationService->requireKey($companyId, CrmConfiguration::TYPE_OPPORTUNITY_STATUS, 'lost');

        $openStatus = $this->resolveOpenStatus($companyId);



        if ($resolvedStatus === $wonStatus) {

            return ['stage' => $wonStage, 'status' => $wonStatus];

        }



        if ($resolvedStatus === $lostStatus) {

            return ['stage' => $lostStage, 'status' => $lostStatus];

        }



        if ($resolvedStatus === $openStatus) {

            $stage = $currentStage;



            if (in_array($currentStage, [$wonStage, $lostStage], true)) {

                $stage = $discoveryStage;

            }



            return [

                'stage' => $stage ?: $discoveryStage,

                'status' => $openStatus,

            ];

        }



        return [

            'stage' => $currentStage ?: $discoveryStage,

            'status' => $resolvedStatus,

        ];

    }

}


