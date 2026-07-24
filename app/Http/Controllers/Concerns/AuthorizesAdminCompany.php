<?php

namespace App\Http\Controllers\Concerns;

trait AuthorizesAdminCompany
{
    protected function authorizeViewCompany(): void
    {
        abort_unless(auth()->user()?->hasPermission('view_company'), 403, 'You do not have permission to view companies.');
    }

    protected function authorizeEditCompany(): void
    {
        abort_unless(auth()->user()?->hasPermission('edit_company'), 403, 'You do not have permission to edit companies.');
    }

    protected function authorizeApproveCompany(): void
    {
        abort_unless(auth()->user()?->hasPermission('approve_company'), 403, 'You do not have permission to approve companies.');
    }

    protected function authorizeBlockCompany(): void
    {
        abort_unless(auth()->user()?->hasPermission('block_company'), 403, 'You do not have permission to block companies.');
    }

    protected function authorizeUnblockCompany(): void
    {
        abort_unless(auth()->user()?->hasPermission('unblock_company'), 403, 'You do not have permission to unblock companies.');
    }

    protected function authorizeDeleteCompany(): void
    {
        abort_unless(auth()->user()?->hasPermission('delete_company'), 403, 'You do not have permission to delete companies.');
    }

    protected function authorizeResetCompanyPassword(): void
    {
        abort_unless(auth()->user()?->hasPermission('reset_company_password'), 403, 'You do not have permission to reset company passwords.');
    }
}
