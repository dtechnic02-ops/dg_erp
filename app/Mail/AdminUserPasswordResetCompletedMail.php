<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminUserPasswordResetCompletedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $userName)
    {
    }

    public function build(): self
    {
        return $this->subject('DG ERP Password Reset Notice')
            ->view('emails.admin_user_password_reset_completed');
    }
}
