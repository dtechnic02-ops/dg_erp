<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password — DG ERP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<main class="dg-page">
    <div class="container py-5">
        <section class="dg-card card mx-auto" style="max-width: 560px;">
            <header class="dg-card-header card-header"><h1 class="h4 mb-0">Set New Password</h1></header>
            <div class="dg-card-body card-body">
                <p class="text-muted">Enter a new password for {{ $resetRequest->user_email }}. A verification code will then be sent to this email address.</p>
                <form method="POST" action="{{ route('password-reset.password.submit', ['token' => $token]) }}" class="dg-form">
                    @csrf
                    <div class="mb-3">
                        <label for="password" class="form-label">New Password</label>
                        <input id="password" name="password" type="password" class="form-control dg-input @error('password') is-invalid @enderror" autocomplete="new-password" required autofocus>
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Use at least 12 characters with upper- and lower-case letters, numbers, and symbols.</div>
                    </div>
                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Confirm Password</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" class="form-control dg-input" autocomplete="new-password" required>
                    </div>
                    <button type="submit" class="btn btn-primary dg-btn dg-btn-primary">Send Verification Code</button>
                </form>
            </div>
        </section>
    </div>
</main>
</body>
</html>
