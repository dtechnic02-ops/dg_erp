<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyDeletionAudit extends Model
{
    protected $fillable = [
        'deleted_company_id', 'deleted_company_name', 'requested_by',
        'requested_at', 'completed_at', 'result', 'deleted_counts',
        'file_manifest', 'file_cleanup_state', 'safe_error',
    ];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'completed_at' => 'datetime',
            'deleted_counts' => 'array',
            'file_manifest' => 'array',
        ];
    }
}
