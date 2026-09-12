@extends('company.layout')
@section('title', 'CBMS Transmission Status')
@section('content')
@php
    $statuses = [
        'pending' => 'Pending', 'queued' => 'Queued', 'processing' => 'Processing', 'submitted' => 'Submitted',
        'duplicate_requires_reconciliation' => 'Requires Reconciliation', 'retryable_failure' => 'Retryable Failure',
        'permanent_failure' => 'Permanent Failure', 'not_ready' => 'Not Ready',
    ];
@endphp
<div class="dg-page">
    <div class="dg-toolbar"><h4>CBMS Transmission Status</h4></div>
    <div class="dg-container">
        <div class="row g-3 mb-3">
            @foreach($statuses as $key => $label)
                <div class="col-md-3"><div class="card dg-card"><div class="card-body"><small>{{ $label }}</small><h4>{{ (int) ($summary[$key] ?? 0) }}</h4></div></div></div>
            @endforeach
        </div>
        <div class="card dg-card"><div class="card-body table-responsive">
            <table class="table"><thead><tr><th>Document Type</th><th>Document Number</th><th>Fiscal Year</th><th>Issue Date</th><th>Total</th><th>Status</th><th>Attempts</th><th>Last Attempt</th><th>Response</th><th>Action</th></tr></thead><tbody>
            @forelse($transmissions as $transmission)
                @php($document = $transmission->transmittable)
                <tr>
                    <td>{{ $transmission->endpoint_type === 'bill_return' ? 'Credit Note' : 'Sales Invoice' }}</td>
                    <td>{{ $document?->invoice_no ?? $document?->return_no ?? '#'.$transmission->transmittable_id }}</td>
                    <td>{{ $document?->financialYear?->name ?? '-' }}</td>
                    <td>{{ $document?->fiscal_issued_at?->format('Y-m-d H:i:s') ?? '-' }}</td>
                    <td>{{ number_format((float) ($document?->grand_total ?? 0), 2) }}</td>
                    <td>{{ $statuses[$transmission->status] ?? $transmission->status }}</td>
                    <td>{{ $transmission->attempt_count }}</td>
                    <td>{{ $transmission->last_attempted_at?->format('Y-m-d H:i:s') ?: '-' }}</td>
                    <td>{{ $transmission->response_code ?: '-' }}</td>
                    <td><a class="btn btn-sm btn-outline-primary" href="{{ route('company.settings.cbms-transmissions.show', $transmission) }}">View details</a></td>
                </tr>
            @empty<tr><td colspan="10">No CBMS transmission records.</td></tr>@endforelse
            </tbody></table>{{ $transmissions->links() }}
        </div></div>
    </div>
</div>
@endsection
