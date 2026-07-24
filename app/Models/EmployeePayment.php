<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeePayment extends Model
{
    public const STATUS_CANCELLED = 0;

    public const STATUS_ACTIVE = 1;

    protected $table = 'employee_payments';

    protected $fillable = [
        'company_id',
        'financial_year_id',
        'salary_sheet_id',
        'employee_account_id',
        'voucher_no',
        'payment_date',
        'salary_year',
        'salary_month',
        'account_id',
        'amount',
        'attachment',
        'note',
        'created_by',
        'updated_by',
        'cancelled_by',
        'cancelled_at',
        'cancel_reason',
        'status',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'cancelled_at' => 'datetime',
    ];

    public function salarySheet(): BelongsTo
    {
        return $this->belongsTo(SalarySheet::class, 'salary_sheet_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeAccount::class, 'employee_account_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
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

    public function financialYear(): BelongsTo
    {
        return $this->belongsTo(FinancialYear::class, 'financial_year_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function isActive(): bool
    {
        return (int) $this->status === self::STATUS_ACTIVE;
    }

    public function isCancelled(): bool
    {
        return (int) $this->status === self::STATUS_CANCELLED;
    }
}
