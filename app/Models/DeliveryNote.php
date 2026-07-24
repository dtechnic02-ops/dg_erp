<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryNote extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_READY = 'ready';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'company_id',
        'financial_year_id',
        'delivery_no',
        'customer_id',
        'employee_id',
        'sales_invoice_id',
        'delivery_date',
        'status',
        'remarks',
        'cancel_reason',
        'pdf_path',
        'created_by',
        'updated_by',
        'cancelled_by',
        'cancelled_at',
        'completed_by',
        'completed_at',
    ];

    protected $casts = [
        'delivery_date' => 'date',
        'cancelled_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function financialYear(): BelongsTo
    {
        return $this->belongsTo(FinancialYear::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeAccount::class, 'employee_id');
    }

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(DeliveryNoteItem::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(DeliveryStatusHistory::class);
    }

    public function signature(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(DeliverySignature::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(DeliveryAttachment::class);
    }

    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
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

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_READY], true);
    }

    public function isCancellable(): bool
    {
        return in_array($this->status, [self::STATUS_READY, self::STATUS_DRAFT], true);
    }

    public function isProcessable(): bool
    {
        return $this->status === self::STATUS_READY;
    }

    public function isCompleted(): bool
    {
        return in_array($this->status, [self::STATUS_DELIVERED, self::STATUS_PARTIAL], true);
    }

    public function isLocked(): bool
    {
        return $this->isCompleted() || $this->isCancelled();
    }

    public static function statusLabel(string $status): string
    {
        return ucfirst($status);
    }
}
