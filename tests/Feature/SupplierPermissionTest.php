<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckSubscription;
use App\Http\Middleware\EnsureCompanyUser;
use App\Http\Middleware\UpdateLastSeen;
use App\Models\Permission;
use App\Models\Supplier;
use App\Models\SupplierTransaction;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SupplierPermissionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'supplier_transactions', 'purchase_returns', 'purchase_invoices', 'suppliers', 'user_permissions', 'permissions', 'users', 'roles',
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
        Schema::create('suppliers', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('company_id');
            $t->unsignedBigInteger('created_by')->nullable();
            $t->string('name');
            $t->string('authority_name')->nullable();
            $t->string('mobile')->nullable();
            $t->string('telephone')->nullable();
            $t->string('fax_no')->nullable();
            $t->string('email')->nullable();
            $t->string('website')->nullable();
            $t->text('address')->nullable();
            $t->string('tax_no')->nullable();
            $t->integer('credit_days')->default(0);
            $t->decimal('opening_balance', 10, 2)->default(0);
            $t->decimal('current_balance', 10, 2)->default(0);
            $t->string('bank_name')->nullable();
            $t->string('bank_account_no')->nullable();
            $t->text('note')->nullable();
            $t->string('image_path')->nullable();
            $t->string('status')->default('active');
            $t->timestamps();
        });
        Schema::create('purchase_invoices', fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('supplier_id'), $t->timestamps()]);
        Schema::create('purchase_returns', fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('supplier_id'), $t->timestamps()]);
        Schema::create('supplier_transactions', fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('company_id'), $t->unsignedBigInteger('financial_year_id')->nullable(), $t->unsignedBigInteger('supplier_id'), $t->date('transaction_date'), $t->string('voucher_no'), $t->string('reference_type'), $t->unsignedBigInteger('reference_id'), $t->decimal('debit', 20, 4)->default(0), $t->decimal('credit', 20, 4)->default(0), $t->decimal('balance', 20, 4)->default(0), $t->integer('status')->default(1), $t->timestamps()]);

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
            'module_supplier', 'view_supplier', 'create_supplier', 'edit_supplier', 'delete_supplier', 'print_supplier',
        ] as $permission) {
            Permission::create(['name' => $permission, 'scope' => Permission::SCOPE_COMPANY]);
        }

        foreach (['module_supplier', 'view_supplier', 'create_supplier', 'edit_supplier', 'delete_supplier', 'print_supplier'] as $permission) {
            $this->assignPermission(3, $permission);
        }

        $this->withoutMiddleware([EnsureCompanyUser::class, CheckSubscription::class, UpdateLastSeen::class, VerifyCsrfToken::class]);
    }

    public function test_unauthorized_staff_is_rejected_by_every_supplier_route(): void
    {
        $supplier = $this->supplier(1);
        $staff = User::find(2);
        $payload = $this->payload();

        foreach ($this->routes($supplier, $payload) as [$method, $name, $params, $data]) {
            $this->actingAs($staff)->{$method}(route($name, $params), $data)->assertForbidden();
        }
    }

    public function test_company_admin_and_authorized_staff_can_access_supplier_routes(): void
    {
        $supplier = $this->supplier(1);
        $payload = $this->payload();

        foreach ([User::find(1), User::find(3)] as $user) {
            foreach ($this->routes($supplier, $payload) as [$method, $name, $params, $data]) {
                $this->assertNotSame(
                    403,
                    $this->actingAs($user)->{$method}(route($name, $params), $data)->getStatusCode(),
                    $name . ' for user ' . $user->id
                );
            }
        }
    }

    public function test_delete_is_blocked_when_supplier_has_financial_history(): void
    {
        $supplier = $this->supplier(1);
        SupplierTransaction::create([
            'company_id' => 1,
            'financial_year_id' => 1,
            'supplier_id' => $supplier->id,
            'transaction_date' => '2026-06-01',
            'voucher_no' => 'TX-1',
            'reference_type' => 'purchase_invoice',
            'reference_id' => 1,
            'debit' => 0,
            'credit' => 100,
            'balance' => -100,
            'status' => 1,
        ]);

        $this->actingAs(User::find(1))
            ->post(route('company.suppliers.delete', $supplier->id))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame('active', Supplier::find($supplier->id)->status);
    }

    public function test_cross_company_supplier_access_remains_blocked_for_admin(): void
    {
        $foreign = $this->supplier(2);
        $admin = User::find(1);

        $this->actingAs($admin)->get(route('company.suppliers.show', $foreign->id))->assertNotFound();
        $this->actingAs($admin)->post(route('company.suppliers.update', $foreign->id), $this->payload())->assertNotFound();
        $this->actingAs($admin)->post(route('company.suppliers.delete', $foreign->id))->assertNotFound();
    }

    public function test_supplier_store_ignores_embedded_opening_balance(): void
    {
        $this->actingAs(User::find(1))->post(route('company.suppliers.store'), $this->payload() + [
            'opening_balance' => '1250.50',
            'current_balance' => '999.00',
        ])->assertRedirect();

        $supplier = Supplier::where('name', 'Route Supplier')->firstOrFail();
        $this->assertSame('0.00', number_format((float) $supplier->opening_balance, 2, '.', ''));
        $this->assertSame('0.00', number_format((float) $supplier->current_balance, 2, '.', ''));
    }

    private function routes(Supplier $supplier, array $payload): array
    {
        return [
            ['get', 'company.suppliers.index', [], []],
            ['get', 'company.suppliers.show', [$supplier->id], []],
            ['post', 'company.suppliers.store', [], $payload],
            ['post', 'company.suppliers.update', [$supplier->id], $payload],
            ['post', 'company.suppliers.delete', [$supplier->id], []],
            ['get', 'company.suppliers.print', [], []],
            ['get', 'company.suppliers.printProfile', [$supplier->id], []],
        ];
    }

    private function supplier(int $companyId): Supplier
    {
        return Supplier::create([
            'company_id' => $companyId,
            'name' => 'Supplier ' . $companyId,
            'opening_balance' => 0,
            'current_balance' => 0,
            'status' => 'active',
        ]);
    }

    private function payload(): array
    {
        return [
            'name' => 'Route Supplier',
            'mobile' => '9800000004',
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
