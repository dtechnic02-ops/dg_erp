@extends('company.layout')
@section('title', 'CBMS Transmission Evidence')
@section('content')
@php
    $document = $transmission->transmittable;
    $isAdmin = (int) auth()->user()->role_id === \App\Models\Role::COMPANY_ADMIN_ID;
@endphp
<div class="dg-page"><div class="dg-toolbar"><h4>CBMS Transmission Evidence</h4><a class="btn btn-outline-secondary" href="{{ route('company.settings.cbms-transmissions.index') }}">Back</a></div><div class="dg-container">
    <div class="card dg-card mb-3"><div class="card-body row g-3">
        <div class="col-md-3"><strong>Document</strong><div>{{ $transmission->endpoint_type === 'bill_return' ? 'Credit Note' : 'Sales Invoice' }}</div></div>
        <div class="col-md-3"><strong>Number</strong><div>{{ $document?->invoice_no ?? $document?->return_no ?? '#'.$transmission->transmittable_id }}</div></div>
        <div class="col-md-3"><strong>Fiscal Year</strong><div>{{ $document?->financialYear?->name ?? '-' }}</div></div>
        <div class="col-md-3"><strong>Total</strong><div>{{ number_format((float) ($document?->grand_total ?? 0), 2) }}</div></div>
        <div class="col-md-3"><strong>Status</strong><div>{{ $transmission->status }}</div></div>
        <div class="col-md-3"><strong>Payload hash</strong><div class="text-break">{{ $transmission->payload_hash ?: '-' }}</div></div>
        <div class="col-md-3"><strong>Response</strong><div>{{ $transmission->response_code ?: '-' }} / {{ $transmission->response_category ?: '-' }}</div></div>
        <div class="col-md-3"><strong>Last attempt</strong><div>{{ $transmission->last_attempted_at?->format('Y-m-d H:i:s') ?: '-' }}</div></div>
        @if($transmission->status === 'duplicate_requires_reconciliation')
            <div class="col-12 alert alert-warning mb-0">Awaiting authoritative external verification. A duplicate response is not treated as success.</div>
        @endif
        @if($transmission->status === 'not_ready')
            <div class="col-12 alert alert-danger mb-0">Not ready: {{ implode(', ', $transmission->response_body_redacted['reason_codes'] ?? []) }}</div>
        @endif
        @if($isAdmin)
            <div class="col-12 d-flex gap-2">
                @if($transmission->status === 'not_ready')
                    <form method="POST" action="{{ route('company.settings.cbms-transmissions.queue', $transmission) }}">@csrf<button class="btn btn-primary">Queue now</button></form>
                @elseif($transmission->status === 'retryable_failure')
                    <form method="POST" action="{{ route('company.settings.cbms-transmissions.retry', $transmission) }}">@csrf<button class="btn btn-primary">Retry</button></form>
                @elseif($transmission->status === 'duplicate_requires_reconciliation')
                    <form method="POST" action="{{ route('company.settings.cbms-transmissions.reconcile', $transmission) }}">@csrf<button class="btn btn-outline-primary">Recheck external evidence</button></form>
                @endif
            </div>
        @endif
    </div></div>
    <div class="card dg-card"><div class="card-body table-responsive"><h5>Immutable Attempt History</h5>
        <table class="table"><thead><tr><th>#</th><th>Attempted</th><th>Transport</th><th>HTTP</th><th>CBMS</th><th>Parser</th><th>Realtime</th><th>Result</th><th>Evidence</th></tr></thead><tbody>
        @forelse($transmission->attempts as $attempt)<tr><td>{{ $attempt->attempt_number }}</td><td>{{ $attempt->attempted_at?->format('Y-m-d H:i:s') }}</td><td>{{ $attempt->transport_classification }}</td><td>{{ $attempt->http_status ?: '-' }}</td><td>{{ $attempt->response_code ?: '-' }}</td><td>{{ $attempt->parser_classification ?: '-' }}</td><td>{{ $attempt->is_realtime ? 'Yes' : 'No' }}</td><td>{{ $attempt->result_status }}</td><td class="text-break">{{ $attempt->response_excerpt_redacted ?: '-' }}</td></tr>
        @empty<tr><td colspan="9">No transmission attempt has been made.</td></tr>@endforelse
        </tbody></table>
    </div></div>
</div></div>
@endsection
