<?php



namespace App\Http\Controllers\Concerns;



trait AuthorizesAdminSubscription

{

    protected function authorizeAdminSubscriptionView(): void

    {

        abort_unless(auth()->user()?->hasPermission('view_subscription_module'), 403, 'You do not have permission to view subscription data.');

    }



    protected function authorizeAdminSubscriptionManage(): void

    {

        abort_unless(auth()->user()?->hasPermission('manage_subscription_module'), 403, 'You do not have permission to manage subscriptions.');

    }

}

