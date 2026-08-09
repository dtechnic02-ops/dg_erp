<?php



namespace App\Http\Controllers\Concerns;



trait AuthorizesAdminSubscription

{

    protected function authorizeAdminSubscriptionView(): void

    {

        abort_unless(app(\App\Services\PlatformAuthorizationService::class)->can(auth()->user(), 'platform_subscriptions_view'), 403, 'You do not have permission to view subscription data.');

    }



    protected function authorizeAdminSubscriptionManage(): void

    {

        abort_unless(app(\App\Services\PlatformAuthorizationService::class)->can(auth()->user(), 'platform_subscriptions_manage'), 403, 'You do not have permission to manage subscriptions.');

    }

}
