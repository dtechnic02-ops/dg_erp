<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesCostSnapshot extends Model
{
    protected $fillable = ['company_id', 'sales_invoice_id', 'sales_item_id', 'product_id', 'stock_movement_id', 'inventory_valuation_id', 'average_cost_used', 'movement_unit_cost', 'movement_value'];

    protected function casts(): array
    {
        return ['average_cost_used' => 'decimal:8', 'movement_unit_cost' => 'decimal:8', 'movement_value' => 'decimal:4'];
    }

    public function salesInvoice(): BelongsTo { return $this->belongsTo(SalesInvoice::class); }
    public function salesItem(): BelongsTo { return $this->belongsTo(SalesItem::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function stockMovement(): BelongsTo { return $this->belongsTo(StockMovement::class); }
    public function inventoryValuation(): BelongsTo { return $this->belongsTo(InventoryValuation::class); }
}
