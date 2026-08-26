<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CompanyRegistrationApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $adminName,
        public string $companyName,
        public string $loginEmail,
        public string $loginUrl,
    ) {
    }

    public function build(): self
    {
        return $this->subject('Your Company Registration Has Been Approved')
            ->view('emails.company_registration_approved');
    }
}
