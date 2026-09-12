<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Concerns\AuthorizesCompanyProfile;
use App\Http\Controllers\Controller;
use App\Models\CompanyTaxSetting;
use App\Services\CompanyTaxIdentityService;
use App\Services\CompanyTaxIdentityUpdateService;
use App\Services\NepalIrdCbmsModeService;
use App\Services\TaxIdentifierService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class IrdCbmsSettingController extends Controller
{
    use AuthorizesCompanyProfile;

    public function edit(NepalIrdCbmsModeService $mode, CompanyTaxIdentityService $taxIdentity): View
    {
        $this->authorizeViewCompanyProfile();
        $company = auth()->user()->company->loadMissing('countryMaster');
        abort_unless($company->countryMaster?->iso_code === 'NP', 404);
        $taxSetting = CompanyTaxSetting::firstOrNew(['company_id' => $company->id]);

        return view('company.settings.ird-cbms', [
            'company' => $company,
            'isNepalCompany' => $company->countryMaster?->iso_code === 'NP',
            'isModeActive' => $mode->isActiveForCompany($company),
            'taxSetting' => $taxSetting,
            'taxIdentity' => $taxIdentity->resolve($company),
        ]);
    }

    public function update(): RedirectResponse
    {
        abort(403, 'Nepal IRD/CBMS status can only be changed by platform administration.');
    }

    public function updateTaxIdentity(Request $request, TaxIdentifierService $identifiers, NepalIrdCbmsModeService $cbmsMode): RedirectResponse
    {
        $this->authorizeEditCompanyProfile();
        app(CompanyTaxIdentityUpdateService::class)->update(auth()->user()->company, $request->all(), auth()->id());

        return back()->with('success', 'Tax identity updated successfully.');
    }
}
