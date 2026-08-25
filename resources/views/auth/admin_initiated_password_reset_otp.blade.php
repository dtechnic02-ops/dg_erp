<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Password Reset — DG ERP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<main class="dg-page">
    <div class="container py-5">
        <section class="dg-card card mx-auto" style="max-width: 560px;">
            <header class="dg-card-header card-header"><h1 class="h4 mb-0">Verify Password Reset</h1></header>
            <div class="dg-card-body card-body">
                @if(session('success'))<div class="alert alert-success dg-alert" role="alert">{{ session('success') }}</div>@endif
                <p class="text-muted">Enter the six-digit verification code sent to {{ $resetRequest->user_email }}.</p>
                <form method="POST" action="{{ route('password-reset.otp.verify', ['challenge' => $challenge]) }}" class="dg-form">
                    @csrf
                    <div class="mb-3">
                        <label for="otp" class="form-label">Verification Code</label>
                        <input id="otp" name="otp" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" class="form-control dg-input @error('otp') is-invalid @enderror" autocomplete="one-time-code" required autofocus>
                        @error('otp')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">The code expires in 10 minutes and allows a maximum of five attempts.</div>
                    </div>
                    <button type="submit" class="btn btn-primary dg-btn dg-btn-primary">Verify and Change Password</button>
                </form>
            </div>
        </section>
    </div>
</main>
</body>
</html>
