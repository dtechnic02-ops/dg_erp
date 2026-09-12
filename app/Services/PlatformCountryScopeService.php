<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyRegistration;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class PlatformCountryScopeService
{
    public function isGlobal(User $user): bool
    {
        return $user->company_id === null && (int) $user->role_id === Role::SUPER_ADMIN_ID;
    }

    public function permitsCountry(User $user, ?int $countryId): bool
    {
        return $this->isGlobal($user)
            || ($countryId !== null && $user->country_id !== null && (int) $user->country_id === $countryId);
    }

    public function permitsCompany(User $user, Company $company): bool
    {
        return $this->permitsCountry($user, $company->country_id);
    }

    public function permitsRegistration(User $user, CompanyRegistration $registration): bool
    {
        return $this->permitsCountry($user, $registration->country_id);
    }

    public function scopeCompanies(Builder $query, User $user): Builder
    {
        return $this->scopeDirectCountry($query, $user, 'companies.country_id');
    }

    public function scopeRegistrations(Builder $query, User $user): Builder
    {
        return $this->scopeDirectCountry($query, $user, 'company_registrations.country_id');
    }

    public function scopeSubscriptionPayments(Builder $query, User $user): Builder
    {
        if ($this->isGlobal($user)) {
            return $query;
        }

        if ($user->country_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('company', fn (Builder $company): Builder =>
            $company->where('companies.country_id', $user->country_id)
        );
    }

    public function scopeUsers(Builder $query, User $user): Builder
    {
        if ($this->isGlobal($user)) {
            return $query;
        }

        if ($user->country_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $scoped) use ($user): void {
            $scoped->where(function (Builder $platformUser) use ($user): void {
                $platformUser->whereNull('company_id')->where('country_id', $user->country_id);
            })->orWhereHas('company', fn (Builder $company): Builder =>
                $company->where('companies.country_id', $user->country_id)
            );
        });
    }

    private function scopeDirectCountry(Builder $query, User $user, string $column): Builder
    {
        if ($this->isGlobal($user)) {
            return $query;
        }

        return $user->country_id === null
            ? $query->whereRaw('1 = 0')
            : $query->where($column, $user->country_id);
    }
}
