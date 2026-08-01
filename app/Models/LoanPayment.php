<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LoanPayment extends Model
{
    use SoftDeletes;

    public const STATUS_CANCELLED = 0;

    public const STATUS_ACTIVE = 1;
    public const SOURCE_ACCOUNT = 'account';
    public const SOURCE_SAVING = 'saving';
    public const EVENT_CREATED = 'created';

    protected $fillable = [
        'company_id',
        'financial_year_id',
        'loan_account_id',
        'account_id',
        'payment_date',
        'next_payment_date',
        'principal_amount',
        'interest_amount',
        'fine_amount',
        'saving_amount',
        'total_amount',
        'remaining_principal',
        'reference_no',
        'request_key',
        'attachment',
        'note',
        'created_by',
        'updated_by',
        'cancelled_by',
        'cancelled_date',
        'cancel_reason',
        'status',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'next_payment_date' => 'date',
        'cancelled_date' => 'date',
        'principal_amount' => 'decimal:2',
        'interest_amount' => 'decimal:2',
        'fine_amount' => 'decimal:2',
        'saving_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'remaining_principal' => 'decimal:2',
        'status' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function isActive(): bool
    {
        return (int) $this->status === self::STATUS_ACTIVE;
    }

    public function loanAccount()
    {
        return $this->belongsTo(LoanAccount::class);
    }

    public function financialYear()
    {
        return $this->belongsTo(FinancialYear::class, 'financial_year_id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function savingLedgers()
    {
        return $this->hasMany(LoanSavingLedger::class);
    }

    public function savingLedger()
    {
        return $this->hasOne(LoanSavingLedger::class);
    }

    public function isPaidFromSaving(): bool
    {
        if ($this->relationLoaded('savingLedgers')) {
            return $this->savingLedgers
                ->where('status', 1)
                ->contains('type', LoanSavingLedger::TYPE_LOAN_SETTLEMENT);
        }

        return $this->savingLedgers()
            ->where('status', 1)
            ->where('type', LoanSavingLedger::TYPE_LOAN_SETTLEMENT)
            ->exists();
    }
}
