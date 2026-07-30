@extends('admin.layout')

@section('title', 'Verify Password Reset')

@section('content')
    <div class="dg-page">
        <div class="dg-page-header">
            <div>
                <h2 class="dg-page-title">Verify Password Reset</h2>
                <p class="dg-page-subtitle">Enter the six-digit verification code sent to your registered email address.</p>
            </div>
        </div>

        <section class="dg-card card">
            <div class="dg-card-header card-header">Resetting password for {{ $user->name }}</div>
            <div class="dg-card-body card-body">
                @if(session('success'))<div class="alert alert-success dg-alert" role="alert">{{ session('success') }}</div>@endif
                <form method="POST" action="{{ route('admin.user.reset.verify', $user) }}" class="dg-form">
                    @csrf
                    <div class="mb-3">
                        <label for="otp" class="form-label">Verification code</label>
                        <input id="otp" name="otp" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" class="form-control dg-input @error('otp') is-invalid @enderror" autocomplete="one-time-code" required autofocus>
                        @error('otp')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">The code expires in 10 minutes and allows a maximum of five attempts.</div>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="submit" class="btn btn-primary dg-btn dg-btn-primary">Verify OTP</button>
                        <a href="{{ route('admin.users') }}" class="btn btn-light dg-btn dg-btn-light">Cancel</a>
                    </div>
                </form>
            </div>
        </section>
    </div>
@endsection
