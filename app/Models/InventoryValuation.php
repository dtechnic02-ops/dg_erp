<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryValuation extends Model
{
    protected $fillable = [
        'company_id', 'product_id', 'stock_movement_id', 'valuation_sequence',
        'movement_type', 'source_module', 'source_type', 'source_id', 'source_event',
        'quantity_before', 'quantity_change', 'quantity_after',
        'inventory_value_before', 'inventory_value_change', 'inventory_value_after',
        'average_cost_before', 'movement_unit_cost', 'average_cost_after',
        'reversal_of_id', 'valued_at',
    ];

    protected function casts(): array
    {
        return [
            'valuation_sequence' => 'integer',
            'quantity_before' => 'decimal:6',
            'quantity_change' => 'decimal:6',
            'quantity_after' => 'decimal:6',
            'inventory_value_before' => 'decimal:4',
            'inventory_value_change' => 'decimal:4',
            'inventory_value_after' => 'decimal:4',
            'average_cost_before' => 'decimal:8',
            'movement_unit_cost' => 'decimal:8',
            'average_cost_after' => 'decimal:8',
            'valued_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function stockMovement(): BelongsTo
    {
        return $this->belongsTo(StockMovement::class);
    }

    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_of_id');
    }
}
