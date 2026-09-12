<?php

namespace App\Services;

use App\Models\Company;

class CompanyTaxIdentityService
{
    public function __construct(
        private readonly TaxIdentifierService $identifiers,
        private readonly NepalIrdCbmsModeService $cbmsMode,
    ) {
    }

    public function resolve(Company $company): array
    {
        $isNepal = $company->countryMaster()->where('iso_code', 'NP')->exists();
        $pan = $this->identifiers->normalize($company->pan_number);
        $vatNumber = $this->identifiers->normalize($company->vat_number);
        $vatRegistered = $isNepal
            && $company->taxSetting()->where('is_vat_registered', true)->exists();
        $cbmsActive = $this->cbmsMode->isActiveForCompany($company);

        return [
            'is_nepal_company' => $isNepal,
            'seller_pan' => $pan,
            'seller_vat_number' => $vatNumber,
            'is_vat_registered' => $vatRegistered,
            'is_nepal_ird_cbms_active' => $cbmsActive,
            'effective_tax_mode' => ! $isNepal
                ? 'non_nepal'
                : ($cbmsActive ? 'nepal_cbms' : ($vatRegistered ? 'nepal_vat' : 'nepal_pan')),
        ];
    }

    public function hasValidNepalCbmsIdentity(Company $company): bool
    {
        $identity = $this->resolve($company);

        return $identity['is_nepal_company']
            && $identity['is_vat_registered']
            && $this->identifiers->isValid($identity['seller_pan'])
            && $this->identifiers->isValid($identity['seller_vat_number']);
    }
}
