<?php



namespace App\Services;



use App\Models\CrmConfiguration;

use App\Models\CrmFollowUp;

use App\Models\CrmLead;

use App\Models\CrmMeeting;

use App\Models\CrmOpportunity;

use App\Models\CrmTask;

use App\Services\Concerns\GuardsSubscriptionModule;

use Carbon\Carbon;

use Illuminate\Support\Facades\DB;



class CrmDashboardService

{

    use GuardsSubscriptionModule;

    public function __construct(

        private CrmConfigurationService $configurationService

    ) {

    }



    public function summary(int $companyId): array

    {

        $this->assertSubscriptionModule($companyId, 'crm');

        $this->configurationService->ensureDefaults($companyId);



        $today = Carbon::today()->toDateString();

        $monthStart = Carbon::now()->startOfMonth()->toDateString();

        $monthEnd = Carbon::now()->endOfMonth()->toDateString();



        $relationshipActive = $this->configurationService->activeKeys($companyId, CrmConfiguration::TYPE_LEAD_STATUS);

        $followUpTerminal = $this->configurationService->terminalKeys($companyId, CrmConfiguration::TYPE_FOLLOW_UP_STATUS);

        $taskPending = $this->configurationService->activeKeys($companyId, CrmConfiguration::TYPE_TASK_STATUS);

        $meetingScheduled = $this->configurationService->requireKey($companyId, CrmConfiguration::TYPE_MEETING_STATUS, 'scheduled');

        $wonKey = $this->configurationService->requireKey($companyId, CrmConfiguration::TYPE_OPPORTUNITY_STATUS, 'won');

        $lostKey = $this->configurationService->requireKey($companyId, CrmConfiguration::TYPE_OPPORTUNITY_STATUS, 'lost');



        $monthlyRelationships = CrmLead::where('company_id', $companyId)

            ->whereBetween('lead_date', [$monthStart, $monthEnd])

            ->whereNull('cancelled_at')

            ->count();



        return [

            'todaysFollowUps' => CrmFollowUp::where('company_id', $companyId)

                ->whereDate('follow_up_date', $today)

                ->whereNotIn('status', $followUpTerminal)

                ->count(),

            'pendingTasks' => CrmTask::where('company_id', $companyId)

                ->whereIn('task_status', $taskPending)

                ->count(),

            'upcomingMeetings' => CrmMeeting::where('company_id', $companyId)

                ->whereDate('meeting_date', '>=', $today)

                ->where('status', $meetingScheduled)

                ->count(),

            'wonOpportunities' => CrmOpportunity::where('company_id', $companyId)

                ->where('status', $wonKey)

                ->count(),

            'lostOpportunities' => CrmOpportunity::where('company_id', $companyId)

                ->where('status', $lostKey)

                ->count(),

            'monthlyRelationships' => $monthlyRelationships,

            'activeRelationships' => CrmLead::where('company_id', $companyId)

                ->whereIn('status', $relationshipActive)

                ->whereNull('cancelled_at')

                ->count(),

            'employeePerformance' => CrmLead::query()

                ->with('assignedEmployee')

                ->select('assigned_employee_id', DB::raw('COUNT(*) as total_relationships'))

                ->where('company_id', $companyId)

                ->whereBetween('lead_date', [$monthStart, $monthEnd])

                ->whereNull('cancelled_at')

                ->groupBy('assigned_employee_id')

                ->orderByDesc('total_relationships')

                ->limit(5)

                ->get(),

            'todayFollowUpList' => CrmFollowUp::with(['lead.customer', 'assignedEmployee'])

                ->where('company_id', $companyId)

                ->whereDate('follow_up_date', $today)

                ->orderBy('id')

                ->limit(10)

                ->get(),

            'pendingTaskList' => CrmTask::with(['lead.customer', 'assignedEmployee'])

                ->where('company_id', $companyId)

                ->whereIn('task_status', $taskPending)

                ->orderBy('due_date')

                ->limit(10)

                ->get(),

        ];

    }

}


