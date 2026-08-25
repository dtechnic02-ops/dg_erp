<?php

namespace App\Services;

use App\Models\PlatformSmtpSetting;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

class PlatformMailService
{
    public const MAILER_NAME = 'platform_smtp';

    public function __construct(private PlatformSettingService $settings)
    {
    }

    public function send(string $recipient, Mailable $mailable): void
    {
        $this->configureMailer();
        Mail::mailer(self::MAILER_NAME)->to($recipient)->send($mailable);
    }

    public function sendRaw(string $recipient, string $subject, string $content): void
    {
        $this->configureMailer();
        Mail::mailer(self::MAILER_NAME)->raw($content, function ($message) use ($recipient, $subject): void {
            $message->to($recipient)->subject($subject);
        });
    }

    private function configureMailer(): void
    {
        $smtp = $this->settings->settings()->smtpSetting;

        if (! $smtp) {
            throw new RuntimeException('Platform SMTP configuration is missing.');
        }

        if (! $smtp->is_active) {
            throw new RuntimeException('Platform SMTP configuration is disabled.');
        }

        if (! $this->isComplete($smtp)) {
            throw new RuntimeException('Platform SMTP configuration is incomplete.');
        }

        Config::set('mail.mailers.'.self::MAILER_NAME, [
            'transport' => 'smtp',
            'host' => $smtp->host,
            'port' => $smtp->port,
            'username' => $smtp->username,
            'password' => $smtp->password,
            'scheme' => $smtp->encryption === 'starttls' ? null : $smtp->encryption,
            'timeout' => null,
        ]);
        Config::set('mail.from', [
            'address' => $smtp->from_address,
            'name' => $smtp->from_name,
        ]);

        Mail::purge(self::MAILER_NAME);
    }

    private function isComplete(PlatformSmtpSetting $smtp): bool
    {
        return $smtp->mailer === 'smtp'
            && filled($smtp->host)
            && is_int($smtp->port)
            && $smtp->port >= 1
            && $smtp->port <= 65535
            && filled($smtp->username)
            && filled($smtp->password)
            && filter_var($smtp->from_address, FILTER_VALIDATE_EMAIL) !== false
            && filled($smtp->from_name)
            && in_array($smtp->encryption, [null, '', 'tls', 'ssl', 'starttls'], true);
    }
}
