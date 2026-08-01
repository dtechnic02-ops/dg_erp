<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpeningBalanceLine extends Model
{
    protected $fillable = [
        'opening_balance_id', 'chart_account_id', 'operational_account_id', 'line_number', 'debit',
        'credit', 'subledger_type', 'subledger_id', 'description', 'line_reference', 'currency',
        'exchange_rate', 'base_debit', 'base_credit',
    ];

    protected function casts(): array
    {
        return ['debit' => 'decimal:4', 'credit' => 'decimal:4', 'base_debit' => 'decimal:4',
            'base_credit' => 'decimal:4', 'exchange_rate' => 'decimal:8'];
    }

    public function openingBalance() { return $this->belongsTo(OpeningBalance::class); }
    public function chartAccount() { return $this->belongsTo(ChartAccount::class); }
    public function operationalAccount() { return $this->belongsTo(Account::class); }
}
