<?php

namespace App\Http\Controllers\Concerns;

trait AuthorizesCompanyPermission
{
    protected function authorizeCompanyPermission(string $permission): void
    {
        abort_unless(
            auth()->user()?->hasPermission($permission),
            403,
            'You do not have permission to perform this action.'
        );
    }
}
