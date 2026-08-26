@extends('admin.layout')

@section('title', 'Permanent Company Delete')

@section('content')
<div class="dg-page">
    <div class="dg-page-header">
        <div>
            <h2 class="dg-page-title">Permanent Company Delete</h2>
            <p class="dg-page-subtitle">This operation permanently removes the selected Company and all verified Company-owned data and files.</p>
        </div>
        <a href="{{ route('admin.company.show', $company) }}" class="btn btn-light dg-btn dg-btn-light">Back</a>
    </div>

    @if(session('success'))<div class="alert alert-success dg-alert">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger dg-alert">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger dg-alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="dg-card card border-danger">
        <div class="dg-card-header card-header text-danger">Irreversible destructive action</div>
        <div class="dg-card-body card-body">
            <dl class="dg-record-list mb-4">
                <div><dt>Company</dt><dd>{{ $company->company_name }}</dd></div>
                <div><dt>Company ID</dt><dd>{{ $company->id }}</dd></div>
                <div><dt>Company email</dt><dd>{{ $company->email ?? 'N/A' }}</dd></div>
                <div><dt>OTP recipient</dt><dd>{{ $admin->email }} (authenticated Super Admin)</dd></div>
            </dl>

            <p class="text-danger"><strong>Permanent deletion removes Company users, transactions, accounting, inventory, subscriptions, settings, and Company-owned files. It cannot be undone.</strong></p>

            @if(!$challengeId)
                <form method="POST" action="{{ route('admin.company.permanent-delete.otp', $company) }}" class="dg-form">
                    @csrf
                    <label for="company-name-confirmation" class="form-label">Type the exact Company name: <strong>{{ $company->company_name }}</strong></label>
                    <input id="company-name-confirmation" class="form-control dg-input mb-3" name="company_name_confirmation" autocomplete="off" required>
                    <button type="submit" class="btn btn-danger dg-btn dg-btn-danger">Send Verification Code</button>
                </form>
            @else
                <form method="POST" action="{{ route('admin.company.permanent-delete.destroy', $company) }}" class="dg-form">
                    @csrf
                    <input type="hidden" name="challenge_id" value="{{ $challengeId }}">
                    <div class="mb-3">
                        <label for="deletion-otp" class="form-label">6-digit verification code</label>
                        <input id="deletion-otp" class="form-control dg-input" name="otp" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required>
                    </div>
                    <div class="mb-3">
                        <label for="final-company-name-confirmation" class="form-label">Type the exact Company name again</label>
                        <input id="final-company-name-confirmation" class="form-control dg-input" name="company_name_confirmation" autocomplete="off" required>
                    </div>
                    <button type="submit" class="btn btn-danger dg-btn dg-btn-danger" onclick="return confirm('Permanently delete this Company and all of its owned data?')">Verify &amp; Permanently Delete</button>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
