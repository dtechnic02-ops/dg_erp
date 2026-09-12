<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CbmsTransmission extends Model
{
    public const ENDPOINT_BILL = 'bill';
    public const ENDPOINT_BILL_RETURN = 'bill_return';
    public const STATUS_PENDING = 'pending';
    public const STATUS_NOT_READY = 'not_ready';
    public const STATUS_QUEUED = 'queued';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_DUPLICATE_REQUIRES_RECONCILIATION = 'duplicate_requires_reconciliation';
    public const STATUS_RETRYABLE_FAILURE = 'retryable_failure';
    public const STATUS_PERMANENT_FAILURE = 'permanent_failure';

    protected $fillable = ['company_id', 'transmittable_type', 'transmittable_id', 'endpoint_type', 'status', 'attempt_count', 'last_attempted_at', 'submitted_at', 'response_code', 'response_category', 'payload_hash', 'response_body_redacted'];

    protected function casts(): array
    {
        return ['attempt_count' => 'integer', 'last_attempted_at' => 'datetime', 'submitted_at' => 'datetime', 'response_body_redacted' => 'array'];
    }

    public function company() { return $this->belongsTo(Company::class); }
    public function transmittable() { return $this->morphTo(); }
    public function attempts() { return $this->hasMany(CbmsTransmissionAttempt::class)->orderBy('attempt_number'); }
}
