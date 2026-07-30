<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformSocialLink extends Model
{
    protected $fillable = ['provider', 'url', 'is_active', 'display_order'];
    protected function casts(): array { return ['is_active' => 'boolean', 'display_order' => 'integer']; }
    public function platformSetting(): BelongsTo { return $this->belongsTo(PlatformSetting::class); }
}
