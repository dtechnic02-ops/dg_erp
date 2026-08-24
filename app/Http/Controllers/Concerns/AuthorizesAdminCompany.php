<?php

namespace App\Http\Controllers\Concerns;

trait AuthorizesAdminCompany
{
    protected function authorizeViewCompany(): void
    {
        abort_unless(app(\App\Services\PlatformAuthorizationService::class)->can(auth()->user(), 'platform_companies_view'), 403, 'You do not have permission to view companies.');
    }

    protected function authorizeEditCompany(): void
    {
        abort_unless(app(\App\Services\PlatformAuthorizationService::class)->can(auth()->user(), 'platform_companies_edit'), 403, 'You do not have permission to edit companies.');
    }

    protected function authorizeApproveCompany(): void
    {
        abort_unless(app(\App\Services\PlatformAuthorizationService::class)->can(auth()->user(), 'platform_registrations_approve'), 403, 'You do not have permission to approve companies.');
    }

    protected function authorizeBlockCompany(): void
    {
        abort_unless(app(\App\Services\PlatformAuthorizationService::class)->can(auth()->user(), 'platform_companies_block'), 403, 'You do not have permission to block companies.');
    }

    protected function authorizeUnblockCompany(): void
    {
        abort_unless(app(\App\Services\PlatformAuthorizationService::class)->can(auth()->user(), 'platform_companies_unblock'), 403, 'You do not have permission to unblock companies.');
    }

    protected function authorizeDeleteCompany(): void
    {
        abort_unless(app(\App\Services\PlatformAuthorizationService::class)->can(auth()->user(), 'platform_companies_delete'), 403, 'You do not have permission to delete companies.');
    }

    protected function authorizeResetCompanyPassword(): void
    {
        abort_unless(app(\App\Services\PlatformAuthorizationService::class)->can(auth()->user(), 'platform_companies_reset_password'), 403, 'You do not have permission to reset company passwords.');
    }
}
