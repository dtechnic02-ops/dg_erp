<?php



namespace App\Http\Controllers\Admin;



use App\Http\Controllers\Concerns\AuthorizesAdminSubscription;

use App\Http\Controllers\Controller;

use App\Models\Company;

use App\Models\CompanySubscription;

use App\Models\SubscriptionHistory;

use App\Models\SubscriptionPayment;

use Illuminate\Support\Facades\DB;



class SubscriptionReportController extends Controller

{

    use AuthorizesAdminSubscription;



    public function index()

    {

        $this->authorizeAdminSubscriptionView();



        $activeCompanies = CompanySubscription::active()

            ->where(function ($q) {

                $q->whereNull('expiry_date')

                    ->orWhereDate('expiry_date', '>=', now()->toDateString());

            })

            ->count();



        $expiredCompanies = Company::where('status', 'expired')->count();



        $expiringSoonDays = (int) config('subscription.expiring_soon_days', 14);

        $expiringSoon = CompanySubscription::with(['company', 'plan'])

            ->active()

            ->whereNotNull('expiry_date')

            ->whereBetween('expiry_date', [now()->toDateString(), now()->addDays($expiringSoonDays)->toDateString()])

            ->orderBy('expiry_date')

            ->get();



        $renewalHistory = SubscriptionHistory::with(['company', 'companySubscription'])

            ->where('event_type', 'renewed')

            ->latest('event_at')

            ->limit(50)

            ->get();



        $revenueReports = SubscriptionPayment::approved()

            ->select(

                DB::raw('DATE(COALESCE(approved_at, payment_date)) as report_date'),

                DB::raw('SUM(amount) as total_amount'),

                DB::raw('COUNT(*) as payment_count')

            )

            ->groupBy('report_date')

            ->orderByDesc('report_date')

            ->limit(30)

            ->get();



        return view('admin.subscription-reports.index', compact(

            'activeCompanies',

            'expiredCompanies',

            'expiringSoon',

            'renewalHistory',

            'revenueReports'

        ));

    }

}

