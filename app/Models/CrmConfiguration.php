<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmConfiguration extends Model
{
    public const TYPE_LEAD_STATUS = 'lead_status';
    public const TYPE_CONTACT_STATUS = 'contact_status';
    public const TYPE_OPPORTUNITY_STATUS = 'opportunity_status';
    public const TYPE_PRIORITY = 'priority';
    public const TYPE_LEAD_SOURCE = 'lead_source';
    public const TYPE_OPPORTUNITY_STAGE = 'opportunity_stage';
    public const TYPE_FOLLOW_UP_STATUS = 'follow_up_status';
    public const TYPE_TASK_TYPE = 'task_type';
    public const TYPE_TASK_STATUS = 'task_status';
    public const TYPE_MEETING_STATUS = 'meeting_status';
    public const TYPE_NOTE_STATUS = 'note_status';

    protected $fillable = [
        'company_id',
        'config_type',
        'config_key',
        'config_label',
        'sort_order',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
