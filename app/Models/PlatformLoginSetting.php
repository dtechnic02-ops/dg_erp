<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformLoginSetting extends Model
{
    protected $fillable = [
        'heading',
        'description',
        'hero_image_path',
        'address_line_2',
        'address_line_3',
        'gallery_images',
        'is_published',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'gallery_images' => 'array',
            'is_published' => 'boolean',
        ];
    }

    public function platformSetting(): BelongsTo
    {
        return $this->belongsTo(PlatformSetting::class);
    }
}
