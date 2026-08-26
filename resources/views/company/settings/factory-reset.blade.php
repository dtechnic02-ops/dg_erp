@extends('company.layout')

@section('title', 'Factory Reset')

@section('content')
<div class="dg-page">
    <div class="dg-page-header">
        <div>
            <h2 class="dg-page-title">Factory Reset</h2>
            <p class="dg-page-subtitle">Permanently remove this Company's operational business data and return it to a fresh operational state.</p>
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success dg-alert">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger dg-alert">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger dg-alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="dg-card card border-danger">
        <div class="dg-card-header card-header text-danger">Irreversible business-data reset</div>
        <div class="dg-card-body card-body">
            <h3 class="h6 text-success">Preserved</h3>
            <ul><li>Company identity and Company ID</li><li>All Company Admin accounts and passwords</li><li>Company profile, logo and signature</li><li>Subscription, billing history and entitlement</li><li>Company permissions and WhatsApp settings</li></ul>
            <h3 class="h6 text-danger">Removed</h3>
            <ul><li>Company Staff and Staff access</li><li>Customers, suppliers, products and services</li><li>Sales, purchases, returns, stock and inventory</li><li>Accounting, journals, opening balances, income and expenses</li><li>Loans, payroll, delivery, CRM, quotations and operational files</li><li>Financial Years and operational Cash/Bank accounts</li></ul>
            <p><strong>Company:</strong> {{ $company->company_name }} (ID {{ $company->id }})</p>
            <p><strong>OTP recipient:</strong> {{ $admin->email }}</p>

            @if(!$challengeId)
                <form method="POST" action="{{ route('company.settings.factory-reset.otp') }}" class="dg-form">
                    @csrf
                    <label for="reset-confirmation" class="form-label">Type <strong>RESET MY COMPANY</strong></label>
                    <input id="reset-confirmation" class="form-control dg-input mb-3" name="confirmation_phrase" autocomplete="off" required>
                    <button type="submit" class="btn btn-danger dg-btn dg-btn-danger">Send Verification Code</button>
                </form>
            @else
                <form method="POST" action="{{ route('company.settings.factory-reset.execute') }}" class="dg-form" autocomplete="off">
                    @csrf
                    <input type="hidden" name="challenge_id" value="{{ $challengeId }}">
                    <div class="mb-3"><label for="factory-current-password" class="form-label">Current password</label><input id="factory-current-password" type="password" class="form-control dg-input" name="current_password" autocomplete="current-password" required></div>
                    <div class="mb-3"><label for="factory-otp" class="form-label">6-digit verification code</label><input id="factory-otp" class="form-control dg-input" name="otp" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required></div>
                    <div class="mb-3"><label for="factory-final-confirmation" class="form-label">Type <strong>RESET MY COMPANY</strong> again</label><input id="factory-final-confirmation" class="form-control dg-input" name="confirmation_phrase" autocomplete="off" required></div>
                    <button type="submit" class="btn btn-danger dg-btn dg-btn-danger" onclick="return confirm('Permanently reset this Company’s operational business data?')">Verify &amp; Factory Reset</button>
                </form>
            @endif
        </div>
    </div>
    @if($cleanupAudit)
        <div class="dg-card card mt-3 border-warning">
            <div class="dg-card-body card-body">
                <p>Verified operational file cleanup from the last Factory Reset requires retry.</p>
                <form method="POST" action="{{ route('company.settings.factory-reset.retry-files', $cleanupAudit) }}">@csrf<button type="submit" class="btn btn-warning dg-btn dg-btn-warning">Retry Verified File Cleanup</button></form>
            </div>
        </div>
    @endif
</div>
@endsection
