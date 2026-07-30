@extends('admin.layout')

@section('title', 'Registration Details')

@section('content')
<div class="dg-page dg-record-print">
    <div class="dg-page-header dg-print-hide">
        <div class="dg-page-header-content"><h2 class="dg-page-title">Registration Details</h2><p class="dg-page-subtitle">Company registration request and applicant information.</p></div>
        <div class="d-flex gap-2 flex-wrap"><a href="{{ route('admin.registrations') }}" class="btn btn-light dg-btn dg-btn-light">Back</a><button type="button" class="btn btn-primary dg-btn dg-btn-primary" onclick="window.print()">Print A4</button></div>
    </div>

    <article id="printArea" class="dg-card card dg-record-sheet">
        <header class="dg-record-header"><div class="dg-record-brand"><div class="dg-record-logo-fallback" aria-hidden="true">{{ strtoupper(substr($registration->company_name ?? 'R', 0, 1)) }}</div><div><h1 class="dg-record-title">{{ $registration->company_name }}</h1><p class="dg-record-subtitle">Company registration request</p></div></div><div class="dg-record-status"><span class="dg-badge dg-badge-status {{ $registration->status === 'pending' ? 'dg-badge-warning' : ($registration->status === 'approved' ? 'dg-badge-success' : 'dg-badge-danger') }}">{{ ucfirst($registration->status) }}</span><span class="dg-record-id">Registration ID: {{ $registration->id }}</span></div></header>
        <div class="dg-record-grid">
            <section class="dg-record-section" aria-labelledby="registration-company-title"><h2 id="registration-company-title" class="dg-record-section-title">Company Request</h2><dl class="dg-record-list"><div><dt>Company name</dt><dd>{{ $registration->company_name }}</dd></div><div><dt>Country</dt><dd>{{ $registration->country ?? 'N/A' }}</dd></div><div><dt>Requested user limit</dt><dd>{{ $registration->selected_user_limit ?? 'N/A' }}</dd></div><div><dt>Registration status</dt><dd>{{ ucfirst($registration->status) }}</dd></div></dl></section>
            <section class="dg-record-section" aria-labelledby="registration-applicant-title"><h2 id="registration-applicant-title" class="dg-record-section-title">Applicant</h2><dl class="dg-record-list"><div><dt>Full name</dt><dd>{{ $registration->full_name }}</dd></div><div><dt>Username</dt><dd>{{ $registration->username ?? 'N/A' }}</dd></div><div><dt>Email</dt><dd>{{ $registration->email }}</dd></div><div><dt>Mobile</dt><dd>{{ $registration->mobile_no ?? 'N/A' }}</dd></div></dl></section>
            <section class="dg-record-section" aria-labelledby="registration-record-title"><h2 id="registration-record-title" class="dg-record-section-title">Record Information</h2><dl class="dg-record-list"><div><dt>Submitted</dt><dd>{{ $registration->created_at?->format('Y-m-d H:i') ?? 'N/A' }}</dd></div><div><dt>Last updated</dt><dd>{{ $registration->updated_at?->format('Y-m-d H:i') ?? 'N/A' }}</dd></div></dl></section>
        </div>
        <footer class="dg-record-footer">Printed from DG ERP on {{ now()->format('Y-m-d H:i') }}</footer>
    </article>
</div>
@endsection
