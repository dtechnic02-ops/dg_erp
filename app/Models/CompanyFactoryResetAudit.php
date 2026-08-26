<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyFactoryResetAudit extends Model
{
    protected $fillable = [
        'company_id', 'initiated_by', 'company_name', 'requested_at',
        'completed_at', 'result', 'deleted_counts', 'file_manifest', 'file_cleanup_state', 'safe_error',
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
