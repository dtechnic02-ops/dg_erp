<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePlatformBrandingRequest;
use App\Http\Requests\UpdatePlatformGeneralSettingRequest;
use App\Http\Requests\UpdatePlatformPaymentGatewayRequest;
use App\Http\Requests\UpdatePlatformSmtpRequest;
use App\Http\Requests\UpdatePlatformSocialLinksRequest;
use App\Models\Role;
use App\Services\PlatformSettingService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

class PlatformSettingController extends Controller
{
    public function __construct(private PlatformSettingService $settings) {}

    public function index()
    {
        $this->authorizeSuperAdmin();
        $setting = $this->settings->settings()->load(['socialLinks', 'smtpSetting', 'paymentGateways']);
        return view('admin.platform_settings.index', ['setting' => $setting, 'gateways' => $setting->paymentGateways->keyBy('gateway')]);
    }

    public function updateGeneral(UpdatePlatformGeneralSettingRequest $request)
    {
        $this->authorizeSuperAdmin();
        $this->settings->updateGeneral($request->validated(), $request->user()->id);
        return back()->with('success', 'Platform settings updated.');
    }

    public function updateBranding(UpdatePlatformBrandingRequest $request)
    {
        $this->authorizeSuperAdmin();
        $this->settings->updateBranding($request->validated(), $request->user()->id);
        return back()->with('success', 'Platform branding updated.');
    }

    public function updateSocialLinks(UpdatePlatformSocialLinksRequest $request)
    {
        $this->authorizeSuperAdmin();
        $this->settings->updateSocialLinks($request->validated('links') ?? []);
        return back()->with('success', 'Social media links updated.');
    }

    public function updateSmtp(UpdatePlatformSmtpRequest $request)
    {
        $this->authorizeSuperAdmin();
        $this->settings->updateSmtp($request->validated());
        return back()->with('success', 'SMTP configuration stored securely.');
    }

    public function testSmtp()
    {
        $this->authorizeSuperAdmin();
        $smtp = $this->settings->settings()->smtpSetting;
        if (! $smtp || ! $smtp->is_active || ! $smtp->getRawOriginal('password')) { return back()->with('error', 'An active SMTP configuration with a password is required before testing.'); }

        try {
            Config::set('mail.mailers.platform_smtp', ['transport' => 'smtp', 'host' => $smtp->host, 'port' => $smtp->port, 'username' => $smtp->username, 'password' => $smtp->password, 'scheme' => $smtp->encryption === 'starttls' ? null : $smtp->encryption]);
            Config::set('mail.from', ['address' => $smtp->from_address, 'name' => $smtp->from_name]);
            Mail::mailer('platform_smtp')->raw('DG ERP SMTP test completed successfully.', function ($message) { $message->to(auth()->user()->email)->subject('DG ERP SMTP Test'); });
            $smtp->forceFill(['last_tested_at' => now()])->save();
        } catch (\Throwable) {
            return back()->with('error', 'SMTP test failed. Review the saved configuration without exposing credentials.');
        }

        return back()->with('success', 'SMTP test email sent to your Super Admin email address.');
    }

    public function updatePaymentGateway(UpdatePlatformPaymentGatewayRequest $request)
    {
        $this->authorizeSuperAdmin();
        $this->settings->updateGateway($request->validated());
        return back()->with('success', 'Payment gateway configuration stored securely.');
    }

    private function authorizeSuperAdmin(): void
    {
        abort_unless((int) auth()->user()?->role_id === Role::SUPER_ADMIN_ID, 403);
    }
}
