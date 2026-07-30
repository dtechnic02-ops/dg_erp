<?php

namespace App\Services;

use App\Models\InventoryValuation;
use App\Models\Product;
use App\Models\SalesCostSnapshot;
use App\Models\SalesInvoice;
use App\Models\SalesItem;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class SalesInventoryCostService
{
    public function snapshot(SalesInvoice $sale, SalesItem $item, StockMovement $movement): SalesCostSnapshot
    {
        if (! $sale->exists || ! $item->exists || ! $movement->exists || $item->item_type !== 'product') {
            throw new InvalidArgumentException('A saved product sale, sales item, and stock movement are required for cost snapshotting.');
        }

        if (
            (int) $item->company_id !== (int) $sale->company_id
            || (int) $item->sales_invoice_id !== (int) $sale->id
            || (int) $movement->company_id !== (int) $sale->company_id
            || (int) $movement->product_id !== (int) $item->product_id
            || $movement->type !== 'sale'
        ) {
            throw new RuntimeException('The sales item and stock movement must belong to the same company product sale.');
        }

        return DB::transaction(function () use ($sale, $item, $movement): SalesCostSnapshot {
            if (SalesCostSnapshot::where('stock_movement_id', $movement->id)->exists()
                || SalesCostSnapshot::where('sales_item_id', $item->id)->exists()) {
                throw new RuntimeException('A sales cost snapshot already exists for this sales item or stock movement.');
            }

            Product::where('company_id', $sale->company_id)->lockForUpdate()->findOrFail($item->product_id);
            $latest = InventoryValuation::where('company_id', $sale->company_id)->where('product_id', $item->product_id)->orderByDesc('valuation_sequence')->lockForUpdate()->first();
            if (! $latest) {
                throw new RuntimeException('A product sale requires an existing inventory valuation.');
            }

            $quantityBefore = $this->decimal($latest->quantity_after, 6);
            $quantityChange = $this->decimal($movement->quantity, 6);
            if (bccomp($quantityChange, '0', 6) >= 0 || bccomp($quantityBefore, $this->decimal($movement->before_stock, 6), 6) !== 0) {
                throw new RuntimeException('The sales stock movement does not match the latest inventory valuation.');
            }

            $quantityAfter = bcadd($quantityBefore, $quantityChange, 6);
            if (bccomp($quantityAfter, $this->decimal($movement->after_stock, 6), 6) !== 0) {
                throw new RuntimeException('The sales stock movement after quantity does not match the inventory valuation.');
            }

            $averageCost = $this->decimal($latest->average_cost_after, 8);
            $movementValue = bcmul(ltrim($quantityChange, '-'), $averageCost, 4);
            $valueAfter = bcsub($this->decimal($latest->inventory_value_after, 4), $movementValue, 4);
            $valuation = InventoryValuation::create([
                'company_id' => $sale->company_id, 'product_id' => $item->product_id, 'stock_movement_id' => $movement->id,
                'valuation_sequence' => $latest->valuation_sequence + 1, 'movement_type' => 'sale',
                'source_module' => 'sales', 'source_type' => SalesInvoice::class, 'source_id' => $sale->id, 'source_event' => 'created',
                'quantity_before' => $quantityBefore, 'quantity_change' => $quantityChange, 'quantity_after' => $quantityAfter,
                'inventory_value_before' => $latest->inventory_value_after, 'inventory_value_change' => '-' . $movementValue, 'inventory_value_after' => $valueAfter,
                'average_cost_before' => $averageCost, 'movement_unit_cost' => $averageCost,
                'average_cost_after' => bccomp($quantityAfter, '0', 6) === 0 ? '0.00000000' : $averageCost,
                'valued_at' => now(),
            ]);

            return SalesCostSnapshot::create([
                'company_id' => $sale->company_id, 'sales_invoice_id' => $sale->id, 'sales_item_id' => $item->id, 'product_id' => $item->product_id,
                'stock_movement_id' => $movement->id, 'inventory_valuation_id' => $valuation->id,
                'average_cost_used' => $averageCost, 'movement_unit_cost' => $averageCost, 'movement_value' => $movementValue,
            ]);
        });
    }

    private function decimal(mixed $value, int $scale): string { return bcadd((string) $value, '0', $scale); }
}
