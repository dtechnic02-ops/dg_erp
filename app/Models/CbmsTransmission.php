<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class CbmsTransmission extends Model
{
    public const ENDPOINT_BILL = 'bill';
    public const ENDPOINT_BILL_RETURN = 'bill_return';
    public const ENVIRONMENT_TEST = 'test';
    public const ENVIRONMENT_PRODUCTION = 'production';
    public const ENVIRONMENT_LEGACY = 'legacy';
    public const ENVIRONMENTS = [self::ENVIRONMENT_TEST, self::ENVIRONMENT_PRODUCTION, self::ENVIRONMENT_LEGACY];
    public const TRANSPORT_DISABLED = 'disabled';
    public const TRANSPORT_SIMULATOR = 'simulator';
    public const TRANSPORT_IRD = 'ird';
    public const TRANSPORT_LEGACY = 'legacy';
    public const TRANSPORT_KINDS = [self::TRANSPORT_DISABLED, self::TRANSPORT_SIMULATOR, self::TRANSPORT_IRD, self::TRANSPORT_LEGACY];
    public const STATUS_PENDING = 'pending';
    public const STATUS_NOT_READY = 'not_ready';
    public const STATUS_QUEUED = 'queued';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_DUPLICATE_REQUIRES_RECONCILIATION = 'duplicate_requires_reconciliation';
    public const STATUS_RETRYABLE_FAILURE = 'retryable_failure';
    public const STATUS_PERMANENT_FAILURE = 'permanent_failure';

    protected $fillable = ['company_id', 'transmittable_type', 'transmittable_id', 'endpoint_type', 'environment', 'transport_kind', 'status', 'attempt_count', 'last_attempted_at', 'submitted_at', 'response_code', 'response_category', 'payload_hash', 'response_body_redacted'];

    protected static function booted(): void
    {
        static::updating(function (self $transmission): void {
            if ($transmission->isDirty(['environment', 'transport_kind'])) {
                throw new LogicException('CBMS transmission provenance is immutable.');
            }
        });
    }

    protected function casts(): array
    {
        return ['attempt_count' => 'integer', 'last_attempted_at' => 'datetime', 'submitted_at' => 'datetime', 'response_body_redacted' => 'array'];
    }

    public function company() { return $this->belongsTo(Company::class); }
    public function transmittable() { return $this->morphTo(); }
    public function attempts() { return $this->hasMany(CbmsTransmissionAttempt::class)->orderBy('attempt_number'); }

    public function scopeSubmittedForProvenance($query, string $environment, string $transportKind)
    {
        return $query->where('environment', $environment)
            ->where('transport_kind', $transportKind)
            ->where('status', self::STATUS_SUBMITTED);
    }

    public function scopeOfficialIrdProductionSubmission($query)
    {
        return $query->submittedForProvenance(self::ENVIRONMENT_PRODUCTION, self::TRANSPORT_IRD);
    }

    public function isOfficialIrdProductionSubmission(): bool
    {
        return $this->environment === self::ENVIRONMENT_PRODUCTION
            && $this->transport_kind === self::TRANSPORT_IRD
            && $this->status === self::STATUS_SUBMITTED;
    }
}
