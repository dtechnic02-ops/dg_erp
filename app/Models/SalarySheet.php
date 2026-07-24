<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalarySheet extends Model
{
    public const STATUS_UNPAID = 'unpaid';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_PAID = 'paid';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'company_id',
        'financial_year_id',
        'employee_id',
        'salary_month',
        'basic_salary',
        'working_days',
        'present_days',
        'absent_days',
        'allowance',
        'bonus',
        'overtime_amount',
        'deduction',
        'net_salary',
        'paid_amount',
        'due_amount',
        'status',
        'note',
        'created_by',
        'updated_by',
        'cancelled_by',
        'cancelled_at',
        'cancel_reason',
    ];

    protected $casts = [
        'cancelled_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeAccount::class, 'employee_id');
    }

    public function financialYear(): BelongsTo
    {
        return $this->belongsTo(FinancialYear::class, 'financial_year_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function payments(): HasMany
    {
        return $this->employeePayments();
    }

    public function employeePayments(): HasMany
    {
        return $this->hasMany(EmployeePayment::class, 'salary_sheet_id');
    }

    public function activeEmployeePayments(): HasMany
    {
        return $this->employeePayments()->where('status', EmployeePayment::STATUS_ACTIVE);
    }

    public function hasActiveEmployeePayments(): bool
    {
        if ($this->relationLoaded('activeEmployeePayments')) {
            return $this->activeEmployeePayments->isNotEmpty();
        }

        if ($this->id) {
            return $this->activeEmployeePayments()->exists();
        }

        return false;
    }

    public function isUnpaid(): bool
    {
        return $this->status === self::STATUS_UNPAID;
    }

    public function isPartial(): bool
    {
        return $this->status === self::STATUS_PARTIAL;
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isEditable(): bool
    {
        return $this->isUnpaid();
    }

    public function isCancellable(): bool
    {
        return !$this->isCancelled()
            && !$this->isPaid()
            && !$this->isPartial()
            && !$this->hasActiveEmployeePayments();
    }

    public function canAcceptPayment(): bool
    {
        return !$this->isCancelled()
            && !$this->isPaid()
            && (float) $this->due_amount > 0;
    }
}
