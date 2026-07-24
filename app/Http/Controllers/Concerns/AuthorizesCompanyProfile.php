<?php

namespace App\Http\Controllers\Concerns;

trait AuthorizesCompanyProfile
{
    protected function authorizeViewCompanyProfile(): void
    {
        abort_unless(auth()->user()?->hasPermission('view_company_profile'), 403, 'You do not have permission to view the company profile.');
    }

    protected function authorizeEditCompanyProfile(): void
    {
        abort_unless(auth()->user()?->hasPermission('edit_company_profile'), 403, 'You do not have permission to edit the company profile.');
    }
}
