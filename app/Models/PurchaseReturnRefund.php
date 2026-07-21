<?php

namespace App\Models;

use App\Services\PurchaseReturnSyncService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseReturnRefund extends Model
{
    use SoftDeletes;

    public const STATUS_CANCELLED = 0;

    public const STATUS_ACTIVE = 1;

    public const STATUS_LEGACY_ACTIVE = 3;

    protected static function booted(): void
    {
        static::deleted(function (PurchaseReturnRefund $refund) {
            if ($refund->purchase_return_id) {
                PurchaseReturnSyncService::sync($refund->purchase_return_id);
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
        'purchase_return_id',
        'supplier_id',
        'account_id',
        'refund_no',
        'refund_date',
        'refund_amount',
        'amount',
        'adjust_amount',
        'cash_amount',
        'payment_method',
        'reference_no',
        'attachment',
        'note',
        'created_by',
        'updated_by',
        'deleted_by',
        'status',
    ];

    protected $casts = [
        'refund_date'   => 'date',
        'refund_amount' => 'decimal:2',
        'amount'        => 'decimal:2',
        'adjust_amount' => 'decimal:2',
        'cash_amount'   => 'decimal:2',
        'status'        => 'integer',
    ];

    public function purchaseReturn()
    {
        return $this->belongsTo(PurchaseReturn::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function financialYear()
    {
        return $this->belongsTo(FinancialYear::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function adjustments()
    {
        return $this->hasMany(
            PurchaseReturnRefundAdjustment::class,
            'purchase_return_refund_id'
        );
    }
}
