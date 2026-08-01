<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpeningBalance extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_POSTED = 'posted';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REVERSED = 'reversed';

    public const TYPES = ['initial', 'financial_year', 'new_account', 'migration', 'correction'];

    protected $fillable = [
        'company_id', 'financial_year_id', 'business_date', 'type', 'reference_number', 'remarks',
        'status', 'active_key', 'request_key', 'journal_id', 'accounting_entry_id', 'created_by', 'submitted_by',
        'approved_by', 'posted_by', 'cancelled_by', 'reversed_by', 'locked_by', 'submitted_at',
        'approved_at', 'posted_at', 'cancelled_at', 'reversed_at', 'locked_at', 'cancellation_reason',
        'reversal_reason', 'lock_reason', 'is_locked',
    ];

    protected function casts(): array
    {
        return [
            'business_date' => 'date', 'is_locked' => 'boolean', 'submitted_at' => 'datetime',
            'approved_at' => 'datetime', 'posted_at' => 'datetime', 'cancelled_at' => 'datetime',
            'reversed_at' => 'datetime', 'locked_at' => 'datetime',
        ];
    }

    public function lines() { return $this->hasMany(OpeningBalanceLine::class); }
    public function audits() { return $this->hasMany(OpeningBalanceAuditEvent::class); }
    public function financialYear() { return $this->belongsTo(FinancialYear::class); }
    public function journal() { return $this->belongsTo(Journal::class); }
    public function accountingEntry() { return $this->belongsTo(AccountingEntry::class); }
}
