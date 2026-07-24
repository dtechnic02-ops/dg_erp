<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmTask extends Model
{
    protected $fillable = [
        'company_id',
        'financial_year_id',
        'activity_no',
        'crm_lead_id',
        'crm_opportunity_id',
        'task_type',
        'task_status',
        'priority',
        'due_date',
        'assigned_employee_id',
        'remarks',
        'completed_at',
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
        'due_date' => 'date',
        'completed_at' => 'datetime',
        'archived_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(CrmLead::class, 'crm_lead_id');
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(CrmOpportunity::class, 'crm_opportunity_id');
    }

    public function assignedEmployee(): BelongsTo
    {
        return $this->belongsTo(EmployeeAccount::class, 'assigned_employee_id');
    }

    public function financialYear(): BelongsTo
    {
        return $this->belongsTo(FinancialYear::class);
    }

    public function isPending(array $pendingStatuses): bool
    {
        return in_array($this->task_status, $pendingStatuses, true);
    }

    public function isTerminal(array $terminalKeys): bool
    {
        return in_array($this->task_status, $terminalKeys, true) || $this->archived_at || $this->cancelled_at;
    }
}
