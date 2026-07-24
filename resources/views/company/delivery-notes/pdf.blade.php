<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delivery Note {{ $deliveryNote->delivery_no }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
        .header { text-align: center; margin-bottom: 20px; }
        .title { font-size: 20px; font-weight: bold; margin: 0 0 6px; }
        .meta-table, .items-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .meta-table td, .meta-table th { padding: 6px 8px; vertical-align: top; }
        .meta-table th { width: 160px; text-align: left; }
        .items-table th, .items-table td { border: 1px solid #ccc; padding: 6px 8px; }
        .items-table th { background: #f5f5f5; }
        .text-end { text-align: right; }
        .section-title { font-size: 14px; font-weight: bold; margin: 18px 0 8px; }
        .signature-box { margin-top: 20px; }
        .signature-box img { max-width: 260px; max-height: 120px; border: 1px solid #ccc; }
        .footer { margin-top: 24px; font-size: 11px; color: #555; }
    </style>
</head>
<body>

    <div class="header">
        <div class="title">DELIVERY NOTE</div>
        <div>{{ $company->company_name ?? 'Company' }}</div>
    </div>

    <table class="meta-table">
        <tr>
            <th>Delivery No</th>
            <td>{{ $deliveryNote->delivery_no }}</td>
            <th>Financial Year</th>
            <td>{{ $deliveryNote->financialYear->name ?? '-' }}</td>
        </tr>
        <tr>
            <th>Delivery Date</th>
            <td>{{ $deliveryNote->delivery_date?->format('d-m-Y') ?? '-' }}</td>
            <th>Status</th>
            <td>{{ \App\Models\DeliveryNote::statusLabel($deliveryNote->status) }}</td>
        </tr>
        <tr>
            <th>Customer</th>
            <td>{{ $deliveryNote->customer->name ?? '-' }}</td>
            <th>Sales Invoice</th>
            <td>{{ $deliveryNote->salesInvoice->invoice_no ?? '-' }}</td>
        </tr>
        <tr>
            <th>Employee</th>
            <td>{{ $deliveryNote->employee->full_name ?? '-' }}</td>
            <th>Completed At</th>
            <td>{{ $deliveryNote->completed_at?->format('d-m-Y H:i') ?? '-' }}</td>
        </tr>
    </table>

    <div class="section-title">Delivery Items</div>
    <table class="items-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Item</th>
                <th class="text-end">Invoice Qty</th>
                <th class="text-end">Planned Qty</th>
                <th class="text-end">Delivered Qty</th>
                <th class="text-end">Remaining Qty</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($lineItems as $index => $line)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $line['item_name'] }}</td>
                    <td class="text-end">{{ number_format($line['invoice_qty'], 2) }}</td>
                    <td class="text-end">{{ number_format($line['planned_qty'], 2) }}</td>
                    <td class="text-end">{{ number_format($line['delivered_qty'], 2) }}</td>
                    <td class="text-end">{{ number_format($line['remaining_qty'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if ($deliveryNote->signature)
        <div class="signature-box">
            <div class="section-title">Customer Signature</div>
            <div><strong>Receiver:</strong> {{ $deliveryNote->signature->receiver_name ?? '-' }} ({{ $deliveryNote->signature->receiver_mobile ?? '-' }})</div>
            @php
                $signaturePath = public_path('companies/' . $deliveryNote->company_id . '/deliveries/' . $deliveryNote->id . '/' . $deliveryNote->signature->signature_path);
            @endphp
            @if (is_file($signaturePath))
                <div style="margin-top:8px;">
                    <img src="{{ $signaturePath }}" alt="Signature">
                </div>
            @endif
        </div>
    @endif

    @if ($deliveryNote->remarks)
        <div class="section-title">Remarks</div>
        <div>{{ $deliveryNote->remarks }}</div>
    @endif

    <div class="footer">
        Generated on {{ now()->format('d-m-Y H:i') }} — {{ $deliveryNote->delivery_no }}
    </div>

</body>
</html>
