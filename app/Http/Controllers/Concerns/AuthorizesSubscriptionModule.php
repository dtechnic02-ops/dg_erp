<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Role;
use App\Services\SubscriptionService;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

trait AuthorizesSubscriptionModule
{
    abstract protected static function subscriptionModuleCode(): string;

    public static function subscriptionModuleMiddleware(): array
    {
        if (! is_subclass_of(static::class, HasMiddleware::class)) {
            return [];
        }

        return [
            new Middleware('subscription.module:' . static::subscriptionModuleCode()),
        ];
    }

    protected function authorizeSubscriptionModule(?string $module = null): void
    {
        $user = auth()->user();

        if (! $user || (int) $user->role_id !== Role::COMPANY_ADMIN_ID) {
            return;
        }

        $company = $user->company;

        if (! $company) {
            abort(403, 'Company not found.');
        }

        app(SubscriptionService::class)->assertModuleAccess(
            $company,
            $module ?? static::subscriptionModuleCode()
        );
    }
}
