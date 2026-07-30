<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingEntryLine extends Model
{
    protected $table = 'accounting_entry_lines';

    protected $fillable = [
        'accounting_entry_id',
        'chart_account_id',
        'operational_account_id',
        'line_number',
        'description',
        'debit',
        'credit',
        'subledger_type',
        'subledger_id',
    ];

    protected function casts(): array
    {
        return [
            'debit' => 'decimal:4',
            'credit' => 'decimal:4',
            'line_number' => 'integer',
            'subledger_id' => 'integer',
        ];
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(AccountingEntry::class, 'accounting_entry_id');
    }

    public function chartAccount(): BelongsTo
    {
        return $this->belongsTo(ChartAccount::class);
    }

    public function operationalAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'operational_account_id');
    }

    public function scopeForChartAccount(Builder $query, int $chartAccountId): Builder
    {
        return $query->where('chart_account_id', $chartAccountId);
    }

    public function scopeForOperationalAccount(Builder $query, int $operationalAccountId): Builder
    {
        return $query->where('operational_account_id', $operationalAccountId);
    }

    public function scopeForSubledger(Builder $query, string $subledgerType, int $subledgerId): Builder
    {
        return $query
            ->where('subledger_type', $subledgerType)
            ->where('subledger_id', $subledgerId);
    }

    public function scopeDebitLines(Builder $query): Builder
    {
        return $query->where('debit', '>', 0);
    }

    public function scopeCreditLines(Builder $query): Builder
    {
        return $query->where('credit', '>', 0);
    }
}
