<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePlatformBrandingRequest;
use App\Http\Requests\UpdatePlatformGeneralSettingRequest;
use App\Http\Requests\UpdatePlatformLoginSettingRequest;
use App\Http\Requests\UpdatePlatformPaymentGatewayRequest;
use App\Http\Requests\UpdatePlatformSmtpRequest;
use App\Http\Requests\UpdatePlatformSocialLinksRequest;
use App\Models\Role;
use App\Services\PlatformMailService;
use App\Services\PlatformSettingService;

class PlatformSettingController extends Controller
{
    public function __construct(
        private PlatformSettingService $settings,
        private PlatformMailService $platformMail,
    ) {}

    public function index()
    {
        $this->authorizeSuperAdmin();
        $setting = $this->settings->settings()->load(['loginSetting', 'socialLinks', 'smtpSetting', 'paymentGateways']);
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

    public function updateLoginPage(UpdatePlatformLoginSettingRequest $request)
    {
        $this->authorizeSuperAdmin();
        $this->settings->updateLoginPage($request->validated(), $request->user()->id);

        return back()->with('success', 'Public login page settings updated.');
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
        try {
            $this->platformMail->sendRaw(
                auth()->user()->email,
                'DG ERP SMTP Test',
                'DG ERP SMTP test completed successfully.',
            );
            $smtp = $this->settings->settings()->smtpSetting;
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
        abort_unless(app(\App\Services\PlatformAuthorizationService::class)->can(auth()->user(), 'platform_settings_manage'), 403);
    }
}
