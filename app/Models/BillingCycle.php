<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillingCycle extends Model
{
    protected $fillable = [
        'code',
        'name',
        'duration_days',
        'is_lifetime',
        'is_active',
        'sort_order',
        'created_by',
        'updated_by',
        'cancelled_at',
        'cancelled_by',
        'cancel_reason',
    ];

    protected $casts = [
        'duration_days' => 'integer',
        'is_lifetime' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'cancelled_at' => 'datetime',
    ];

    public function billingOptions(): HasMany
    {
        return $this->hasMany(SubscriptionPlanBillingOption::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
