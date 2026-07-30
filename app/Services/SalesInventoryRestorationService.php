<?php

namespace App\Services;

use App\Models\InventoryValuation;
use App\Models\Product;
use App\Models\SalesCostSnapshot;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SalesInventoryRestorationService
{
    public function restore(SalesCostSnapshot $snapshot, string|int|float $restoreQuantity, string $sourceType, int $sourceId, string $sourceEvent, string $reference, int $financialYearId, string $date, ?int $postedBy = null): InventoryValuation
    {
        return DB::transaction(function () use ($snapshot, $restoreQuantity, $sourceType, $sourceId, $sourceEvent, $reference, $financialYearId, $date, $postedBy): InventoryValuation {
            $snapshot = SalesCostSnapshot::where('company_id', $snapshot->company_id)->lockForUpdate()->findOrFail($snapshot->id);
            $original = InventoryValuation::where('company_id', $snapshot->company_id)->where('id', $snapshot->inventory_valuation_id)->lockForUpdate()->first();
            if (! $original || $original->movement_type !== 'sale') throw new RuntimeException('The original sales inventory valuation could not be resolved.');
            if (InventoryValuation::where('company_id', $snapshot->company_id)->where('source_type', $sourceType)->where('source_id', $sourceId)->where('source_event', $sourceEvent)->where('product_id', $snapshot->product_id)->exists()) throw new RuntimeException('A sales inventory restoration has already been posted for this source.');

            $product = Product::where('company_id', $snapshot->company_id)->lockForUpdate()->findOrFail($snapshot->product_id);
            $latest = InventoryValuation::where('company_id', $snapshot->company_id)->where('product_id', $snapshot->product_id)->orderByDesc('valuation_sequence')->lockForUpdate()->first();
            if (! $latest) throw new RuntimeException('The latest inventory valuation could not be resolved.');
            if (bccomp((string) $latest->quantity_after, (string) $product->current_stock, 6) !== 0) throw new RuntimeException('Inventory quantity continuity is invalid.');

            $originalQuantity = bcsub('0', (string) $original->quantity_change, 6);
            $quantity = bcadd((string) $restoreQuantity, '0', 6);
            if (bccomp($originalQuantity, '0', 6) <= 0 || bccomp($quantity, '0', 6) <= 0 || bccomp($quantity, $originalQuantity, 6) > 0) throw new RuntimeException('The sales inventory restoration quantity is invalid.');
            $cost = bcadd((string) $snapshot->movement_unit_cost, '0', 8);
            $value = bcmul($quantity, $cost, 4);
            if (bccomp($snapshot->movement_value, bcmul($originalQuantity, $cost, 4), 4) !== 0) throw new RuntimeException('The saved sales cost snapshot is inconsistent.');

            /** @var StockMovement $movement */
            $movement = StockService::increase($product, $quantity, 'sales_restore', $reference, $financialYearId, $date, $cost, 'Sales inventory restoration');
            $quantityAfter = bcadd((string) $latest->quantity_after, $quantity, 6);
            $valueAfter = bcadd((string) $latest->inventory_value_after, $value, 4);
            $averageAfter = bcdiv($valueAfter, $quantityAfter, 8);

            return InventoryValuation::create([
                'company_id' => $snapshot->company_id, 'product_id' => $snapshot->product_id, 'stock_movement_id' => $movement->id,
                'valuation_sequence' => $latest->valuation_sequence + 1, 'movement_type' => 'sales_restore',
                'source_module' => 'sales', 'source_type' => $sourceType, 'source_id' => $sourceId, 'source_event' => $sourceEvent,
                'quantity_before' => $latest->quantity_after, 'quantity_change' => $quantity, 'quantity_after' => $quantityAfter,
                'inventory_value_before' => $latest->inventory_value_after, 'inventory_value_change' => $value, 'inventory_value_after' => $valueAfter,
                'average_cost_before' => $latest->average_cost_after, 'movement_unit_cost' => $cost, 'average_cost_after' => $averageAfter,
                'reversal_of_id' => $original->id, 'valued_at' => now(),
            ]);
        });
    }

    public function reverseRestoration(SalesCostSnapshot $snapshot, string|int|float $quantity, int $returnItemId, string $reference, int $financialYearId, string $date): InventoryValuation
    {
        return DB::transaction(function () use ($snapshot, $quantity, $returnItemId, $reference, $financialYearId, $date): InventoryValuation {
            $snapshot = SalesCostSnapshot::where('company_id', $snapshot->company_id)->lockForUpdate()->findOrFail($snapshot->id);
            $restoration = InventoryValuation::where('company_id', $snapshot->company_id)->where('product_id', $snapshot->product_id)->where('source_type', \App\Models\SalesReturnItem::class)->where('source_id', $returnItemId)->where('source_event', 'created')->lockForUpdate()->first();
            if (! $restoration) throw new RuntimeException('The original sales return inventory restoration could not be resolved.');
            if (InventoryValuation::where('company_id', $snapshot->company_id)->where('source_type', \App\Models\SalesReturnItem::class)->where('source_id', $returnItemId)->where('source_event', 'cancelled')->exists()) throw new RuntimeException('This sales return inventory restoration has already been reversed.');
            $restoreQuantity = bcadd((string) $quantity, '0', 6);
            if (bccomp($restoreQuantity, (string) $restoration->quantity_change, 6) !== 0) throw new RuntimeException('The sales return inventory restoration quantity is invalid.');
            $product = Product::where('company_id', $snapshot->company_id)->lockForUpdate()->findOrFail($snapshot->product_id);
            $latest = InventoryValuation::where('company_id', $snapshot->company_id)->where('product_id', $snapshot->product_id)->orderByDesc('valuation_sequence')->lockForUpdate()->first();
            if (! $latest || bccomp((string) $latest->quantity_after, (string) $product->current_stock, 6) !== 0) throw new RuntimeException('Inventory quantity continuity is invalid.');
            $cost = bcadd((string) $snapshot->movement_unit_cost, '0', 8);
            $value = bcmul($restoreQuantity, $cost, 4);
            /** @var StockMovement $movement */
            $movement = StockService::decrease($product, $restoreQuantity, 'sales_return_cancel', $reference, $financialYearId, $date, $cost, 'Sales return inventory restoration reversal');
            $quantityAfter = bcsub((string) $latest->quantity_after, $restoreQuantity, 6);
            $valueAfter = bcsub((string) $latest->inventory_value_after, $value, 4);
            $averageAfter = bccomp($quantityAfter, '0', 6) === 0 ? '0.00000000' : bcdiv($valueAfter, $quantityAfter, 8);
            return InventoryValuation::create(['company_id'=>$snapshot->company_id,'product_id'=>$snapshot->product_id,'stock_movement_id'=>$movement->id,'valuation_sequence'=>$latest->valuation_sequence+1,'movement_type'=>'sales_return_cancel','source_module'=>'sales','source_type'=>\App\Models\SalesReturnItem::class,'source_id'=>$returnItemId,'source_event'=>'cancelled','quantity_before'=>$latest->quantity_after,'quantity_change'=>'-'.$restoreQuantity,'quantity_after'=>$quantityAfter,'inventory_value_before'=>$latest->inventory_value_after,'inventory_value_change'=>'-'.$value,'inventory_value_after'=>$valueAfter,'average_cost_before'=>$latest->average_cost_after,'movement_unit_cost'=>$cost,'average_cost_after'=>$averageAfter,'reversal_of_id'=>$restoration->id,'valued_at'=>now()]);
        });
    }
}
