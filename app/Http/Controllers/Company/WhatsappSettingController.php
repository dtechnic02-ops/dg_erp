<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Concerns\AuthorizesCompanyProfile;
use App\Http\Controllers\Controller;
use App\Models\CompanyWhatsappSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WhatsappSettingController extends Controller
{
    use AuthorizesCompanyProfile;

    public function edit(): View
    {
        $this->authorizeViewCompanyProfile();
        $company = auth()->user()->company;
        $setting = CompanyWhatsappSetting::firstOrNew(['company_id' => $company->id]);

        return view('company.settings.whatsapp', compact('setting'));
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorizeEditCompanyProfile();
        $companyId = (int) auth()->user()->company_id;
        $validated = $request->validate(['is_enabled' => ['required', 'boolean']]);

        CompanyWhatsappSetting::updateOrCreate(
            ['company_id' => $companyId],
            ['is_enabled' => (bool) $validated['is_enabled']]
        );

        return back()->with('success', 'WhatsApp Share setting updated successfully.');
    }
}
