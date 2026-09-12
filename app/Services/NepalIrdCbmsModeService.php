<?php

namespace App\Services;

use App\Models\Company;

class NepalIrdCbmsModeService
{
    public function isActiveForCompany(Company $company): bool
    {
        return $company->country_id !== null
            && $company->countryMaster()->where('iso_code', 'NP')->exists()
            && $company->irdCbmsSetting()->where('is_enabled', true)->exists();
    }
}
