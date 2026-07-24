<?php



namespace App\Services;



use App\Models\CrmConfiguration;

use App\Models\CrmLead;

use App\Services\Concerns\GuardsSubscriptionModule;

use Illuminate\Support\Facades\DB;



class CrmLeadService

{

    use GuardsSubscriptionModule;

    public function __construct(

        private CrmWorkflowService $workflowService

    ) {

    }



    public function close(CrmLead $lead, int $companyId, string $reason): void

    {

        $this->assertSubscriptionModule($companyId, 'crm');

        DB::transaction(function () use ($lead, $companyId, $reason) {

            $lead = CrmLead::where('company_id', $companyId)->lockForUpdate()->findOrFail($lead->id);



            $this->workflowService->guardEditable(

                $lead,

                $companyId,

                CrmConfiguration::TYPE_LEAD_STATUS,

                'status',

                'This relationship cannot be closed.'

            );



            $this->workflowService->applyStatus(

                $lead,

                $companyId,

                'lead',

                'status',

                CrmConfiguration::TYPE_LEAD_STATUS,

                'closed',

                'Relationship Closed',

                $reason,

                [

                    'closed_by' => auth()->id(),

                    'closed_at' => now(),

                    'close_reason' => trim($reason),

                ]

            );

        });

    }

}


