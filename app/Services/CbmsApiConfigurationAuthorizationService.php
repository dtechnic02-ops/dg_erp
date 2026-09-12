<?php
namespace App\Services;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
class CbmsApiConfigurationAuthorizationService {
 public function authorize(User $user, Company $company): void {
  abort_unless($company->countryMaster()->where('iso_code','NP')->exists(),404);
  $global=$user->company_id===null && (int)$user->role_id===Role::SUPER_ADMIN_ID;
  $owner=(int)$user->role_id===Role::COMPANY_ADMIN_ID && (int)$user->company_id===(int)$company->id;
  abort_unless($global || $owner,403);
 }
}
