<?php

namespace App\Services\Cbms;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;

class CbmsTransmissionAuthorizationService
{
    public function authorizeView(User $user, Company $company): void
    {
        $sameCompany = (int) $user->company_id === (int) $company->id;
        abort_unless($sameCompany && in_array((int) $user->role_id, [Role::COMPANY_ADMIN_ID, Role::AUDITOR_ID], true), 403);
    }

    public function authorizeOperate(User $user, Company $company): void
    {
        abort_unless((int) $user->company_id === (int) $company->id
            && (int) $user->role_id === Role::COMPANY_ADMIN_ID, 403);
    }
}
