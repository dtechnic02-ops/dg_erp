<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmMeeting extends Model
{
    protected $fillable = [
        'company_id',
        'financial_year_id',
        'activity_no',
        'crm_lead_id',
        'crm_opportunity_id',
        'meeting_date',
        'meeting_time',
        'assigned_employee_id',
        'location',
        'status',
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
        'meeting_date' => 'date',
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

    public function isTerminal(array $terminalKeys): bool
    {
        return in_array($this->status, $terminalKeys, true) || $this->archived_at || $this->cancelled_at;
    }
}
