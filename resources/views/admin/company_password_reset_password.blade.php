@extends('admin.layout')

@section('title', 'Set Company Admin Password')

@section('content')
    <div class="dg-page">
        <div class="dg-page-header"><div><h2 class="dg-page-title">Set Company Admin Password</h2><p class="dg-page-subtitle">OTP verification succeeded for {{ $user->name }} at {{ $company->company_name }}.</p></div></div>
        <section class="dg-card card"><div class="dg-card-header card-header">New password</div><div class="dg-card-body card-body">
            <form method="POST" action="{{ route('admin.company.reset.password', $company) }}" class="dg-form">@csrf
                <div class="mb-3"><label for="password" class="form-label">New password</label><input id="password" name="password" type="password" class="form-control dg-input @error('password') is-invalid @enderror" autocomplete="new-password" required>@error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror<div class="form-text">Use at least 12 characters with upper- and lower-case letters, numbers, and symbols.</div></div>
                <div class="mb-3"><label for="password_confirmation" class="form-label">Confirm new password</label><input id="password_confirmation" name="password_confirmation" type="password" class="form-control dg-input" autocomplete="new-password" required></div>
                <div class="d-flex gap-2 flex-wrap"><button type="submit" class="btn btn-primary dg-btn dg-btn-primary">Update Password</button><a href="{{ route('admin.companies') }}" class="btn btn-light dg-btn dg-btn-light">Cancel</a></div>
            </form>
        </div></section>
    </div>
@endsection
