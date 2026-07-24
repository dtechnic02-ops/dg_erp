<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmContact extends Model
{
    protected $fillable = [
        'company_id',
        'financial_year_id',
        'contact_no',
        'crm_lead_id',
        'customer_id',
        'name',
        'designation',
        'department',
        'mobile',
        'phone',
        'email',
        'assigned_employee_id',
        'status',
        'priority',
        'contact_date',
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
        'contact_date' => 'date',
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

    public function lead(): BelongsTo
    {
        return $this->belongsTo(CrmLead::class, 'crm_lead_id');
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

    public function notes(): HasMany
    {
        return $this->hasMany(CrmNote::class, 'entity_id')
            ->where('entity_type', 'contact');
    }

    public function isTerminal(array $terminalKeys): bool
    {
        return in_array($this->status, $terminalKeys, true)
            || $this->archived_at
            || $this->cancelled_at;
    }

    public function isEditable(array $terminalKeys): bool
    {
        return !$this->isTerminal($terminalKeys);
    }
}
