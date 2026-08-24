<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JournalAuditEvent extends Model
{
    public $timestamps = false;

    protected static function booted(): void
    {
        static::updating(fn () => throw new \RuntimeException('Journal audit events are immutable.'));
        static::deleting(fn () => throw new \RuntimeException('Journal audit events are immutable.'));
    }

    protected $fillable = [
        'company_id', 'financial_year_id', 'journal_id', 'event',
        'previous_status', 'new_status', 'actor_id', 'event_at', 'reason', 'metadata',
    ];

    protected function casts(): array
    {
        return ['event_at' => 'datetime', 'metadata' => 'array'];
    }

    public function journal()
    {
        return $this->belongsTo(Journal::class);
    }
}
