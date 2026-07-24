<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryNoteItem extends Model
{
    protected $fillable = [
        'company_id',
        'delivery_note_id',
        'sales_item_id',
        'item_type',
        'product_id',
        'service_id',
        'invoice_qty',
        'planned_qty',
        'delivered_qty',
        'status',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'invoice_qty' => 'decimal:2',
        'planned_qty' => 'decimal:2',
        'delivered_qty' => 'decimal:2',
    ];

    public function deliveryNote(): BelongsTo
    {
        return $this->belongsTo(DeliveryNote::class);
    }

    public function salesItem(): BelongsTo
    {
        return $this->belongsTo(SalesItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
