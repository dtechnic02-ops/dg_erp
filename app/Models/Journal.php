<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Journal extends Model
{
    public const STATUS_CANCELLED = 0;

    public const STATUS_POSTED = 1;

    public const STATUS_ACTIVE = self::STATUS_POSTED;

    public const STATUS_REVERSED = 2;

    protected $fillable = [
        'company_id',
        'financial_year_id',
        'journal_no',
        'journal_date',
        'reference_no',
        'total_amount',
        'attachment',
        'note',
        'created_by',
        'updated_by',
        'posted_by',
        'posted_at',
        'cancelled_by',
        'cancelled_date',
        'cancel_reason',
        'reversed_by',
        'reversed_at',
        'reversal_of_journal_id',
        'status',
    ];

    protected $casts = [
        'journal_date'   => 'date',
        'cancelled_date' => 'date',
        'posted_at'      => 'datetime',
        'reversed_at'    => 'datetime',
        'total_amount'   => 'decimal:2',
        'status'         => 'integer',
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
        return (int) $this->status === self::STATUS_ACTIVE;
    }

    public function isPosted(): bool
    {
        return (int) $this->status === self::STATUS_POSTED;
    }
}
