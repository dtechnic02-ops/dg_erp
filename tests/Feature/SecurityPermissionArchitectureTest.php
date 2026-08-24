<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\EnsurePermission;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\CompanyAuthorizationService;
use App\Services\PlatformAuthorizationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SecurityPermissionArchitectureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        foreach (['user_permissions', 'permission_role', 'permissions', 'users', 'roles', 'companies'] as $table) Schema::dropIfExists($table);
        Schema::create('companies', fn (Blueprint $t) => [$t->id(), $t->string('company_name'), $t->string('status')->default('active'), $t->timestamps()]);
        Schema::create('roles', fn (Blueprint $t) => [$t->id(), $t->string('name'), $t->timestamps()]);
        Schema::create('users', fn (Blueprint $t) => [$t->id(), $t->string('name'), $t->string('email'), $t->string('password'), $t->unsignedBigInteger('role_id'), $t->unsignedBigInteger('company_id')->nullable(), $t->string('job_role')->nullable(), $t->rememberToken(), $t->timestamps()]);
        Schema::create('permissions', fn (Blueprint $t) => [$t->id(), $t->string('name'), $t->string('scope'), $t->timestamps()]);
        Schema::create('user_permissions', fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('user_id'), $t->unsignedBigInteger('permission_id'), $t->boolean('is_allowed'), $t->timestamps(), $t->unique(['user_id', 'permission_id'])]);
        Schema::create('permission_role', fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('role_id'), $t->unsignedBigInteger('permission_id'), $t->timestamps()]);
        DB::table('companies')->insert([['id'=>1,'company_name'=>'One','status'=>'active'],['id'=>2,'company_name'=>'Two','status'=>'active']]);
        DB::table('roles')->insert([['id'=>1,'name'=>'super_admin'],['id'=>2,'name'=>'company_admin'],['id'=>3,'name'=>'staff'],['id'=>4,'name'=>'super_staff']]);
        foreach ([
            ['module_income','company'], ['create_income','company'], ['edit_income','company'], ['cancel_income','company'],
            ['module_sales','company'], ['create_sales','company'],
            ['module_journal','company'], ['journal.post','company'], ['journal.reverse','company'],
            ['module_loan','company'], ['create_loan_payment','company'], ['cancel_loan_payment','company'],
            ['module_users','company'], ['manage_users','company'],
            ['module_company_profile','company'], ['edit_company_profile','company'], ['platform_module_companies','platform'], ['platform_companies_block','platform'],
        ] as [$name,$scope]) Permission::create(compact('name','scope'));
    }

    public function test_company_admin_policy_is_full_only_for_valid_own_company_permissions(): void
    {
        $admin = $this->user(2, 1);
        $service = app(CompanyAuthorizationService::class);
        $this->assertTrue($service->can($admin, 'module_income', 1));
        $this->assertTrue($service->can($admin, 'create_income', 1));
        $this->assertTrue($service->can($admin, 'edit_company_profile', 1));
        $this->assertFalse($service->can($admin, 'create_income', 2));
        $this->assertFalse($admin->hasPermission('platform_companies_block'));
        $this->assertSame(0, $admin->permissions()->count());
    }

    public function test_sub_admin_job_role_grants_nothing_and_module_plus_action_are_both_required(): void
    {
        $staff = $this->user(3, 1, 'sub_admin');
        $service = app(CompanyAuthorizationService::class);
        $this->assertFalse($service->can($staff, 'create_income', 1));
        $this->assign($staff, 'module_income');
        $this->assertFalse($service->can($staff, 'create_income', 1));
        $staff->permissions()->detach(); $this->assign($staff, 'create_income');
        $this->assertFalse($service->can($staff, 'create_income', 1));
        $this->assign($staff, 'module_income');
        $this->assertTrue($service->can($staff, 'create_income', 1));
        $this->assign($staff, 'module_users'); $this->assign($staff, 'manage_users');
        $this->assertTrue($service->can($staff, 'manage_users', 1));
        $this->assign($staff, 'edit_company_profile');
        $this->assertFalse($service->can($staff, 'edit_company_profile', 1));
    }

    public function test_company_mutation_permission_preserves_admin_override_staff_assignment_and_company_scope(): void
    {
        $service = app(CompanyAuthorizationService::class);
        $admin = $this->user(Role::COMPANY_ADMIN_ID, 1);
        $staff = $this->user(Role::COMPANY_STAFF_ID, 1, 'sales');

        $this->assertTrue($service->can($admin, 'create_sales', 1));
        $this->assertFalse($service->can($admin, 'create_sales', 2));
        $this->assertFalse($service->can($staff, 'create_sales', 1));

        $this->assign($staff, 'module_sales');
        $this->assertFalse($service->can($staff, 'create_sales', 1));

        $this->assign($staff, 'create_sales');
        $this->assertTrue($service->can($staff, 'create_sales', 1));
        $this->assertFalse($service->can($staff, 'create_sales', 2));
    }

    public function test_role_defaults_and_cross_scope_assignments_do_not_authorize_company_staff(): void
    {
        $staff = $this->user(3, 1);
        foreach (['create_income','edit_income','cancel_income','journal.post','journal.reverse','create_loan_payment','cancel_loan_payment'] as $name) {
            $action = Permission::where('name', $name)->firstOrFail();
            DB::table('permission_role')->insert(['role_id'=>3,'permission_id'=>$action->id]);
            $this->assertFalse($staff->hasPermission($name));
        }
        $this->assign($staff, 'platform_module_companies'); $this->assign($staff, 'platform_companies_block');
        $this->assertFalse($staff->hasPermission('platform_companies_block'));
    }

    public function test_platform_policy_is_platform_only_and_requires_module_plus_action_for_super_staff(): void
    {
        $superAdmin = $this->user(1, null);
        $superStaff = $this->user(4, null);
        $service = app(PlatformAuthorizationService::class);
        $this->assertTrue($service->can($superAdmin, 'platform_companies_block'));
        $this->assertFalse($superAdmin->hasPermission('create_income'));
        $this->assign($superStaff, 'platform_companies_block');
        $this->assertFalse($service->can($superStaff, 'platform_companies_block'));
        $this->assign($superStaff, 'platform_module_companies');
        $this->assertTrue($service->can($superStaff, 'platform_companies_block'));
        $this->assign($superStaff, 'create_income');
        $this->assertFalse($service->can($superStaff, 'create_income'));
    }

    public function test_legacy_check_permission_is_not_the_active_permission_alias_and_role_routes_are_retired(): void
    {
        foreach (app('router')->getRoutes() as $route) {
            $middleware = collect($route->gatherMiddleware());
            $this->assertFalse($middleware->contains(fn ($name) => str_starts_with($name, 'role:')));
            $this->assertFalse($middleware->contains(CheckPermission::class));
        }
    }

    private function user(int $roleId, ?int $companyId, ?string $jobRole = null): User
    {
        return User::create(['name'=>'User','email'=>uniqid().'@test.local','password'=>Hash::make('x'),'role_id'=>$roleId,'company_id'=>$companyId,'job_role'=>$jobRole]);
    }

    private function assign(User $user, string $name): void
    {
        $permission = Permission::where('name', $name)->firstOrFail();
        $user->permissions()->syncWithoutDetaching([$permission->id => ['is_allowed' => true]]);
    }
}
