<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmLead extends Model
{
    protected $fillable = [
        'company_id',
        'financial_year_id',
        'lead_no',
        'customer_id',
        'assigned_employee_id',
        'status',
        'priority',
        'expected_value',
        'lead_date',
        'remarks',
        'closed_by',
        'closed_at',
        'close_reason',
        'archived_by',
        'archived_at',
        'archive_reason',
        'cancelled_by',
        'cancelled_at',
        'cancel_reason',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'lead_date' => 'date',
        'expected_value' => 'decimal:2',
        'converted_at' => 'datetime',
        'closed_at' => 'datetime',
        'archived_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function financialYear(): BelongsTo
    {
        return $this->belongsTo(FinancialYear::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignedEmployee(): BelongsTo
    {
        return $this->belongsTo(EmployeeAccount::class, 'assigned_employee_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(CrmOpportunity::class, 'crm_lead_id');
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(CrmFollowUp::class, 'crm_lead_id');
    }

    public function meetings(): HasMany
    {
        return $this->hasMany(CrmMeeting::class, 'crm_lead_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(CrmTask::class, 'crm_lead_id');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(CrmContact::class, 'crm_lead_id');
    }

    public function isActive(array $terminalStatusKeys): bool
    {
        return !in_array($this->status, $terminalStatusKeys, true)
            && !$this->archived_at
            && !$this->cancelled_at;
    }

    public function isEditable(array $terminalStatusKeys): bool
    {
        return $this->isActive($terminalStatusKeys);
    }
}
