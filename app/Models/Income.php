<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Income extends Model
{
    public const STATUS_CANCELLED = 0;

    public const STATUS_ACTIVE = 1;

    protected $fillable = [
        'company_id',
        'financial_year_id',
        'income_category_id',
        'income_no',
        'title',
        'account_id',
        'amount',
        'income_date',
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
        'income_date'    => 'date',
        'cancelled_date' => 'date',
        'amount'         => 'decimal:2',
        'status'         => 'integer',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function category()
    {
        return $this->belongsTo(IncomeCategory::class, 'income_category_id');
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
}
