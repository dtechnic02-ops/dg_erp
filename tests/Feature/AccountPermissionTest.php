<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckSubscription;
use App\Http\Middleware\EnsureCompanyUser;
use App\Http\Middleware\UpdateLastSeen;
use App\Models\Account;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AccountPermissionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'accounts', 'user_permissions', 'permission_role', 'permissions', 'users', 'roles',
            'companies', 'company_subscriptions', 'financial_years',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('companies', fn (Blueprint $t) => [$t->id(), $t->string('company_name'), $t->string('status')->default('active'), $t->timestamps()]);
        Schema::create('roles', fn (Blueprint $t) => [$t->id(), $t->string('name'), $t->timestamps()]);
        Schema::create('users', fn (Blueprint $t) => [$t->id(), $t->string('name'), $t->string('email'), $t->string('password'), $t->unsignedBigInteger('company_id'), $t->unsignedBigInteger('role_id'), $t->string('account_status')->default('active'), $t->rememberToken(), $t->timestamps()]);
        Schema::create('permissions', fn (Blueprint $t) => [$t->id(), $t->string('name'), $t->string('scope')->default('company'), $t->timestamps()]);
        Schema::create('permission_role', fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('permission_id'), $t->unsignedBigInteger('role_id'), $t->timestamps()]);
        Schema::create('user_permissions', fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('user_id'), $t->unsignedBigInteger('permission_id'), $t->boolean('is_allowed'), $t->timestamps()]);
        Schema::create('company_subscriptions', fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('company_id'), $t->string('status'), $t->boolean('is_all_modules_enabled')->default(true), $t->timestamps()]);
        Schema::create('financial_years', fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('company_id'), $t->string('name'), $t->date('start_date'), $t->date('end_date'), $t->boolean('is_active'), $t->timestamps()]);
        Schema::create('accounts', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('company_id');
            $t->string('account_group')->nullable();
            $t->string('account_type');
            $t->string('bank_name')->default('');
            $t->string('account_name');
            $t->string('branch')->nullable();
            $t->string('account_no')->nullable();
            $t->string('iban')->nullable();
            $t->string('swift_code')->nullable();
            $t->string('currency')->default('AED');
            $t->decimal('opening_balance', 15, 2)->default(0);
            $t->decimal('current_balance', 15, 2)->default(0);
            $t->string('status')->default('active');
            $t->timestamps();
        });

        DB::table('companies')->insert([
            ['id' => 1, 'company_name' => 'One'],
            ['id' => 2, 'company_name' => 'Two'],
        ]);
        DB::table('roles')->insert([
            ['id' => 2, 'name' => 'company_admin'],
            ['id' => 3, 'name' => 'staff'],
        ]);
        DB::table('users')->insert([
            ['id' => 1, 'name' => 'Admin', 'email' => 'admin@test', 'password' => Hash::make('x'), 'company_id' => 1, 'role_id' => 2],
            ['id' => 2, 'name' => 'Staff', 'email' => 'staff@test', 'password' => Hash::make('x'), 'company_id' => 1, 'role_id' => 3],
            ['id' => 3, 'name' => 'Authorized Staff', 'email' => 'auth-staff@test', 'password' => Hash::make('x'), 'company_id' => 1, 'role_id' => 3],
        ]);
        DB::table('company_subscriptions')->insert(['company_id' => 1, 'status' => 'active']);
        DB::table('financial_years')->insert(['id' => 1, 'company_id' => 1, 'name' => 'FY26', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => 1]);

        foreach ([
            'module_accounts', 'view_accounts', 'create_accounts', 'edit_accounts', 'delete_accounts', 'print_accounts',
        ] as $permission) {
            Permission::create(['name' => $permission, 'scope' => Permission::SCOPE_COMPANY]);
        }

        $this->assignPermission(3, 'module_accounts');
        $this->assignPermission(3, 'view_accounts');
        $this->assignPermission(3, 'create_accounts');
        $this->assignPermission(3, 'edit_accounts');
        $this->assignPermission(3, 'delete_accounts');
        $this->assignPermission(3, 'print_accounts');

        $this->withoutMiddleware([EnsureCompanyUser::class, CheckSubscription::class, UpdateLastSeen::class, VerifyCsrfToken::class]);
    }

    public function test_unauthorized_staff_is_rejected_by_every_account_route(): void
    {
        $account = $this->account(1);
        $staff = User::find(2);
        $payload = $this->payload();

        foreach ($this->routes($account, $payload) as [$method, $name, $params, $data]) {
            $this->actingAs($staff)->{$method}(route($name, $params), $data)->assertForbidden();
        }
    }

    public function test_company_admin_can_access_every_account_route(): void
    {
        $account = $this->account(1);
        $admin = User::find(1);
        $payload = $this->payload();

        foreach ($this->routes($account, $payload) as [$method, $name, $params, $data]) {
            $this->assertNotSame(
                403,
                $this->actingAs($admin)->{$method}(route($name, $params), $data)->getStatusCode(),
                $name
            );
        }
    }

    public function test_authorized_staff_can_access_assigned_account_routes(): void
    {
        $account = $this->account(1);
        $staff = User::find(3);
        $payload = $this->payload();

        foreach ($this->routes($account, $payload) as [$method, $name, $params, $data]) {
            $this->assertNotSame(
                403,
                $this->actingAs($staff)->{$method}(route($name, $params), $data)->getStatusCode(),
                $name
            );
        }
    }

    public function test_cross_company_account_access_remains_blocked_for_admin(): void
    {
        $foreign = $this->account(2);
        $admin = User::find(1);

        $this->actingAs($admin)->get(route('company.accounts.show', $foreign->id))->assertNotFound();
        $this->actingAs($admin)->post(route('company.accounts.update', $foreign->id), $this->payload())->assertNotFound();
        $this->actingAs($admin)->post(route('company.accounts.delete', $foreign->id))->assertNotFound();
    }

    private function routes(Account $account, array $payload): array
    {
        return [
            ['get', 'company.accounts.index', [], []],
            ['get', 'company.accounts.show', [$account->id], []],
            ['post', 'company.accounts.store', [], $payload],
            ['post', 'company.accounts.update', [$account->id], $payload],
            ['post', 'company.accounts.delete', [$account->id], []],
            ['get', 'company.accounts.print', [], []],
            ['get', 'company.accounts.printProfile', [$account->id], []],
        ];
    }

    private function account(int $companyId): Account
    {
        return Account::create([
            'company_id' => $companyId,
            'account_group' => 'Asset',
            'account_type' => 'Cash',
            'account_name' => 'Cash ' . $companyId,
            'bank_name' => '',
            'opening_balance' => 0,
            'current_balance' => 0,
            'status' => 'active',
        ]);
    }

    private function payload(): array
    {
        return [
            '_account_form' => 'create',
            'account_group' => 'Asset',
            'account_type' => 'Cash',
            'account_name' => 'Route Cash',
            'bank_name' => '',
        ];
    }

    private function assignPermission(int $userId, string $permission): void
    {
        $permissionId = Permission::where('name', $permission)->value('id');
        DB::table('user_permissions')->insert([
            'user_id' => $userId,
            'permission_id' => $permissionId,
            'is_allowed' => true,
        ]);
    }
}
