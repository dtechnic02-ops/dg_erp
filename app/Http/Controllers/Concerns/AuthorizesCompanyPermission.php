<?php

namespace App\Http\Controllers\Concerns;

trait AuthorizesCompanyPermission
{
    protected function authorizeCompanyPermission(string $permission): void
    {
        abort_unless(
            auth()->user()?->hasPermission($permission, auth()->user()?->company_id),
            403,
            'You do not have permission to perform this action.'
        );
    }
}
