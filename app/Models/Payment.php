<?php

namespace App\Models;

/** @deprecated Use SubscriptionPayment instead */
class Payment extends SubscriptionPayment
{
    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }
}
