<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliverySignature extends Model
{
    protected $fillable = [
        'company_id',
        'delivery_note_id',
        'customer_name',
        'receiver_name',
        'receiver_mobile',
        'signature_path',
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
