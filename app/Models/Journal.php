<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Journal extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_POSTED = 'posted';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REVERSED = 'reversed';
    public const STATUS_ACTIVE = self::STATUS_POSTED;

    public const TYPE_GENERAL = 'general';
    public const TYPE_ADJUSTMENT = 'adjustment';
    public const TYPE_OPENING = 'opening';
    public const TYPE_CLOSING = 'closing';
    public const TYPE_CORRECTION = 'correction';
    public const TYPE_REVERSAL = 'reversal';

    public const TYPES = [self::TYPE_GENERAL, self::TYPE_ADJUSTMENT, self::TYPE_OPENING, self::TYPE_CLOSING, self::TYPE_CORRECTION, self::TYPE_REVERSAL];
    public const STATUSES = [self::STATUS_DRAFT, self::STATUS_SUBMITTED, self::STATUS_APPROVED, self::STATUS_REJECTED, self::STATUS_POSTED, self::STATUS_CANCELLED, self::STATUS_REVERSED];

    protected $fillable = [
        'company_id',
        'financial_year_id',
        'journal_no',
        'journal_date',
        'journal_type',
        'description',
        'remarks',
        'reference_no',
        'source_module',
        'source_type',
        'source_id',
        'source_key',
        'request_key',
        'total_amount',
        'attachment',
        'note',
        'created_by',
        'updated_by',
        'submitted_by',
        'submitted_at',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'posted_by',
        'posted_at',
        'cancelled_by',
        'cancelled_date',
        'cancel_reason',
        'cancellation_reason',
        'cancelled_at',
        'reversed_by',
        'reversed_at',
        'reversal_reason',
        'reversal_of_journal_id',
        'is_locked',
        'locked_by',
        'locked_at',
        'lock_reason',
        'unlocked_by',
        'unlocked_at',
        'unlock_reason',
        'legacy_classification',
        'legacy_classification_reason',
        'status',
    ];

    protected $casts = [
        'journal_date'   => 'date',
        'cancelled_date' => 'date',
        'posted_at'      => 'datetime',
        'reversed_at'    => 'datetime',
        'submitted_at'   => 'datetime',
        'approved_at'    => 'datetime',
        'rejected_at'    => 'datetime',
        'cancelled_at'   => 'datetime',
        'locked_at'      => 'datetime',
        'unlocked_at'    => 'datetime',
        'total_amount'   => 'decimal:4',
        'is_locked'      => 'boolean',
    ];

    public function items()
    {
        return $this->hasMany(JournalItem::class);
    }

    public function financialYear()
    {
        return $this->belongsTo(FinancialYear::class, 'financial_year_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedByUser()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function cancelledByUser()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function isActive(): bool
    {
        return $this->isPosted();
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function auditEvents()
    {
        return $this->hasMany(JournalAuditEvent::class)->orderBy('event_at');
    }

    public function isPosted(): bool
    {
        return $this->status === self::STATUS_POSTED || (string) $this->status === '1';
    }
}
