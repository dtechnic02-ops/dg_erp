<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserPasswordResetLinkMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $userName,
        public string $initiatorName,
        public string $resetUrl,
        public int $expiresInMinutes,
    ) {
    }

    public function build(): self
    {
        return $this->subject('DG ERP Secure Password Reset')
            ->view('emails.user_password_reset_link');
    }
}
