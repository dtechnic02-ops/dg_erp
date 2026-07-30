<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AccountingEntry extends Model
{
    protected $table = 'accounting_entries';

    protected $fillable = [
        'company_id',
        'entry_number',
        'entry_date',
        'reference_number',
        'source_module',
        'source_type',
        'source_id',
        'source_event',
        'source_key',
        'description',
        'status',
        'reversal_of_id',
        'posted_at',
        'posted_by',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'posted_at' => 'datetime',
            'source_id' => 'integer',
            'reversal_of_id' => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(AccountingEntryLine::class);
    }

    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_of_id');
    }

    public function reversalEntry(): HasOne
    {
        return $this->hasOne(self::class, 'reversal_of_id');
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    public function scopePosted(Builder $query): Builder
    {
        return $query->where('status', 'posted');
    }

    public function scopeReversed(Builder $query): Builder
    {
        return $query->where('status', 'reversed');
    }

    public function scopeBetweenDates(Builder $query, $from, $to): Builder
    {
        return $query->whereBetween('entry_date', [$from, $to]);
    }

    public function scopeFromSource(
        Builder $query,
        string $sourceModule,
        ?string $sourceType = null,
        ?int $sourceId = null
    ): Builder {
        return $query
            ->where('source_module', $sourceModule)
            ->when($sourceType, fn (Builder $sourceQuery) => $sourceQuery->where('source_type', $sourceType))
            ->when($sourceId, fn (Builder $sourceQuery) => $sourceQuery->where('source_id', $sourceId));
    }

    public function scopeOrderByEntryDate(Builder $query, string $direction = 'desc'): Builder
    {
        return $query->orderBy('entry_date', $direction)->orderBy('id', $direction);
    }
}
