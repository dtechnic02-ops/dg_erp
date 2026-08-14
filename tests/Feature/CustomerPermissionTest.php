<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckSubscription;
use App\Http\Middleware\EnsureCompanyUser;
use App\Http\Middleware\UpdateLastSeen;
use App\Models\Customer;
use App\Models\CustomerTransaction;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerPermissionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'customer_transactions', 'customers', 'user_permissions', 'permissions', 'users', 'roles',
            'companies', 'company_subscriptions', 'financial_years',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('companies', fn (Blueprint $t) => [$t->id(), $t->string('company_name'), $t->string('status')->default('active'), $t->timestamps()]);
        Schema::create('roles', fn (Blueprint $t) => [$t->id(), $t->string('name'), $t->timestamps()]);
        Schema::create('users', fn (Blueprint $t) => [$t->id(), $t->string('name'), $t->string('email'), $t->string('password'), $t->unsignedBigInteger('company_id'), $t->unsignedBigInteger('role_id'), $t->string('account_status')->default('active'), $t->rememberToken(), $t->timestamps()]);
        Schema::create('permissions', fn (Blueprint $t) => [$t->id(), $t->string('name'), $t->string('scope')->default('company'), $t->timestamps()]);
        Schema::create('user_permissions', fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('user_id'), $t->unsignedBigInteger('permission_id'), $t->boolean('is_allowed'), $t->timestamps()]);
        Schema::create('company_subscriptions', fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('company_id'), $t->string('status'), $t->boolean('is_all_modules_enabled')->default(true), $t->timestamps()]);
        Schema::create('financial_years', fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('company_id'), $t->string('name'), $t->date('start_date'), $t->date('end_date'), $t->boolean('is_active'), $t->timestamps()]);
        Schema::create('customers', fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('company_id'), $t->string('name'), $t->decimal('opening_balance', 10, 2)->default(0), $t->decimal('current_balance', 10, 2)->default(0), $t->string('status')->default('active'), $t->timestamps()]);
        Schema::create('customer_transactions', fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('company_id'), $t->unsignedBigInteger('financial_year_id')->nullable(), $t->unsignedBigInteger('customer_id'), $t->date('transaction_date'), $t->string('voucher_no'), $t->string('reference_type'), $t->unsignedBigInteger('reference_id'), $t->decimal('debit', 20, 4)->default(0), $t->decimal('credit', 20, 4)->default(0), $t->decimal('balance', 20, 4)->default(0), $t->integer('status')->default(1), $t->timestamps()]);

        DB::table('companies')->insert([['id' => 1, 'company_name' => 'One'], ['id' => 2, 'company_name' => 'Two']]);
        DB::table('roles')->insert([['id' => 2, 'name' => 'company_admin'], ['id' => 3, 'name' => 'staff']]);
        DB::table('users')->insert([
            ['id' => 1, 'name' => 'Admin', 'email' => 'admin@test', 'password' => Hash::make('x'), 'company_id' => 1, 'role_id' => 2],
            ['id' => 2, 'name' => 'Staff', 'email' => 'staff@test', 'password' => Hash::make('x'), 'company_id' => 1, 'role_id' => 3],
            ['id' => 3, 'name' => 'Authorized Staff', 'email' => 'auth-staff@test', 'password' => Hash::make('x'), 'company_id' => 1, 'role_id' => 3],
        ]);
        DB::table('company_subscriptions')->insert(['company_id' => 1, 'status' => 'active']);
        DB::table('financial_years')->insert(['id' => 1, 'company_id' => 1, 'name' => 'FY26', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => 1]);

        foreach ([
            'module_customer', 'view_customer', 'create_customer', 'edit_customer', 'delete_customer', 'print_customer',
        ] as $permission) {
            Permission::create(['name' => $permission, 'scope' => Permission::SCOPE_COMPANY]);
        }

        foreach (['module_customer', 'view_customer', 'create_customer', 'edit_customer', 'delete_customer', 'print_customer'] as $permission) {
            $this->assignPermission(3, $permission);
        }

        $this->withoutMiddleware([EnsureCompanyUser::class, CheckSubscription::class, UpdateLastSeen::class, VerifyCsrfToken::class]);
    }

    public function test_unauthorized_staff_is_rejected_by_every_customer_route(): void
    {
        $customer = $this->customer(1);
        $staff = User::find(2);
        $payload = $this->payload();

        foreach ($this->routes($customer, $payload) as [$method, $name, $params, $data]) {
            $this->actingAs($staff)->{$method}(route($name, $params), $data)->assertForbidden();
        }
    }

    public function test_company_admin_and_authorized_staff_can_access_customer_routes(): void
    {
        $customer = $this->customer(1);
        $payload = $this->payload();

        foreach ([User::find(1), User::find(3)] as $user) {
            foreach ($this->routes($customer, $payload) as [$method, $name, $params, $data]) {
                $this->assertNotSame(
                    403,
                    $this->actingAs($user)->{$method}(route($name, $params), $data)->getStatusCode(),
                    $name . ' for user ' . $user->id
                );
            }
        }
    }

    public function test_delete_is_blocked_when_customer_has_financial_history(): void
    {
        $customer = $this->customer(1);
        CustomerTransaction::create([
            'company_id' => 1,
            'financial_year_id' => 1,
            'customer_id' => $customer->id,
            'transaction_date' => '2026-06-01',
            'voucher_no' => 'TX-1',
            'reference_type' => 'sales_invoice',
            'reference_id' => 1,
            'debit' => 100,
            'credit' => 0,
            'balance' => 100,
            'status' => 1,
        ]);

        $this->actingAs(User::find(1))
            ->post(route('company.customers.delete', $customer->id))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(1, Customer::whereKey($customer->id)->count());
    }

    public function test_cross_company_customer_access_remains_blocked_for_admin(): void
    {
        $foreign = $this->customer(2);
        $admin = User::find(1);

        $this->actingAs($admin)->get(route('company.customers.show', $foreign->id))->assertNotFound();
        $this->actingAs($admin)->post(route('company.customers.update', $foreign->id), $this->payload())->assertNotFound();
        $this->actingAs($admin)->post(route('company.customers.delete', $foreign->id))->assertNotFound();
    }

    private function routes(Customer $customer, array $payload): array
    {
        return [
            ['get', 'company.customers.index', [], []],
            ['get', 'company.customers.show', [$customer->id], []],
            ['post', 'company.customers.store', [], $payload],
            ['post', 'company.customers.update', [$customer->id], $payload],
            ['post', 'company.customers.delete', [$customer->id], []],
            ['get', 'company.customers.print', [], []],
            ['get', 'company.customers.printProfile', [$customer->id], []],
        ];
    }

    private function customer(int $companyId): Customer
    {
        return Customer::create([
            'company_id' => $companyId,
            'name' => 'Customer ' . $companyId,
            'opening_balance' => 0,
            'current_balance' => 0,
            'status' => 'active',
        ]);
    }

    private function payload(): array
    {
        return [
            'name' => 'Route Customer',
            'mobile' => '9800000003',
            'credit_days' => 0,
            'status' => 'active',
        ];
    }

    private function assignPermission(int $userId, string $permission): void
    {
        DB::table('user_permissions')->insert([
            'user_id' => $userId,
            'permission_id' => Permission::where('name', $permission)->value('id'),
            'is_allowed' => true,
        ]);
    }
}
