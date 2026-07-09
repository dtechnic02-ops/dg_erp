<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        // permissions
        $view = Permission::firstOrCreate(['name' => 'view_users']);
        $edit = Permission::firstOrCreate(['name' => 'edit_users']);
        $delete = Permission::firstOrCreate(['name' => 'delete_users']);

        // roles
        $superAdmin = Role::where('name', 'super_admin')->first();
        $admin = Role::where('name', 'company_admin')->first();
        $staff = Role::where('name', 'staff')->first();

        // super admin = all permissions
        if ($superAdmin) {
            $superAdmin->permissions()->sync([$view->id, $edit->id, $delete->id]);
        }

        // company admin
        if ($admin) {
            $admin->permissions()->sync([$view->id, $edit->id, $delete->id]);
        }

        // staff = only view
        if ($staff) {
            $staff->permissions()->sync([$view->id]);
        }
    }
}