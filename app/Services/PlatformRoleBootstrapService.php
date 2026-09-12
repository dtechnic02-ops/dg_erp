<?php

namespace App\Services;

use App\Models\Role;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PlatformRoleBootstrapService
{
    private const REQUIRED_ROLES = [
        Role::SUPER_STAFF_ID => 'super_staff',
        Role::COUNTRY_ADMIN_ID => 'country_admin',
        Role::AUDITOR_ID => 'auditor',
    ];

    public function seedRequiredRoles(): void
    {
        DB::transaction(function (): void {
            foreach (self::REQUIRED_ROLES as $id => $name) {
                $roleAtId = DB::table('roles')->where('id', $id)->first();
                $rolesWithName = DB::table('roles')->where('name', $name)->get();

                if (($roleAtId && $roleAtId->name !== $name)
                    || $rolesWithName->contains(fn (object $role): bool => (int) $role->id !== $id)) {
                    throw new RuntimeException("Platform role [{$name}] conflicts with reserved role ID [{$id}].");
                }
            }

            foreach (self::REQUIRED_ROLES as $id => $name) {
                if (! DB::table('roles')->where('id', $id)->exists()) {
                    DB::table('roles')->insert([
                        'id' => $id,
                        'name' => $name,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });
    }
}
