<?php



namespace App\Http\Middleware;



use App\Services\SubscriptionService;

use Closure;

use Illuminate\Http\Request;

use Symfony\Component\HttpFoundation\Response;



class CheckSubscription

{

    public function __construct(private SubscriptionService $subscriptionService)

    {

    }



    public function handle(Request $request, Closure $next): Response

    {

        $user = auth()->user();

        $user?->loadMissing('role');

        if (! $user || ! $user->role?->resolvesToCompanyDashboard()) {

            return $next($request);

        }



        $company = $user->company;



        if (! $company) {

            abort(403, 'Company not found.');

        }



        if ($company->status === 'blocked') {

            abort(403, 'Company is blocked.');

        }



        $routeName = $request->route()?->getName();

        $exemptRoutes = [

            'company.profile',

            'company.profile.update',

            'company.subscription.index',

            'company.subscription.payment.store',

            'logout',

        ];



        if (in_array($routeName, $exemptRoutes, true)) {

            return $next($request);

        }



        if (! $this->subscriptionService->isSubscriptionOperational($company)) {

            if ($request->expectsJson()) {

                return response()->json(['message' => 'Subscription expired. Please renew your plan.'], 403);

            }



            return redirect()

                ->route('company.subscription.index')

                ->with('error', 'Subscription expired. Please renew your plan.');

        }



        $module = $this->subscriptionService->resolveRouteModule($routeName);



        if ($module) {
            $this->subscriptionService->assertModuleAccess($company, $module);
        }



        return $next($request);

    }

}

