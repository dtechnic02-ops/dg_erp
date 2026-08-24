<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckSubscription;
use App\Http\Middleware\EnsureCompanyUser;
use App\Http\Middleware\UpdateLastSeen;
use App\Models\Permission;
use App\Models\User;
use App\Models\Vat;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class VatPermissionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'vats', 'user_permissions', 'permissions', 'users', 'roles',
            'companies', 'company_subscriptions', 'financial_years',
            'sales_invoices', 'sales_returns', 'purchase_invoices', 'purchase_returns',
            'customers', 'suppliers',
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
        Schema::create('customers', fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('company_id'), $t->string('name'), $t->timestamps()]);
        Schema::create('suppliers', fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('company_id'), $t->string('name'), $t->timestamps()]);
        Schema::create('vats', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('company_id');
            $t->string('name');
            $t->decimal('rate', 5, 2);
            $t->boolean('is_default')->default(false);
            $t->boolean('status')->default(true);
            $t->timestamps();
        });
        Schema::create('sales_invoices', fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('company_id'), $t->unsignedBigInteger('financial_year_id')->nullable(), $t->unsignedBigInteger('customer_id')->nullable(), $t->string('invoice_no'), $t->date('sale_date'), $t->decimal('total_vat', 20, 4)->default(0), $t->integer('status')->default(1), $t->timestamps()]);
        Schema::create('sales_returns', fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('company_id'), $t->unsignedBigInteger('financial_year_id')->nullable(), $t->unsignedBigInteger('customer_id')->nullable(), $t->string('return_no'), $t->date('return_date'), $t->decimal('total_vat', 20, 4)->default(0), $t->integer('status')->default(1), $t->timestamps()]);
        Schema::create('purchase_invoices', fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('company_id'), $t->unsignedBigInteger('financial_year_id')->nullable(), $t->unsignedBigInteger('supplier_id')->nullable(), $t->string('invoice_no'), $t->date('purchase_date'), $t->decimal('total_vat', 20, 4)->default(0), $t->integer('status')->default(1), $t->timestamps()]);
        Schema::create('purchase_returns', fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('company_id'), $t->unsignedBigInteger('financial_year_id')->nullable(), $t->unsignedBigInteger('supplier_id')->nullable(), $t->string('return_no'), $t->date('return_date'), $t->decimal('total_vat', 20, 4)->default(0), $t->integer('status')->default(1), $t->timestamps()]);

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
            'module_vat', 'view_vat', 'create_vat', 'edit_vat', 'delete_vat', 'print_vat',
        ] as $permission) {
            Permission::create(['name' => $permission, 'scope' => Permission::SCOPE_COMPANY]);
        }

        foreach (['module_vat', 'view_vat', 'create_vat', 'edit_vat', 'delete_vat', 'print_vat'] as $permission) {
            $this->assignPermission(3, $permission);
        }

        $this->withoutMiddleware([EnsureCompanyUser::class, CheckSubscription::class, UpdateLastSeen::class, VerifyCsrfToken::class]);
    }

    public function test_unauthorized_staff_is_rejected_by_every_vat_route(): void
    {
        $vat = $this->vat(1);
        $staff = User::find(2);
        $payload = $this->payload();

        foreach ($this->routes($vat, $payload) as [$method, $name, $params, $data]) {
            $this->actingAs($staff)->{$method}(route($name, $params), $data)->assertForbidden();
        }
    }

    public function test_company_admin_and_authorized_staff_can_access_vat_routes(): void
    {
        $vat = $this->vat(1);
        $payload = $this->payload();

        foreach ([User::find(1), User::find(3)] as $user) {
            foreach ($this->routes($vat, $payload) as [$method, $name, $params, $data]) {
                $this->assertNotSame(
                    403,
                    $this->actingAs($user)->{$method}(route($name, $params), $data)->getStatusCode(),
                    $name . ' for user ' . $user->id
                );
            }
        }
    }

    public function test_cross_company_vat_access_remains_blocked_for_admin(): void
    {
        $foreign = $this->vat(2);
        $admin = User::find(1);

        $this->actingAs($admin)->post(route('company.vats.update', $foreign->id), $this->payload())->assertNotFound();
        $this->actingAs($admin)->post(route('company.vats.delete', $foreign->id))->assertNotFound();
    }

    private function routes(Vat $vat, array $payload): array
    {
        return [
            ['get', 'company.vats.index', [], []],
            ['post', 'company.vats.store', [], $payload],
            ['post', 'company.vats.update', [$vat->id], $payload],
            ['post', 'company.vats.delete', [$vat->id], []],
            ['get', 'company.vat-report.index', [], []],
            ['get', 'company.vat-report.print', [], []],
        ];
    }

    private function vat(int $companyId): Vat
    {
        return Vat::create([
            'company_id' => $companyId,
            'name' => 'VAT ' . $companyId,
            'rate' => 13,
            'is_default' => false,
            'status' => true,
        ]);
    }

    private function payload(): array
    {
        return [
            'name' => 'VAT 13%',
            'rate' => '13',
            'is_default' => 0,
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
