<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StaffUserService
{
    public function staffRoleId(): int
    {
        return (int) Role::query()
            ->where('name', Role::STAFF)
            ->value('id');
    }

    public function staffQuery(int $companyId)
    {
        return User::query()
            ->where('company_id', $companyId)
            ->where('role_id', $this->staffRoleId());
    }

    public function paginateStaff(Request $request, int $companyId): LengthAwarePaginator
    {
        $query = $this->staffQuery($companyId);

        if ($search = trim((string) $request->query('search'))) {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($status = $request->query('status')) {
            $query->where('account_status', $status);
        }

        if ($jobRole = $request->query('job_role')) {
            $query->where('job_role', $jobRole);
        }

        $perPage = (int) $request->query('per_page', 10);

        if (! in_array($perPage, [10, 20, 50, 100], true)) {
            $perPage = 10;
        }

        return $query->latest()->paginate($perPage)->withQueryString();
    }

    public function findStaffForCompany(int $staffId, int $companyId): User
    {
        return $this->staffQuery($companyId)
            ->where('id', $staffId)
            ->firstOrFail();
    }

    public function assignStaffRole(User $user): void
    {
        $user->role_id = $this->staffRoleId();
    }

    public function generateTemporaryPassword(): string
    {
        return Str::password(12);
    }
}
