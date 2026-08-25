<?php

namespace App\Http\Controllers;

use App\Mail\AdminUserPasswordResetCompletedMail;
use App\Mail\AdminUserPasswordResetOtpMail;
use App\Services\PlatformMailService;
use App\Services\UserPasswordResetService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class PasswordResetController extends Controller
{
    public function __construct(
        private UserPasswordResetService $passwordResets,
        private PlatformMailService $platformMail,
    ) {}

    public function showPasswordForm(string $token)
    {
        $resetRequest = $this->passwordResets->openLink($token);

        abort_unless($resetRequest, 404, 'This password reset link is invalid or expired.');

        return view('auth.admin_initiated_password_reset', compact('token', 'resetRequest'));
    }

    public function submitPassword(Request $request, string $token)
    {
        $request->validate([
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()],
        ]);

        $result = $this->passwordResets->beginOtp($token, $request->string('password')->toString());

        abort_unless($result, 404, 'This password reset link is invalid or expired.');

        [$resetRequest, $challenge, $otp] = $result;

        try {
            $this->platformMail->send($resetRequest->user_email, new AdminUserPasswordResetOtpMail($otp, $resetRequest->user?->name ?? 'DG ERP user'));
        } catch (\Throwable) {
            $this->passwordResets->invalidate($resetRequest);

            return redirect()->route('login')->with('error', 'Unable to send the verification code. Ask your administrator to start a new password reset.');
        }

        return redirect()
            ->route('password-reset.otp.show', ['challenge' => $challenge])
            ->with('success', 'A verification code was sent to your registered email address.');
    }

    public function showOtpForm(string $challenge)
    {
        $resetRequest = $this->passwordResets->openOtpChallenge($challenge);

        abort_unless($resetRequest, 404, 'This password reset verification request is invalid or expired.');

        return view('auth.admin_initiated_password_reset_otp', compact('challenge', 'resetRequest'));
    }

    public function verifyOtp(Request $request, string $challenge)
    {
        $validated = $request->validate(['otp' => ['required', 'digits:6']]);
        $resetRequest = $this->passwordResets->openOtpChallenge($challenge);

        abort_unless($resetRequest, 404, 'This password reset verification request is invalid or expired.');

        $result = $this->passwordResets->verifyOtp($challenge, $validated['otp']);

        if ($result !== 'completed') {
            $message = match ($result) {
                'expired' => 'The verification code has expired. Ask your administrator to start a new password reset.',
                'locked' => 'Maximum verification attempts exceeded. Ask your administrator to start a new password reset.',
                default => 'The verification code is invalid. Please try again.',
            };

            return back()->withErrors(['otp' => $message]);
        }

        try {
            $this->platformMail->send($resetRequest->user_email, new AdminUserPasswordResetCompletedMail($resetRequest->user?->name ?? 'DG ERP user'));
        } catch (\Throwable) {
            return redirect()->route('login')->with('warning', 'Your password was changed, but the confirmation email could not be sent.');
        }

        return redirect()->route('login')->with('success', 'Your password has been changed. Sign in with your new password.');
    }
}
