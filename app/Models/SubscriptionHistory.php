<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'company_subscription_id',
        'subscription_payment_id',
        'event_type',
        'subscription_type_before',
        'subscription_type_after',
        'subscription_plan_id_before',
        'subscription_plan_id_after',
        'billing_cycle_id_before',
        'billing_cycle_id_after',
        'status_before',
        'status_after',
        'start_date_before',
        'start_date_after',
        'expiry_date_before',
        'expiry_date_after',
        'staff_limit_before',
        'staff_limit_after',
        'hidden_modules_before',
        'hidden_modules_after',
        'performed_by',
        'notes',
        'event_at',
        'created_at',
    ];

    protected $casts = [
        'start_date_before' => 'date',
        'start_date_after' => 'date',
        'expiry_date_before' => 'date',
        'expiry_date_after' => 'date',
        'hidden_modules_before' => 'array',
        'hidden_modules_after' => 'array',
        'event_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function companySubscription(): BelongsTo
    {
        return $this->belongsTo(CompanySubscription::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPayment::class, 'subscription_payment_id');
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
