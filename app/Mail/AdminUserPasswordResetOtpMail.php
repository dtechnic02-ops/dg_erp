<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminUserPasswordResetOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $otp, public string $targetName)
    {
    }

    public function build(): self
    {
        return $this->subject('DG ERP Password Reset OTP')
            ->view('emails.admin_user_password_reset_otp');
    }
}
