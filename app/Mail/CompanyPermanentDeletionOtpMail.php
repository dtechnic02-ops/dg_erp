<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CompanyPermanentDeletionOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $companyName,
        public int $companyId,
        public string $requestedByName,
        public string $otp,
        public int $expiresInMinutes,
    ) {
    }

    public function build(): self
    {
        return $this->subject('Company Deletion Verification Code')
            ->view('emails.company_permanent_deletion_otp');
    }
}
