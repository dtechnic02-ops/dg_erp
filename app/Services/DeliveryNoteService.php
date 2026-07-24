<?php

namespace App\Services;

use App\Models\DeliveryNote;
use App\Models\DeliveryNoteItem;
use App\Models\DeliveryStatusHistory;
use App\Models\SalesInvoice;
use App\Models\SalesItem;
use App\Services\Concerns\GuardsSubscriptionModule;

class DeliveryNoteService
{
    use GuardsSubscriptionModule;
    /** @var array<int, string> */
    private const COMPLETED_STATUSES = [
        DeliveryNote::STATUS_DELIVERED,
        DeliveryNote::STATUS_PARTIAL,
    ];

    /** @var array<int, string> */
    private const ACTIVE_PLANNED_STATUSES = [
        DeliveryNote::STATUS_READY,
    ];

    public static function generateDeliveryNo(int $companyId): string
    {
        $year = now()->format('Y');

        $last = DeliveryNote::where('company_id', $companyId)
            ->where('delivery_no', 'like', 'DN-' . $year . '-%')
            ->lockForUpdate()
            ->orderByDesc('id')
            ->first();

        $next = 1;

        if ($last && preg_match('/(\d+)$/', $last->delivery_no, $match)) {
            $next = ((int) $match[1]) + 1;
        }

        return 'DN-' . $year . '-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }

    public function totalDeliveredQtyForSalesItem(
        int $companyId,
        int $salesItemId,
        ?int $excludeDeliveryNoteId = null
    ): float {
        $this->assertSubscriptionModule($companyId, 'delivery');

        $query = DeliveryNoteItem::query()
            ->where('company_id', $companyId)
            ->where('sales_item_id', $salesItemId)
            ->whereHas('deliveryNote', function ($noteQuery) use ($excludeDeliveryNoteId) {
                $noteQuery->whereIn('status', self::COMPLETED_STATUSES);

                if ($excludeDeliveryNoteId) {
                    $noteQuery->where('id', '!=', $excludeDeliveryNoteId);
                }
            });

        return round((float) $query->sum('delivered_qty'), 2);
    }

    public function totalPlannedQtyForSalesItem(
        int $companyId,
        int $salesItemId,
        ?int $excludeDeliveryNoteId = null
    ): float {
        $this->assertSubscriptionModule($companyId, 'delivery');

        $query = DeliveryNoteItem::query()
            ->where('company_id', $companyId)
            ->where('sales_item_id', $salesItemId)
            ->whereHas('deliveryNote', function ($noteQuery) use ($excludeDeliveryNoteId) {
                $noteQuery->whereIn('status', self::ACTIVE_PLANNED_STATUSES);

                if ($excludeDeliveryNoteId) {
                    $noteQuery->where('id', '!=', $excludeDeliveryNoteId);
                }
            });

        return round((float) $query->sum('planned_qty'), 2);
    }

    public function remainingQtyForSalesItem(int $companyId, SalesItem $salesItem): float
    {
        $this->assertSubscriptionModule($companyId, 'delivery');

        $invoiceQty = round((float) $salesItem->quantity, 2);
        $returnedQty = round((float) ($salesItem->returned_qty ?? 0), 2);
        $deliverableBase = max(0, round($invoiceQty - $returnedQty, 2));
        $alreadyDelivered = $this->totalDeliveredQtyForSalesItem($companyId, (int) $salesItem->id);
        $alreadyPlanned = $this->totalPlannedQtyForSalesItem($companyId, (int) $salesItem->id);

        return max(0, round($deliverableBase - $alreadyDelivered - $alreadyPlanned, 2));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function buildCreateLineItems(int $companyId, SalesInvoice $invoice): array
    {
        $this->assertSubscriptionModule($companyId, 'delivery');

        $invoice->loadMissing(['items.product', 'items.service']);

        $lines = [];

        foreach ($invoice->items as $salesItem) {
            $remainingQty = $this->remainingQtyForSalesItem($companyId, $salesItem);

            if ($remainingQty <= 0) {
                continue;
            }

            $lines[] = [
                'sales_item_id' => $salesItem->id,
                'item_type' => $salesItem->item_type,
                'item_name' => $this->resolveItemName($salesItem),
                'invoice_qty' => round((float) $salesItem->quantity, 2),
                'returned_qty' => round((float) ($salesItem->returned_qty ?? 0), 2),
                'previously_delivered_qty' => round(
                    $this->totalDeliveredQtyForSalesItem($companyId, (int) $salesItem->id),
                    2
                ),
                'remaining_qty' => $remainingQty,
            ];
        }

        return $lines;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function buildShowLineItems(int $companyId, DeliveryNote $deliveryNote): array
    {
        $this->assertSubscriptionModule($companyId, 'delivery');

        $deliveryNote->loadMissing(['items.product', 'items.service', 'items.salesItem']);

        $lines = [];

        foreach ($deliveryNote->items as $item) {
            $totalDeliveredElsewhere = $this->totalDeliveredQtyForSalesItem(
                $companyId,
                (int) $item->sales_item_id,
                (int) $deliveryNote->id
            );

            $invoiceQty = round((float) $item->invoice_qty, 2);
            $returnedQty = round((float) ($item->salesItem?->returned_qty ?? 0), 2);
            $deliverableBase = max(0, round($invoiceQty - $returnedQty, 2));
            $noteDelivered = $deliveryNote->isCompleted()
                ? round((float) $item->delivered_qty, 2)
                : 0;
            $totalDeliveredIncludingThis = round($totalDeliveredElsewhere + $noteDelivered, 2);
            $remainingAfterThis = max(0, round($deliverableBase - $totalDeliveredIncludingThis, 2));

            $lines[] = [
                'item' => $item,
                'item_name' => $this->resolveItemName($item),
                'invoice_qty' => $invoiceQty,
                'planned_qty' => round((float) $item->planned_qty, 2),
                'delivered_qty' => round((float) $item->delivered_qty, 2),
                'remaining_qty' => $remainingAfterThis,
            ];
        }

        return $lines;
    }

    public function resolveItemName(SalesItem|DeliveryNoteItem $line): string
    {
        $this->assertSubscriptionModule((int) $line->company_id, 'delivery');

        if ($line instanceof DeliveryNoteItem) {
            if ($line->item_type === 'product' && $line->product) {
                return $line->product->product_name ?? $line->product->name ?? 'Product';
            }

            if ($line->item_type === 'service' && $line->service) {
                return $line->service->service_name ?? $line->service->name ?? 'Service';
            }

            $line->loadMissing('salesItem.product', 'salesItem.service');

            return $this->resolveItemName($line->salesItem);
        }

        if ($line->item_type === 'product' && $line->product) {
            return $line->product->product_name ?? $line->product->name ?? 'Product';
        }

        if ($line->item_type === 'service' && $line->service) {
            return $line->service->service_name ?? $line->service->name ?? 'Service';
        }

        return ucfirst((string) $line->item_type);
    }

    public function recordStatusHistory(
        DeliveryNote $deliveryNote,
        ?string $previousStatus,
        string $currentStatus,
        ?string $remarks = null
    ): void {
        $this->assertSubscriptionModule((int) $deliveryNote->company_id, 'delivery');

        DeliveryStatusHistory::create([
            'company_id' => $deliveryNote->company_id,
            'delivery_note_id' => $deliveryNote->id,
            'previous_status' => $previousStatus,
            'current_status' => $currentStatus,
            'changed_by' => auth()->id(),
            'remarks' => $remarks,
            'changed_at' => now(),
        ]);
    }

    public function deliveryStoragePath(int $companyId, int $deliveryNoteId): string
    {
        $this->assertSubscriptionModule($companyId, 'delivery');

        return public_path('companies/' . $companyId . '/deliveries/' . $deliveryNoteId);
    }

    public function ensureDeliveryStorageDirectory(int $companyId, int $deliveryNoteId): string
    {
        $this->assertSubscriptionModule($companyId, 'delivery');

        $path = $this->deliveryStoragePath($companyId, $deliveryNoteId);

        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        return $path;
    }
}
