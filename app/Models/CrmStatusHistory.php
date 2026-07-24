<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmStatusHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'entity_type',
        'entity_id',
        'event',
        'previous_value',
        'current_value',
        'remarks',
        'changed_by',
        'changed_at',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
