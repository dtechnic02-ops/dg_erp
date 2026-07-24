@extends('company.layout')

@section('title', 'Process Delivery')

@section('content')

<div class="dg-page">

    <header class="dg-toolbar">
        <div class="container-fluid">
            <div class="row align-items-center g-2">
                <div class="col">
                    <h1 class="h4 mb-0">Process Delivery</h1>
                    <p class="text-muted small mb-0">{{ $deliveryNote->delivery_no }} — {{ $deliveryNote->customer->name ?? '-' }}</p>
                </div>
                <div class="col-auto">
                    <a href="{{ route('company.delivery-notes.index') }}" class="btn btn-outline-secondary dg-btn">Back</a>
                </div>
            </div>
        </div>
    </header>

    <main class="dg-container">
        <div class="container-fluid">

            @if ($errors->any())
                <div class="alert alert-danger dg-alert" role="alert">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger dg-alert" role="alert">{{ session('error') }}</div>
            @endif

            <form method="POST" action="{{ route('company.delivery-notes.complete', $deliveryNote->id) }}" enctype="multipart/form-data" id="dgDeliveryProcessForm">
                @csrf

                <section class="dg-section">
                    <article class="card dg-card">
                        <header class="card-header dg-card-header">
                            <h2 class="h6 mb-0">Delivery Summary</h2>
                        </header>
                        <div class="card-body dg-card-body">
                            <div class="row g-3">
                                <div class="col-md-3"><strong>Invoice:</strong> {{ $deliveryNote->salesInvoice->invoice_no ?? '-' }}</div>
                                <div class="col-md-3"><strong>Employee:</strong> {{ $deliveryNote->employee->full_name ?? '-' }}</div>
                                <div class="col-md-3"><strong>Date:</strong> {{ $deliveryNote->delivery_date?->format('d-m-Y') }}</div>
                                <div class="col-md-3"><strong>Status:</strong> @include('company.delivery-notes.partials.status-badge', ['deliveryNote' => $deliveryNote])</div>
                            </div>
                        </div>
                    </article>
                </section>

                <section class="dg-section">
                    <article class="card dg-card">
                        <header class="card-header dg-card-header">
                            <h2 class="h6 mb-0">Deliver Now</h2>
                        </header>
                        <div class="card-body dg-card-body">
                            <div class="table-responsive">
                                <table class="table dg-table">
                                    <thead>
                                        <tr>
                                            <th width="50">Select</th>
                                            <th>Item</th>
                                            <th class="text-end">Planned Qty</th>
                                            <th class="text-end" width="160">Deliver Now</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($lineItems as $index => $line)
                                            <tr>
                                                <td>
                                                    <input type="hidden" name="items[{{ $index }}][delivery_note_item_id]" value="{{ $line['item']->id }}">
                                                    <input
                                                        type="checkbox"
                                                        class="form-check-input dg-delivery-item-select"
                                                        name="items[{{ $index }}][selected]"
                                                        value="1"
                                                        data-planned="{{ $line['planned_qty'] }}"
                                                        data-qty-input="delivered_qty_{{ $index }}"
                                                        @checked(old('items.' . $index . '.selected'))
                                                    >
                                                </td>
                                                <td>{{ $line['item_name'] }}</td>
                                                <td class="text-end">{{ number_format($line['planned_qty'], 2) }}</td>
                                                <td class="text-end">
                                                    <input
                                                        type="number"
                                                        step="0.01"
                                                        min="0"
                                                        max="{{ $line['planned_qty'] }}"
                                                        id="delivered_qty_{{ $index }}"
                                                        name="items[{{ $index }}][delivered_qty]"
                                                        class="form-control dg-input text-end dg-delivery-qty"
                                                        value="{{ old('items.' . $index . '.delivered_qty', 0) }}"
                                                    >
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </article>
                </section>

                <section class="dg-section">
                    <article class="card dg-card">
                        <header class="card-header dg-card-header">
                            <h2 class="h6 mb-0">Proof of Delivery</h2>
                        </header>
                        <div class="card-body dg-card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="receiver_name" class="form-label">Receiver Name *</label>
                                    <input type="text" name="receiver_name" id="receiver_name" class="form-control dg-input" value="{{ old('receiver_name') }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="receiver_mobile" class="form-label">Receiver Mobile *</label>
                                    <input type="text" name="receiver_mobile" id="receiver_mobile" class="form-control dg-input" value="{{ old('receiver_mobile') }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="photo_1" class="form-label">Photo 1 *</label>
                                    <input type="file" name="photo_1" id="photo_1" class="form-control dg-input" accept="image/*" capture="environment" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="photo_2" class="form-label">Photo 2</label>
                                    <input type="file" name="photo_2" id="photo_2" class="form-control dg-input" accept="image/*" capture="environment">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Customer Signature *</label>
                                    <div class="border rounded bg-white">
                                        <canvas id="dgSignatureCanvas" width="700" height="220" style="width:100%;max-width:700px;height:220px;touch-action:none;"></canvas>
                                    </div>
                                    <div class="d-flex gap-2 mt-2">
                                        <button type="button" class="btn btn-outline-secondary btn-sm dg-btn" id="dgSignatureClear">Clear Signature</button>
                                    </div>
                                    <input type="hidden" name="signature_data" id="signature_data" value="{{ old('signature_data') }}">
                                </div>
                            </div>
                        </div>
                        <footer class="card-footer dg-card-footer">
                            <button type="submit" class="btn btn-success dg-btn" id="dgDeliverySubmit">Submit Delivery</button>
                        </footer>
                    </article>
                </section>
            </form>
        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const canvas = document.getElementById('dgSignatureCanvas');
    const hiddenInput = document.getElementById('signature_data');
    const clearBtn = document.getElementById('dgSignatureClear');
    const form = document.getElementById('dgDeliveryProcessForm');

    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    let drawing = false;
    let hasStroke = false;

    ctx.strokeStyle = '#111';
    ctx.lineWidth = 2;
    ctx.lineCap = 'round';

    function resizeCanvas() {
        const ratio = window.devicePixelRatio || 1;
        const rect = canvas.getBoundingClientRect();
        canvas.width = rect.width * ratio;
        canvas.height = rect.height * ratio;
        ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
        ctx.strokeStyle = '#111';
        ctx.lineWidth = 2;
        ctx.lineCap = 'round';
    }

    resizeCanvas();
    window.addEventListener('resize', resizeCanvas);

    function pointerPosition(event) {
        const rect = canvas.getBoundingClientRect();
        const clientX = event.touches ? event.touches[0].clientX : event.clientX;
        const clientY = event.touches ? event.touches[0].clientY : event.clientY;
        return {
            x: clientX - rect.left,
            y: clientY - rect.top,
        };
    }

    function startDraw(event) {
        drawing = true;
        const pos = pointerPosition(event);
        ctx.beginPath();
        ctx.moveTo(pos.x, pos.y);
        event.preventDefault();
    }

    function draw(event) {
        if (!drawing) return;
        const pos = pointerPosition(event);
        ctx.lineTo(pos.x, pos.y);
        ctx.stroke();
        hasStroke = true;
        event.preventDefault();
    }

    function stopDraw() {
        drawing = false;
    }

    canvas.addEventListener('mousedown', startDraw);
    canvas.addEventListener('mousemove', draw);
    canvas.addEventListener('mouseup', stopDraw);
    canvas.addEventListener('mouseleave', stopDraw);
    canvas.addEventListener('touchstart', startDraw, { passive: false });
    canvas.addEventListener('touchmove', draw, { passive: false });
    canvas.addEventListener('touchend', stopDraw);

    clearBtn.addEventListener('click', function () {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        hiddenInput.value = '';
        hasStroke = false;
    });

    document.querySelectorAll('.dg-delivery-item-select').forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            const qtyInput = document.getElementById(checkbox.dataset.qtyInput);
            if (!qtyInput) return;
            if (checkbox.checked) {
                qtyInput.value = checkbox.dataset.planned;
            } else {
                qtyInput.value = 0;
            }
        });
    });

    form.addEventListener('submit', function (event) {
        if (!hasStroke) {
            event.preventDefault();
            alert('Customer signature is required.');
            return;
        }

        hiddenInput.value = canvas.toDataURL('image/png');
    });
});
</script>

@endsection
