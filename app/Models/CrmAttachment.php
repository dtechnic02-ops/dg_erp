<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmAttachment extends Model
{
    protected $fillable = [
        'company_id',
        'entity_type',
        'entity_id',
        'document_type',
        'file_path',
        'original_name',
        'mime_type',
        'file_size',
        'remarks',
        'is_archived',
        'archived_by',
        'archived_at',
        'archive_reason',
        'created_by',
    ];

    protected $casts = [
        'is_archived' => 'boolean',
        'archived_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
