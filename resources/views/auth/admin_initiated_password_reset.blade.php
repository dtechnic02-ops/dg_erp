<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password — DG ERP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="dg-reset-page">
<header class="dg-reset-header">
    <a href="{{ route('login') }}" class="dg-reset-brand" aria-label="DG ERP login">
        <img src="{{ asset('logo.png') }}" alt="DG ERP logo">
        <span>DG ERP</span>
    </a>
    <a href="{{ route('login') }}" class="dg-reset-back"><span aria-hidden="true">&larr;</span> Back to Login</a>
</header>

<main class="dg-reset-main">
    <section class="dg-reset-card" aria-labelledby="dg-reset-title">
        <div class="dg-reset-intro">
            <div class="dg-reset-lock" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="5" y="10" width="14" height="11" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v3"></path></svg>
            </div>
            <h1 id="dg-reset-title" class="dg-reset-title">Set New Password</h1>
            <p class="dg-reset-description">
                Enter a new password for <span class="dg-reset-email">{{ $resetRequest->user_email }}</span>.<br>
                A verification code will then be sent to this email address.
            </p>
        </div>

        <form method="POST" action="{{ route('password-reset.password.submit', ['token' => $token]) }}" class="dg-form">
            @csrf
            <div class="dg-reset-field">
                <label for="password" class="dg-reset-label">New Password</label>
                <div class="dg-reset-input-wrap">
                    <svg class="dg-reset-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="5" y="10" width="14" height="11" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v3"></path></svg>
                    <input id="password" name="password" type="password" class="dg-reset-input @error('password') is-invalid @enderror" autocomplete="new-password" required autofocus aria-describedby="@error('password')password-error @enderror password-help password-strength-label" @error('password') aria-invalid="true" @enderror>
                    <button type="button" class="dg-reset-toggle" data-password-toggle="password" aria-label="Show new password" aria-pressed="false">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"></path><circle cx="12" cy="12" r="2.5"></circle></svg>
                    </button>
                </div>
                @error('password')<div id="password-error" class="dg-reset-error" role="alert">{{ $message }}</div>@enderror
                <div id="password-help" class="dg-reset-help">Use at least 12 characters with upper- and lower-case letters, numbers, and symbols.</div>
                <div class="dg-reset-strength" data-password-strength data-level="0" style="--dg-strength-color: #dc3545;">
                    <div id="password-strength-label" class="dg-reset-strength-label" aria-live="polite">Password strength: <strong>Not entered</strong></div>
                    <div class="dg-reset-strength-track" aria-hidden="true"><span></span><span></span><span></span><span></span></div>
                </div>
            </div>

            <div class="dg-reset-field">
                <label for="password_confirmation" class="dg-reset-label">Confirm Password</label>
                <div class="dg-reset-input-wrap">
                    <svg class="dg-reset-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="5" y="10" width="14" height="11" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v3"></path></svg>
                    <input id="password_confirmation" name="password_confirmation" type="password" class="dg-reset-input" autocomplete="new-password" required>
                    <button type="button" class="dg-reset-toggle" data-password-toggle="password_confirmation" aria-label="Show confirmed password" aria-pressed="false">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"></path><circle cx="12" cy="12" r="2.5"></circle></svg>
                    </button>
                </div>
            </div>

            <div class="dg-reset-info" role="note">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 11v6M12 7.5h.01"></path></svg>
                <span>You will receive a verification code on your email. Please check your inbox (and spam folder).</span>
            </div>

            <button type="submit" class="btn btn-primary dg-btn dg-btn-primary dg-reset-submit">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m3 11 18-8-7 18-3-7-8-3Z"></path><path d="m11 14 4-4"></path></svg>
                Send Verification Code
            </button>
        </form>
    </section>
</main>

<script>
    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.passwordToggle);
            const showing = input.type === 'text';
            input.type = showing ? 'password' : 'text';
            button.setAttribute('aria-pressed', showing ? 'false' : 'true');
            button.setAttribute('aria-label', `${showing ? 'Show' : 'Hide'} ${input.id === 'password' ? 'new' : 'confirmed'} password`);
        });
    });

    const passwordInput = document.getElementById('password');
    const strength = document.querySelector('[data-password-strength]');
    const strengthText = strength.querySelector('strong');
    const levels = [
        { label: 'Not entered', color: '#dc3545' },
        { label: 'Weak', color: '#dc3545' },
        { label: 'Fair', color: '#fd7e14' },
        { label: 'Medium', color: '#d39e00' },
        { label: 'Strong', color: '#198754' },
    ];

    passwordInput.addEventListener('input', () => {
        const value = passwordInput.value;
        const score = value === '' ? 0 : Math.min(4, [
            value.length >= 12,
            /[a-z]/.test(value) && /[A-Z]/.test(value),
            /\d/.test(value),
            /[^A-Za-z0-9]/.test(value),
        ].filter(Boolean).length);

        strength.dataset.level = score;
        strength.style.setProperty('--dg-strength-color', levels[score].color);
        strengthText.textContent = levels[score].label;
        strengthText.style.color = levels[score].color;
    });
</script>
</body>
</html>
