<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CompanyFactoryResetOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $companyName,
        public string $requestedByName,
        public string $otp,
        public int $expiresInMinutes,
    ) {
    }

    public function build(): self
    {
        return $this->subject('Company Factory Reset Verification Code')
            ->view('emails.company_factory_reset_otp');
    }
}
