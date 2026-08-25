<p>Hello {{ $userName }},</p>
<p>{{ $initiatorName }} initiated a password reset for your DG ERP account.</p>
<p><a href="{{ $resetUrl }}">Set a new password</a></p>
<p>This secure link expires in {{ $expiresInMinutes }} minutes and can be used only once. If you were not expecting this message, contact your administrator.</p>
