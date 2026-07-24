<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryAttachment extends Model
{
    public const TYPE_PHOTO = 'photo';
    public const TYPE_ADDITIONAL_PHOTO = 'additional_photo';
    public const TYPE_ATTACHMENT = 'attachment';
    public const TYPE_PDF = 'pdf';

    protected $fillable = [
        'company_id',
        'delivery_note_id',
        'document_type',
        'file_path',
        'original_name',
        'remarks',
        'created_by',
    ];

    public function deliveryNote(): BelongsTo
    {
        return $this->belongsTo(DeliveryNote::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
