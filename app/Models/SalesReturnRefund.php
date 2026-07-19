<?php

namespace App\Models;

use App\Services\SalesReturnSyncService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesReturnRefund extends Model
{
    use SoftDeletes;

    public const STATUS_CANCELLED = 0;

    public const STATUS_ACTIVE = 1;

    public const STATUS_LEGACY_ACTIVE = 3;

    protected static function booted(): void
    {
        static::deleted(function (SalesReturnRefund $refund) {
            if ($refund->sales_return_id) {
                SalesReturnSyncService::sync($refund->sales_return_id);
            }
        });
    }

    public static function activeStatusValues(): array
    {
        return [
            self::STATUS_ACTIVE,
            self::STATUS_LEGACY_ACTIVE,
        ];
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', self::activeStatusValues());
    }

    public function isActive(): bool
    {
        return in_array((int) $this->status, self::activeStatusValues(), true);
    }

    protected $fillable = [
        'company_id',
        'financial_year_id',
        'sales_return_id',
        'customer_id',
        'account_id',
        'refund_no',
        'refund_date',
        'refund_amount',
        'adjust_amount',
        'cash_amount',
        'reference_no',
        'attachment',
        'note',
        'created_by',
        'updated_by',
        'deleted_by',
        'status',
    ];

    protected $casts = [
        'refund_date'    => 'date',
        'refund_amount'  => 'decimal:2',
        'adjust_amount'  => 'decimal:2',
        'cash_amount'    => 'decimal:2',
        'status'         => 'integer',
    ];

    public function salesReturn()
    {
        return $this->belongsTo(SalesReturn::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function financialYear()
    {
        return $this->belongsTo(FinancialYear::class);
    }

    public function adjustments()
    {
        return $this->hasMany(
            SalesReturnRefundAdjustment::class,
            'sales_return_refund_id'
        );
    }
}
