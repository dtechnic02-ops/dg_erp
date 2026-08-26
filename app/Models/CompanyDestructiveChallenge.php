<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyDestructiveChallenge extends Model
{
    public const PURPOSE_PERMANENT_DELETE = 'company_permanent_delete';
    public const PURPOSE_FACTORY_RESET = 'company_factory_reset';

    protected $fillable = [
        'company_id', 'requested_by', 'purpose', 'otp_hash', 'attempts',
        'expires_at', 'invalidated_at', 'used_at',
    ];

    protected $hidden = ['otp_hash'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'invalidated_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }
}
