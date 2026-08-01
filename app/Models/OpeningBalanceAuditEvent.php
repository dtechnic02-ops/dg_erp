<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpeningBalanceAuditEvent extends Model
{
    protected $fillable = [
        'company_id', 'financial_year_id', 'opening_balance_id', 'event', 'previous_status',
        'new_status', 'user_id', 'reason', 'metadata', 'occurred_at',
    ];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'occurred_at' => 'datetime'];
    }

    public function openingBalance() { return $this->belongsTo(OpeningBalance::class); }
    public function user() { return $this->belongsTo(User::class); }
}
