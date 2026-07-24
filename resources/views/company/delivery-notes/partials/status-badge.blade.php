@php
    $status = $deliveryNote->status ?? '';
    $badgeClass = match ($status) {
        \App\Models\DeliveryNote::STATUS_READY => 'bg-info',
        \App\Models\DeliveryNote::STATUS_DELIVERED => 'bg-success',
        \App\Models\DeliveryNote::STATUS_PARTIAL => 'bg-warning text-dark',
        \App\Models\DeliveryNote::STATUS_REJECTED => 'bg-danger',
        \App\Models\DeliveryNote::STATUS_CANCELLED => 'bg-secondary',
        default => 'bg-light text-dark',
    };
@endphp

<span class="badge {{ $badgeClass }}">{{ \App\Models\DeliveryNote::statusLabel($status) }}</span>
