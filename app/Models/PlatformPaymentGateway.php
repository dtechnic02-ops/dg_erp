<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformPaymentGateway extends Model
{
    protected $fillable = ['gateway', 'display_name', 'environment', 'public_key', 'secret_key', 'merchant_id', 'webhook_secret', 'additional_config', 'is_active'];
    protected $hidden = ['secret_key', 'webhook_secret'];
    protected function casts(): array { return ['secret_key' => 'encrypted', 'webhook_secret' => 'encrypted', 'additional_config' => 'array', 'is_active' => 'boolean']; }
    public function platformSetting(): BelongsTo { return $this->belongsTo(PlatformSetting::class); }
}
