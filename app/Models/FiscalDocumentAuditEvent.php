<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class FiscalDocumentAuditEvent extends Model
{
    protected $fillable = ['company_id', 'document_type', 'document_id', 'document_number', 'event_type', 'actor_id', 'event_at', 'metadata', 'deduplication_key'];

    protected function casts(): array
    {
        return ['event_at' => 'datetime', 'metadata' => 'array'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Fiscal audit events are append-only.'));
        static::deleting(fn () => throw new LogicException('Fiscal audit events are append-only.'));
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
