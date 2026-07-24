<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmNote extends Model
{
    protected $fillable = [
        'company_id',
        'entity_type',
        'entity_id',
        'note',
        'status',
        'archived_by',
        'archived_at',
        'archive_reason',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'archived_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
