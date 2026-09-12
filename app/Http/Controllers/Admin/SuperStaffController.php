<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Country;
use App\Services\PlatformAuthorizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Services\UserSessionRevocationService;

class SuperStaffController extends Controller
{
    public function index()
    {
        $this->authorizeSuperAdmin();

        $superStaff = User::query()
            ->whereIn('role_id', [Role::COUNTRY_ADMIN_ID, Role::SUPER_STAFF_ID])
            ->whereNull('company_id')
            ->latest()
            ->paginate(15);

        return view('admin.super_staff.index', compact('superStaff'));
    }

    public function create()
    {
        $this->authorizeSuperAdmin();

        return view('admin.super_staff.create', ['countries' => Country::where('is_active', true)->orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $this->authorizeSuperAdmin();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role_id' => ['required', Rule::in([Role::COUNTRY_ADMIN_ID, Role::SUPER_STAFF_ID])],
            'country_id' => ['required', Rule::exists('countries', 'id')->where(fn ($q) => $q->where('is_active', true))],
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role_id' => $data['role_id'],
            'country_id' => $data['country_id'],
            'company_id' => null,
            'job_role' => null,
            'account_status' => 'active',
        ]);

        return redirect()
            ->route('admin.super-staff.index')
            ->with('success', 'Super Staff created successfully.');
    }

    public function show(User $user)
    {
        $this->authorizeSuperAdmin();
        $this->assertPlatformStaff($user);

        return view('admin.super_staff.show', compact('user'));
    }

    public function edit(User $user)
    {
        $this->authorizeSuperAdmin();
        $this->assertPlatformStaff($user);

        return view('admin.super_staff.edit', ['user' => $user, 'countries' => Country::where('is_active', true)->orderBy('name')->get()]);
    }

    public function update(Request $request, User $user)
    {
        $this->authorizeSuperAdmin();
        $this->assertPlatformStaff($user);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'country_id' => ['required', Rule::exists('countries', 'id')->where(fn ($q) => $q->where('is_active', true))],
        ]);

        $user->update($data);

        return redirect()
            ->route('admin.super-staff.show', $user)
            ->with('success', 'Super Staff updated successfully.');
    }

    public function block(User $user)
    {
        $this->authorizeSuperAdmin();
        $this->assertPlatformStaff($user);

        $user->update(['account_status' => 'blocked']);
        app(UserSessionRevocationService::class)->revoke($user);

        return back()->with('success', 'Super Staff blocked successfully.');
    }

    public function unblock(User $user)
    {
        $this->authorizeSuperAdmin();
        $this->assertPlatformStaff($user);

        $user->update(['account_status' => 'active']);

        return back()->with('success', 'Super Staff unblocked successfully.');
    }

    public function editPermissions(User $user)
    {
        $this->authorizeSuperAdmin();
        $this->assertSuperStaff($user);

        $permissionGroups = Permission::platform()
            ->whereIn('name', PlatformAuthorizationService::SUPER_STAFF_ASSIGNABLE_PERMISSIONS)
            ->orderBy('name')
            ->get()
            ->groupBy(function (Permission $permission): string {
                $parts = explode('_', $permission->name);
                $module = count($parts) > 1
                    ? implode(' ', array_slice($parts, 1))
                    : $permission->name;

                return ucwords(str_replace('_', ' ', $module));
            });

        $assignedPermissionIds = $user->permissions()
            ->where('permissions.scope', Permission::SCOPE_PLATFORM)
            ->wherePivot('is_allowed', true)
            ->pluck('permissions.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return view(
            'admin.super_staff.permissions',
            compact('user', 'permissionGroups', 'assignedPermissionIds')
        );
    }

    public function updatePermissions(Request $request, User $user)
    {
        $this->authorizeSuperAdmin();
        $this->assertSuperStaff($user);

        $data = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'distinct', 'exists:permissions,id'],
        ]);

        $selectedPermissionIds = array_map('intval', $data['permissions'] ?? []);
        $platformPermissionIds = Permission::platform()
            ->whereIn('name', PlatformAuthorizationService::SUPER_STAFF_ASSIGNABLE_PERMISSIONS)
            ->whereIn('id', $selectedPermissionIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (count($platformPermissionIds) !== count($selectedPermissionIds)) {
            throw ValidationException::withMessages([
                'permissions' => 'Only platform-scoped permissions may be assigned to Super Staff.',
            ]);
        }

        DB::transaction(function () use ($user, $platformPermissionIds): void {
            $existingAllowedPlatformIds = $user->permissions()
                ->where('permissions.scope', Permission::SCOPE_PLATFORM)
                ->whereIn('permissions.name', PlatformAuthorizationService::SUPER_STAFF_ASSIGNABLE_PERMISSIONS)
                ->wherePivot('is_allowed', true)
                ->pluck('permissions.id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $removedPermissionIds = array_diff($existingAllowedPlatformIds, $platformPermissionIds);

            if ($removedPermissionIds !== []) {
                $user->permissions()->detach($removedPermissionIds);
            }

            if ($platformPermissionIds !== []) {
                $user->permissions()->syncWithoutDetaching(
                    collect($platformPermissionIds)
                        ->mapWithKeys(fn (int $id) => [$id => ['is_allowed' => true]])
                        ->all()
                );
            }
        });

        return redirect()
            ->route('admin.super-staff.permissions.edit', $user)
            ->with('success', 'Platform permissions updated successfully.');
    }

    private function authorizeSuperAdmin(): void
    {
        abort_unless(
            app(\App\Services\PlatformAuthorizationService::class)->can(auth()->user(), 'platform_super_staff_manage'),
            403
        );
    }

    private function assertSuperStaff(User $user): void
    {
        abort_unless(
            (int) $user->role_id === Role::SUPER_STAFF_ID && $user->company_id === null,
            404
        );
    }

    private function assertPlatformStaff(User $user): void
    {
        abort_unless(in_array((int) $user->role_id, [Role::COUNTRY_ADMIN_ID, Role::SUPER_STAFF_ID], true) && $user->company_id === null, 404);
    }
}
