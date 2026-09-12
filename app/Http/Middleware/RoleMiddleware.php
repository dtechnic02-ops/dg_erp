<?php

namespace App\Http\Middleware;

use App\Models\Role;
use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
 public function handle($request, Closure $next, ...$roles)
{

if(!auth()->check()){

return redirect('/login');

}

$user = auth()->user();
$platform = $user->company_id === null && in_array((int) $user->role_id, [Role::SUPER_ADMIN_ID, Role::COUNTRY_ADMIN_ID, Role::SUPER_STAFF_ID], true);
$company = $user->company_id !== null && in_array((int) $user->role_id, [Role::COMPANY_ADMIN_ID, Role::COMPANY_STAFF_ID, Role::AUDITOR_ID], true);
abort_unless($platform || $company, 403);

return $next($request);

}
}
