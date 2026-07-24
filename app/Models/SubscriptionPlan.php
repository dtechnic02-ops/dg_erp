<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'staff_limit',
        'hidden_modules',
        'is_active',
        'sort_order',
        'created_by',
        'updated_by',
        'cancelled_at',
        'cancelled_by',
        'cancel_reason',
    ];

    protected $casts = [
        'staff_limit' => 'integer',
        'hidden_modules' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'cancelled_at' => 'datetime',
    ];

    public function billingOptions(): HasMany
    {
        return $this->hasMany(SubscriptionPlanBillingOption::class);
    }

    public function companySubscriptions(): HasMany
    {
        return $this->hasMany(CompanySubscription::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
