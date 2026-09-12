<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyTaxSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CompanyTaxIdentityUpdateService
{
    public function __construct(private TaxIdentifierService $identifiers, private NepalIrdCbmsModeService $cbmsMode) {}

    public function update(Company $company, array $input, int $actorId): void
    {
        $company->loadMissing('countryMaster');
        if ($company->countryMaster?->iso_code !== 'NP') {
            throw ValidationException::withMessages(['pan_number' => 'Nepal tax identity settings are available only when the company Country Master ISO code is NP.']);
        }
        $data = Validator::make([
            'pan_number' => $this->identifiers->normalize($input['pan_number'] ?? null),
            'is_vat_registered' => $input['is_vat_registered'] ?? null,
        ], [
            'pan_number' => ['required', $this->identifiers->validationRule()],
            'is_vat_registered' => ['required', 'boolean'],
        ])->validate();

        $vatRegistered = (bool) $data['is_vat_registered'];
        $panNumber = $data['pan_number'];
        $vatNumber = $vatRegistered ? $panNumber : null;

        if ($this->cbmsMode->isActiveForCompany($company) && ! $vatRegistered) {
            throw ValidationException::withMessages(['is_vat_registered' => 'VAT registration and a valid VAT number are required while Nepal IRD/CBMS mode is ON.']);
        }

        DB::transaction(function () use ($company, $panNumber, $vatNumber, $vatRegistered, $actorId): void {
            $company->update(['pan_number' => $panNumber, 'vat_number' => $vatNumber]);
            CompanyTaxSetting::updateOrCreate(['company_id' => $company->id], ['is_vat_registered' => $vatRegistered, 'updated_by' => $actorId]);
        });
    }
}
