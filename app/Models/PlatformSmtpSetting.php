<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformSmtpSetting extends Model
{
    protected $fillable = ['mailer', 'host', 'port', 'username', 'password', 'encryption', 'from_address', 'from_name', 'is_active', 'last_tested_at'];
    protected $hidden = ['password'];
    protected function casts(): array { return ['password' => 'encrypted', 'is_active' => 'boolean', 'last_tested_at' => 'datetime', 'port' => 'integer']; }
    public function platformSetting(): BelongsTo { return $this->belongsTo(PlatformSetting::class); }
}
