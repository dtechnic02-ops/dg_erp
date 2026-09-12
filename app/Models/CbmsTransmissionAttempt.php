<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class CbmsTransmissionAttempt extends Model
{
    protected $fillable = [
        'company_id', 'cbms_transmission_id', 'attempt_number', 'attempted_at', 'finished_at',
        'transport_classification', 'http_status', 'response_code', 'parser_classification',
        'response_excerpt_redacted', 'payload_hash', 'is_realtime', 'result_status',
    ];

    protected function casts(): array
    {
        return [
            'attempt_number' => 'integer', 'attempted_at' => 'datetime', 'finished_at' => 'datetime',
            'http_status' => 'integer', 'is_realtime' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('CBMS transmission attempt evidence is append-only.'));
        static::deleting(fn () => throw new LogicException('CBMS transmission attempt evidence is append-only.'));
    }

    public function company() { return $this->belongsTo(Company::class); }
    public function transmission() { return $this->belongsTo(CbmsTransmission::class, 'cbms_transmission_id'); }
}
