<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountingPeriodLock extends Model
{
    protected $fillable = ['company_id', 'financial_year_id', 'date_from', 'date_to', 'is_locked', 'reason', 'locked_by'];

    protected function casts(): array
    {
        return ['date_from' => 'date', 'date_to' => 'date', 'is_locked' => 'boolean'];
    }
}
