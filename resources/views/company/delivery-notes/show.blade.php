@extends('company.layout')

@section('title', 'Delivery Note')

@section('content')

@php
    $canCancel = userCan('cancel_delivery') && $deliveryNote->isCancellable();
    $canProcess = userCan('process_delivery') && $deliveryNote->isProcessable();
    $canPrint = userCan('print_delivery') && $deliveryNote->isCompleted();
    $storageBase = 'companies/' . $deliveryNote->company_id . '/deliveries/' . $deliveryNote->id . '/';
@endphp

<div class="dg-page">

    <header class="dg-toolbar">
        <div class="container-fluid">
            <div class="row align-items-center g-2">
                <div class="col">
                    <h1 class="h4 mb-0">Delivery Note</h1>
                    <p class="text-muted small mb-0">{{ $deliveryNote->delivery_no }}</p>
                </div>
                <div class="col-auto">
                    <nav class="btn-group flex-wrap" aria-label="Delivery note toolbar">
                        <a href="{{ route('company.delivery-notes.index') }}" class="btn btn-outline-secondary dg-btn">Back</a>
                        @if ($canProcess)
                            <a href="{{ route('company.delivery-notes.process', $deliveryNote->id) }}" class="btn btn-success dg-btn">Process Delivery</a>
                        @endif
                        @if ($canPrint)
                            <a href="{{ route('company.delivery-notes.print', $deliveryNote->id) }}" target="_blank" class="btn btn-outline-secondary dg-btn">Print PDF</a>
                        @endif
                        @if ($canCancel)
                            <button type="button" class="btn btn-outline-danger dg-btn" data-bs-toggle="modal" data-bs-target="#dgDeliveryCancelModal">Cancel</button>
                        @endif
                    </nav>
                </div>
            </div>
        </div>
    </header>

    @if ($canCancel)
        <div class="modal fade" id="dgDeliveryCancelModal" tabindex="-1" aria-labelledby="dgDeliveryCancelModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('company.delivery-notes.cancel', $deliveryNote->id) }}">
                        @csrf

                        <div class="modal-header">
                            <h5 class="modal-title" id="dgDeliveryCancelModalLabel">Cancel Delivery Note</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <div class="modal-body">
                            <div class="mb-0">
                                <label for="cancel_reason" class="form-label">Cancel Reason <span class="text-danger">*</span></label>
                                <textarea name="cancel_reason" id="cancel_reason" class="form-control dg-input" rows="4" required>{{ old('cancel_reason') }}</textarea>
                                @error('cancel_reason')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary dg-btn" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-danger dg-btn">Cancel Delivery</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <main class="dg-container">
        <div class="container-fluid">

            @if (session('success'))
                <div class="alert alert-success dg-alert" role="alert">{{ session('success') }}</div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger dg-alert" role="alert">{{ session('error') }}</div>
            @endif

            <section class="dg-section">
                <article class="card dg-card">
                    <header class="card-header dg-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h2 class="h6 mb-0">Delivery Information</h2>
                        @include('company.delivery-notes.partials.status-badge', ['deliveryNote' => $deliveryNote])
                    </header>
                    <div class="card-body dg-card-body">
                        <div class="table-responsive">
                            <table class="table dg-table">
                                <tbody>
                                    <tr><th width="220">Delivery No</th><td>{{ $deliveryNote->delivery_no }}</td></tr>
                                    <tr><th>Financial Year</th><td>{{ $deliveryNote->financialYear->name ?? '-' }}</td></tr>
                                    <tr><th>Delivery Date</th><td>{{ $deliveryNote->delivery_date?->format('d-m-Y') ?? '-' }}</td></tr>
                                    <tr><th>Customer</th><td>{{ $deliveryNote->customer->name ?? '-' }}</td></tr>
                                    <tr><th>Sales Invoice</th><td>{{ $deliveryNote->salesInvoice->invoice_no ?? '-' }}</td></tr>
                                    <tr><th>Employee</th><td>{{ $deliveryNote->employee->full_name ?? '-' }}</td></tr>
                                    <tr><th>Remarks</th><td>{{ $deliveryNote->remarks ?: '-' }}</td></tr>
                                    <tr><th>Created By</th><td>{{ $deliveryNote->creator->name ?? '-' }}</td></tr>
                                    <tr><th>Created At</th><td>{{ $deliveryNote->created_at?->format('d-m-Y H:i') ?? '-' }}</td></tr>
                                    @if ($deliveryNote->isCompleted())
                                        <tr><th>Completed By</th><td>{{ $deliveryNote->completer->name ?? '-' }}</td></tr>
                                        <tr><th>Completed At</th><td>{{ $deliveryNote->completed_at?->format('d-m-Y H:i') ?? '-' }}</td></tr>
                                    @endif
                                    @if ($deliveryNote->isCancelled())
                                        <tr><th>Cancelled By</th><td>{{ $deliveryNote->canceller->name ?? '-' }}</td></tr>
                                        <tr><th>Cancelled At</th><td>{{ $deliveryNote->cancelled_at?->format('d-m-Y H:i') ?? '-' }}</td></tr>
                                        <tr><th>Cancel Reason</th><td>{{ $deliveryNote->cancel_reason ?: '-' }}</td></tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </article>
            </section>

            <section class="dg-section">
                <article class="card dg-card">
                    <header class="card-header dg-card-header">
                        <h2 class="h6 mb-0">Delivery Items</h2>
                    </header>
                    <div class="card-body dg-card-body">
                        <div class="table-responsive">
                            <table class="table dg-table">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th class="text-end">Invoice Qty</th>
                                        <th class="text-end">Planned Qty</th>
                                        <th class="text-end">Delivered Qty</th>
                                        <th class="text-end">Remaining Qty</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($lineItems as $line)
                                        <tr>
                                            <td>{{ $line['item_name'] }}</td>
                                            <td class="text-end">{{ number_format($line['invoice_qty'], 2) }}</td>
                                            <td class="text-end">{{ number_format($line['planned_qty'], 2) }}</td>
                                            <td class="text-end">{{ number_format($line['delivered_qty'], 2) }}</td>
                                            <td class="text-end">{{ number_format($line['remaining_qty'], 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center">No delivery items found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </article>
            </section>

            @if ($deliveryNote->signature || $deliveryNote->attachments->whereIn('document_type', ['photo', 'additional_photo'])->count())
                <section class="dg-section">
                    <article class="card dg-card">
                        <header class="card-header dg-card-header">
                            <h2 class="h6 mb-0">Proof of Delivery</h2>
                        </header>
                        <div class="card-body dg-card-body">
                            @if ($deliveryNote->signature)
                                <div class="mb-3">
                                    <div><strong>Receiver:</strong> {{ $deliveryNote->signature->receiver_name ?? '-' }}</div>
                                    <div><strong>Mobile:</strong> {{ $deliveryNote->signature->receiver_mobile ?? '-' }}</div>
                                    <div class="mt-2">
                                        <img src="{{ asset($storageBase . $deliveryNote->signature->signature_path) }}" alt="Signature" class="img-fluid border" style="max-height:160px;">
                                    </div>
                                </div>
                            @endif

                            <div class="row g-3">
                                @foreach ($deliveryNote->attachments->whereIn('document_type', ['photo', 'additional_photo']) as $attachment)
                                    <div class="col-md-4">
                                        <div class="small text-muted mb-1">{{ ucfirst(str_replace('_', ' ', $attachment->document_type)) }}</div>
                                        <img src="{{ asset($storageBase . $attachment->file_path) }}" alt="Delivery photo" class="img-fluid border rounded">
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </article>
                </section>
            @endif

            <section class="dg-section">
                <article class="card dg-card">
                    <header class="card-header dg-card-header">
                        <h2 class="h6 mb-0">Status History</h2>
                    </header>
                    <div class="card-body dg-card-body">
                        <div class="table-responsive">
                            <table class="table dg-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Previous</th>
                                        <th>Current</th>
                                        <th>Changed By</th>
                                        <th>Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($deliveryNote->statusHistories->sortByDesc('changed_at') as $history)
                                        <tr>
                                            <td>{{ $history->changed_at?->format('d-m-Y H:i') ?? '-' }}</td>
                                            <td>{{ $history->previous_status ? \App\Models\DeliveryNote::statusLabel($history->previous_status) : '-' }}</td>
                                            <td>{{ \App\Models\DeliveryNote::statusLabel($history->current_status) }}</td>
                                            <td>{{ $history->changer->name ?? '-' }}</td>
                                            <td>{{ $history->remarks ?: '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center">No status history recorded.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </article>
            </section>

        </div>
    </main>
</div>

@endsection
