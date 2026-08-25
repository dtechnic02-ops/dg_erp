<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PasswordResetRequest extends Model
{
    protected $fillable = [
        'user_id',
        'initiated_by',
        'user_email',
        'initiated_by_email',
        'token_hash',
        'otp_session_hash',
        'pending_password_hash',
        'otp_hash',
        'otp_attempts',
        'expires_at',
        'link_opened_at',
        'token_consumed_at',
        'otp_sent_at',
        'otp_expires_at',
        'otp_verified_at',
        'password_changed_at',
        'used_at',
        'invalidated_at',
    ];

    protected $hidden = [
        'token_hash',
        'otp_session_hash',
        'pending_password_hash',
        'otp_hash',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'link_opened_at' => 'datetime',
            'token_consumed_at' => 'datetime',
            'otp_sent_at' => 'datetime',
            'otp_expires_at' => 'datetime',
            'otp_verified_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'used_at' => 'datetime',
            'invalidated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
