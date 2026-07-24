<?php

namespace App\Models;

/** @deprecated Use CompanySubscription instead */
class Subscription extends CompanySubscription
{
    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }
}
