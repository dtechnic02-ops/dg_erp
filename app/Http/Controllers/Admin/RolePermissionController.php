<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Permission;
use App\Models\RolePermission;

class RolePermissionController extends Controller
{
    public function index()
    {
        $permissions = Permission::all();
        return view('admin.role_permissions', compact('permissions'));
    }

    public function update(Request $request)
    {
        $role_id = $request->role_id;

        // पुरानो हटाउने
        RolePermission::where('role_id', $role_id)->delete();

        // नयाँ add
        if ($request->permissions) {
            foreach ($request->permissions as $perm) {
                RolePermission::create([
                    'role_id' => $role_id,
                    'permission_id' => $perm
                ]);
            }
        }

        return back()->with('success', 'Permissions Updated');
    }
}