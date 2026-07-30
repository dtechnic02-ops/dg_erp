@extends('admin.layout')

@section('title', 'Company Details')

@section('content')
@php
    $logoPath = $company->logo_path;
    $logoUrl = $logoPath
        ? asset(\Illuminate\Support\Str::startsWith($logoPath, ['companies/', 'storage/']) ? ltrim($logoPath, '/') : 'companies/'.$company->id.'/'.ltrim($logoPath, '/'))
        : null;
@endphp
<div class="dg-page dg-record-print">
    <div class="dg-page-header dg-print-hide">
        <div class="dg-page-header-content">
            <h2 class="dg-page-title">Company Details</h2>
            <p class="dg-page-subtitle">Company profile, administrator, plan, limits, and registration details.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.companies') }}" class="btn btn-light dg-btn dg-btn-light">Back</a>
            <button type="button" class="btn btn-primary dg-btn dg-btn-primary" onclick="window.print()">Print A4</button>
        </div>
    </div>

    <article id="printArea" class="dg-card card dg-record-sheet">
        <header class="dg-record-header">
            <div class="dg-record-brand">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" class="dg-record-logo" alt="{{ $company->company_name }} logo">
                @else
                    <div class="dg-record-logo-fallback" aria-hidden="true">{{ strtoupper(substr($company->company_name ?? 'C', 0, 1)) }}</div>
                @endif
                <div><h1 class="dg-record-title">{{ $company->company_name }}</h1><p class="dg-record-subtitle">Company profile and subscription summary</p></div>
            </div>
            <div class="dg-record-status"><span class="dg-badge dg-badge-status {{ $company->status === 'active' ? 'dg-badge-success' : 'dg-badge-danger' }}">{{ ucfirst($company->status) }}</span><span class="dg-record-id">Company ID: {{ $company->id }}</span></div>
        </header>

        <div class="dg-record-grid">
            <section class="dg-record-section" aria-labelledby="company-contact-title"><h2 id="company-contact-title" class="dg-record-section-title">Company & Contact</h2><dl class="dg-record-list"><div><dt>Email</dt><dd>{{ $company->email ?? 'N/A' }}</dd></div><div><dt>Mobile</dt><dd>{{ $company->mobile ?? 'N/A' }}</dd></div><div><dt>Telephone</dt><dd>{{ $company->telephone ?? 'N/A' }}</dd></div><div><dt>Fax</dt><dd>{{ $company->fax_no ?? 'N/A' }}</dd></div><div><dt>Website</dt><dd>{{ $company->website ?? 'N/A' }}</dd></div></dl></section>
            <section class="dg-record-section" aria-labelledby="admin-title"><h2 id="admin-title" class="dg-record-section-title">Company Administrator</h2><dl class="dg-record-list"><div><dt>Name</dt><dd>{{ $companyAdmin?->name ?? 'Not available' }}</dd></div><div><dt>Email</dt><dd>{{ $companyAdmin?->email ?? 'Not available' }}</dd></div><div><dt>Account status</dt><dd>{{ ucfirst($companyAdmin?->account_status ?? 'N/A') }}</dd></div><div><dt>Role</dt><dd>Company Admin</dd></div></dl></section>
            <section class="dg-record-section" aria-labelledby="plan-title"><h2 id="plan-title" class="dg-record-section-title">Plan & Expiry</h2><dl class="dg-record-list"><div><dt>Plan</dt><dd>{{ $subscription?->plan?->name ?? 'No current plan' }}</dd></div><div><dt>Subscription status</dt><dd>{{ ucfirst($subscription?->status ?? 'N/A') }}</dd></div><div><dt>Start date</dt><dd>{{ $subscription?->start_date?->format('Y-m-d') ?? 'N/A' }}</dd></div><div><dt>Expiry date</dt><dd>{{ $subscription?->expiry_date?->format('Y-m-d') ?? ($company->expiry_date ?? 'N/A') }}</dd></div><div><dt>Billing cycle</dt><dd>{{ $subscription?->billingCycle?->name ?? 'N/A' }}</dd></div></dl></section>
            <section class="dg-record-section" aria-labelledby="limits-title"><h2 id="limits-title" class="dg-record-section-title">Limits</h2><dl class="dg-record-list"><div><dt>Selected user limit</dt><dd>{{ $company->selected_user_limit ?? 'N/A' }}</dd></div><div><dt>Subscription staff limit</dt><dd>{{ $subscription?->staff_limit ?? 'N/A' }}</dd></div><div><dt>Customer limit</dt><dd>{{ $company->selected_customer_limit ?? 'N/A' }}</dd></div></dl></section>
            <section class="dg-record-section" aria-labelledby="address-title"><h2 id="address-title" class="dg-record-section-title">Address & Registration</h2><dl class="dg-record-list"><div><dt>Address</dt><dd>{{ $company->address ?? 'N/A' }}{{ $company->address_line_2 ? ', '.$company->address_line_2 : '' }}</dd></div><div><dt>Country</dt><dd>{{ $company->country ?? 'N/A' }}</dd></div><div><dt>Language</dt><dd>{{ $company->language ?? 'N/A' }}</dd></div><div><dt>PAN</dt><dd>{{ $company->pan_number ?? 'N/A' }}</dd></div><div><dt>VAT</dt><dd>{{ $company->vat_number ?? 'N/A' }}</dd></div></dl></section>
            <section class="dg-record-section" aria-labelledby="audit-title"><h2 id="audit-title" class="dg-record-section-title">Record Information</h2><dl class="dg-record-list"><div><dt>Created</dt><dd>{{ $company->created_at?->format('Y-m-d H:i') ?? 'N/A' }}</dd></div><div><dt>Last updated</dt><dd>{{ $company->updated_at?->format('Y-m-d H:i') ?? 'N/A' }}</dd></div><div><dt>Company status</dt><dd>{{ ucfirst($company->status) }}</dd></div></dl></section>
        </div>
        <footer class="dg-record-footer">Printed from DG ERP on {{ now()->format('Y-m-d H:i') }}</footer>
    </article>
</div>
@endsection
