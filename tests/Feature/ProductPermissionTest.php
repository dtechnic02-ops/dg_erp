<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckSubscription;
use App\Http\Middleware\EnsureCompanyUser;
use App\Http\Middleware\UpdateLastSeen;
use App\Models\Permission;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductPermissionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'stock_movements', 'products', 'units', 'product_categories', 'brands',
            'user_permissions', 'permissions', 'users', 'roles',
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
        Schema::create('units', fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('company_id'), $t->string('name'), $t->timestamps()]);
        Schema::create('product_categories', fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('company_id'), $t->string('name'), $t->timestamps()]);
        Schema::create('brands', fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('company_id'), $t->string('name'), $t->timestamps()]);
        Schema::create('products', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('company_id');
            $t->unsignedBigInteger('category_id')->nullable();
            $t->unsignedBigInteger('brand_id')->nullable();
            $t->unsignedBigInteger('unit_id')->nullable();
            $t->string('name');
            $t->string('barcode')->nullable();
            $t->string('origin_type')->nullable();
            $t->string('hs_code')->nullable();
            $t->string('product_type')->nullable();
            $t->string('model')->nullable();
            $t->string('size')->nullable();
            $t->decimal('cost_price', 10, 2)->default(0);
            $t->decimal('retail_price', 10, 2)->default(0);
            $t->decimal('wholesale_price', 10, 2)->default(0);
            $t->integer('stock_alert')->default(0);
            $t->decimal('current_stock', 15, 2)->default(0);
            $t->string('status')->default('active');
            $t->string('image')->nullable();
            $t->string('batch_no')->nullable();
            $t->date('manufacture_date')->nullable();
            $t->date('expiry_date')->nullable();
            $t->boolean('allow_online')->default(false);
            $t->text('description')->nullable();
            $t->timestamps();
        });
        Schema::create('stock_movements', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('company_id');
            $t->unsignedBigInteger('financial_year_id')->nullable();
            $t->date('transaction_date')->nullable();
            $t->unsignedBigInteger('product_id');
            $t->string('type');
            $t->decimal('quantity', 15, 2)->default(0);
            $t->decimal('before_stock', 15, 2)->default(0);
            $t->decimal('after_stock', 15, 2)->default(0);
            $t->decimal('unit_price', 15, 2)->nullable();
            $t->string('reference_no')->nullable();
            $t->text('note')->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();
        });

        DB::table('companies')->insert([['id' => 1, 'company_name' => 'One'], ['id' => 2, 'company_name' => 'Two']]);
        DB::table('roles')->insert([['id' => 2, 'name' => 'company_admin'], ['id' => 3, 'name' => 'staff']]);
        DB::table('users')->insert([
            ['id' => 1, 'name' => 'Admin', 'email' => 'admin@test', 'password' => Hash::make('x'), 'company_id' => 1, 'role_id' => 2],
            ['id' => 2, 'name' => 'Staff', 'email' => 'staff@test', 'password' => Hash::make('x'), 'company_id' => 1, 'role_id' => 3],
            ['id' => 3, 'name' => 'Authorized Staff', 'email' => 'auth-staff@test', 'password' => Hash::make('x'), 'company_id' => 1, 'role_id' => 3],
        ]);
        DB::table('company_subscriptions')->insert(['company_id' => 1, 'status' => 'active']);
        DB::table('financial_years')->insert(['id' => 1, 'company_id' => 1, 'name' => 'FY26', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => 1]);
        DB::table('units')->insert(['id' => 1, 'company_id' => 1, 'name' => 'PCS']);
        DB::table('product_categories')->insert(['id' => 1, 'company_id' => 1, 'name' => 'General']);
        DB::table('brands')->insert(['id' => 1, 'company_id' => 1, 'name' => 'Brand']);

        foreach (['module_stock', 'view_stock', 'print_stock'] as $permission) {
            Permission::create(['name' => $permission, 'scope' => Permission::SCOPE_COMPANY]);
        }

        foreach (['module_stock', 'view_stock', 'print_stock'] as $permission) {
            $this->assignPermission(3, $permission);
        }

        $this->withoutMiddleware([EnsureCompanyUser::class, CheckSubscription::class, UpdateLastSeen::class, VerifyCsrfToken::class]);
    }

    public function test_unauthorized_staff_is_rejected_by_every_product_and_stock_route(): void
    {
        $product = $this->product(1);
        $staff = User::find(2);
        $payload = $this->payload();

        foreach ($this->routes($product, $payload) as [$method, $name, $params, $data]) {
            $this->actingAs($staff)->{$method}(route($name, $params), $data)->assertForbidden();
        }
    }

    public function test_company_admin_and_authorized_staff_can_access_product_and_stock_routes(): void
    {
        $product = $this->product(1);
        $payload = $this->payload();

        foreach ([User::find(1), User::find(3)] as $user) {
            foreach ($this->routes($product, $payload) as [$method, $name, $params, $data]) {
                $this->assertNotSame(
                    403,
                    $this->actingAs($user)->{$method}(route($name, $params), $data)->getStatusCode(),
                    $name . ' for user ' . $user->id
                );
            }
        }
    }

    public function test_delete_archives_product_with_stock_history_instead_of_hard_delete(): void
    {
        $product = $this->product(1);
        StockMovement::create([
            'company_id' => 1,
            'financial_year_id' => 1,
            'transaction_date' => '2026-06-01',
            'product_id' => $product->id,
            'type' => 'opening_stock',
            'quantity' => 10,
            'before_stock' => 0,
            'after_stock' => 10,
            'unit_price' => 5,
            'reference_no' => 'OPENING',
        ]);

        $this->actingAs(User::find(1))
            ->delete(route('company.products.destroy', $product->id))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('inactive', Product::find($product->id)->status);
    }

    public function test_cross_company_product_access_remains_blocked_for_admin(): void
    {
        $foreign = $this->product(2);
        $admin = User::find(1);

        $this->actingAs($admin)->get(route('company.products.show', $foreign->id))->assertNotFound();
        $this->actingAs($admin)->put(route('company.products.update', $foreign->id), $this->payload())->assertNotFound();
        $this->actingAs($admin)->delete(route('company.products.destroy', $foreign->id))->assertNotFound();
    }

    public function test_brand_is_optional_and_foreign_brand_is_rejected(): void
    {
        $this->actingAs(User::findOrFail(1))
            ->post(route('company.products.store'), $this->payload() + ['brand_id' => ''])
            ->assertRedirect(route('company.products.index'))
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('products', ['name' => 'Route Product', 'company_id' => 1, 'brand_id' => null]);

        DB::table('brands')->insert(['id' => 2, 'company_id' => 2, 'name' => 'Foreign Brand']);
        $this->post(route('company.products.store'), array_replace($this->payload(), ['name' => 'Invalid brand product', 'brand_id' => 2]))
            ->assertSessionHasErrors('brand_id');
        $this->assertDatabaseMissing('products', ['name' => 'Invalid brand product']);
    }

    public function test_product_fiscal_details_are_controlled_normalized_and_optional(): void
    {
        $admin = User::findOrFail(1);
        $this->actingAs($admin)->post(route('company.products.store'), array_replace($this->payload(), [
            'name' => 'Imported Device', 'origin_type' => 'imported', 'hs_code' => ' 0847 10 ',
            'product_type' => ' Computer ', 'model' => ' X100 ', 'size' => ' 13 inch ',
        ]))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('products', [
            'name' => 'Imported Device', 'origin_type' => 'imported', 'hs_code' => '084710',
            'product_type' => 'Computer', 'model' => 'X100', 'size' => '13 inch',
        ]);

        $this->post(route('company.products.store'), array_replace($this->payload(), [
            'name' => 'Leading Zero Device', 'origin_type' => 'imported', 'hs_code' => '0123',
        ]))->assertSessionHasNoErrors();
        $this->assertSame('0123', Product::where('name', 'Leading Zero Device')->value('hs_code'));

        $this->post(route('company.products.store'), array_replace($this->payload(), [
            'name' => 'Domestic Item', 'origin_type' => 'domestic', 'hs_code' => null,
        ]))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('products', ['name' => 'Domestic Item', 'origin_type' => 'domestic', 'hs_code' => null]);

        $this->post(route('company.products.store'), array_replace($this->payload(), [
            'name' => 'Unknown Item', 'origin_type' => 'unknown', 'hs_code' => null,
        ]))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('products', ['name' => 'Unknown Item', 'origin_type' => 'unknown']);

        foreach (['123', '12-A', '8471.30'] as $index => $invalid) {
            $this->post(route('company.products.store'), array_replace($this->payload(), [
                'name' => 'Invalid Hs '.$index, 'origin_type' => 'imported', 'hs_code' => $invalid,
            ]))->assertSessionHasErrors('hs_code');
            $this->assertDatabaseMissing('products', ['name' => 'Invalid Hs '.$index]);
        }
    }

    private function routes(Product $product, array $payload): array
    {
        return [
            ['get', 'company.products.index', [], []],
            ['get', 'company.products.create', [], []],
            ['get', 'company.products.show', [$product->id], []],
            ['get', 'company.products.edit', [$product->id], []],
            ['post', 'company.products.store', [], $payload],
            ['put', 'company.products.update', [$product->id], $payload],
            ['delete', 'company.products.destroy', [$product->id], []],
            ['get', 'company.products.print', [], []],
            ['get', 'company.products.printProfile', [$product->id], []],
            ['get', 'company.stock-ledger.index', [], []],
            ['post', 'company.stock-ledger.sync', [], []],
            ['get', 'company.stock-ledger.pdf', [], []],
        ];
    }

    private function product(int $companyId): Product
    {
        return Product::create([
            'company_id' => $companyId,
            'category_id' => 1,
            'unit_id' => 1,
            'name' => 'Product ' . $companyId,
            'cost_price' => 10,
            'retail_price' => 15,
            'wholesale_price' => 12,
            'current_stock' => 0,
            'status' => 'active',
        ]);
    }

    private function payload(): array
    {
        return [
            'name' => 'Route Product',
            'category_id' => 1,
            'unit_id' => 1,
            'cost_price' => '10.00',
            'retail_price' => '15.00',
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
