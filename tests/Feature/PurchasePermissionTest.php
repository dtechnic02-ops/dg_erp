<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckSubscription;
use App\Http\Middleware\EnsureCompanyUser;
use App\Http\Middleware\UpdateLastSeen;
use App\Models\Permission;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PurchasePermissionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'purchase_return_refunds', 'purchase_returns', 'purchase_payments', 'purchase_items', 'purchase_invoices', 'accounts', 'vats', 'services', 'products', 'units',
            'suppliers', 'user_permissions', 'permissions', 'users', 'roles',
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
        Schema::create('suppliers', fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('company_id'), $t->string('name'), $t->string('status')->default('active'), $t->timestamps()]);
        Schema::create('products', fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('company_id'), $t->unsignedBigInteger('unit_id')->nullable(), $t->unsignedBigInteger('vat_id')->nullable(), $t->string('name'), $t->string('status')->default('active'), $t->timestamps()]);
        Schema::create('services', fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('company_id'), $t->unsignedBigInteger('vat_id')->nullable(), $t->string('name'), $t->string('status')->default('active'), $t->timestamps()]);
        Schema::create('vats', fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('company_id'), $t->string('name'), $t->decimal('rate', 5, 2), $t->boolean('is_default')->default(false), $t->timestamps()]);
        Schema::create('accounts', fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('company_id'), $t->string('account_type'), $t->string('account_name'), $t->string('status')->default('active'), $t->timestamps()]);
        Schema::create('purchase_invoices', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('company_id');
            $t->unsignedBigInteger('financial_year_id');
            $t->unsignedBigInteger('supplier_id')->nullable();
            $t->string('invoice_no');
            $t->date('purchase_date');
            $t->decimal('grand_total', 20, 4)->default(0);
            $t->decimal('paid_amount', 20, 4)->default(0);
            $t->decimal('due_amount', 20, 4)->default(0);
            $t->string('payment_status')->default('unpaid');
            $t->integer('status')->default(1);
            $t->text('note')->nullable();
            $t->timestamps();
        });
        Schema::create('purchase_items', fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('company_id'), $t->unsignedBigInteger('financial_year_id'), $t->unsignedBigInteger('purchase_invoice_id'), $t->string('item_type'), $t->unsignedBigInteger('product_id')->nullable(), $t->unsignedBigInteger('service_id')->nullable(), $t->decimal('quantity', 20, 4)->default(1), $t->decimal('unit_price', 20, 4)->default(0), $t->decimal('total_price', 20, 4)->default(0), $t->decimal('vat_amount', 20, 4)->default(0), $t->integer('status')->default(1), $t->timestamps()]);
        Schema::create('purchase_payments', fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('purchase_invoice_id'), $t->integer('status')->default(1), $t->timestamps()]);
        Schema::create('purchase_returns', fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('company_id'), $t->unsignedBigInteger('purchase_invoice_id'), $t->integer('status')->default(1), $t->timestamps()]);
        Schema::create('purchase_return_refunds', fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('company_id'), $t->unsignedBigInteger('purchase_return_id'), $t->string('status')->default('active'), $t->timestamps()]);

        DB::table('companies')->insert([['id' => 1, 'company_name' => 'One'], ['id' => 2, 'company_name' => 'Two']]);
        DB::table('roles')->insert([['id' => 2, 'name' => 'company_admin'], ['id' => 3, 'name' => 'staff']]);
        DB::table('users')->insert([
            ['id' => 1, 'name' => 'Admin', 'email' => 'admin@test', 'password' => Hash::make('x'), 'company_id' => 1, 'role_id' => 2],
            ['id' => 2, 'name' => 'Staff', 'email' => 'staff@test', 'password' => Hash::make('x'), 'company_id' => 1, 'role_id' => 3],
            ['id' => 3, 'name' => 'Authorized Staff', 'email' => 'auth-staff@test', 'password' => Hash::make('x'), 'company_id' => 1, 'role_id' => 3],
        ]);
        DB::table('company_subscriptions')->insert(['company_id' => 1, 'status' => 'active']);
        DB::table('financial_years')->insert(['id' => 1, 'company_id' => 1, 'name' => 'FY26', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => 1]);
        DB::table('suppliers')->insert(['id' => 1, 'company_id' => 1, 'name' => 'Supplier One', 'status' => 'active']);

        foreach ([
            'module_purchase', 'view_purchase', 'create_purchase', 'edit_purchase', 'cancel_purchase', 'print_purchase',
        ] as $permission) {
            Permission::create(['name' => $permission, 'scope' => Permission::SCOPE_COMPANY]);
        }

        foreach (['module_purchase', 'view_purchase', 'create_purchase', 'edit_purchase', 'cancel_purchase', 'print_purchase'] as $permission) {
            $this->assignPermission(3, $permission);
        }

        $this->withoutMiddleware([EnsureCompanyUser::class, CheckSubscription::class, UpdateLastSeen::class, VerifyCsrfToken::class]);
    }

    public function test_unauthorized_staff_is_rejected_by_every_purchase_route(): void
    {
        $invoice = $this->invoice(1);
        $staff = User::find(2);

        foreach ($this->routes($invoice) as [$method, $name, $params, $data]) {
            $this->actingAs($staff)->{$method}(route($name, $params), $data)->assertForbidden();
        }
    }

    public function test_company_admin_and_authorized_staff_can_access_purchase_routes(): void
    {
        $invoice = $this->invoice(1);

        foreach ([User::find(1), User::find(3)] as $user) {
            foreach ($this->routes($invoice) as [$method, $name, $params, $data]) {
                $this->assertNotSame(
                    403,
                    $this->actingAs($user)->{$method}(route($name, $params), $data)->getStatusCode(),
                    $name . ' for user ' . $user->id
                );
            }
        }
    }

    public function test_cross_company_purchase_access_remains_blocked_for_admin(): void
    {
        $foreign = $this->invoice(2);
        $admin = User::find(1);

        $this->actingAs($admin)->get(route('company.purchases.show', $foreign->id))->assertNotFound();
        $this->actingAs($admin)->get(route('company.purchases.edit', $foreign->id))->assertNotFound();

        $this->actingAs($admin)
            ->put(route('company.purchases.update', $foreign->id), $this->updatePayload())
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->actingAs($admin)
            ->post(route('company.purchases.cancel', $foreign->id), $this->cancelPayload())
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(1, PurchaseInvoice::find($foreign->id)->status);
    }

    public function test_index_searches_invoice_and_supplier_without_losing_company_isolation(): void
    {
        $ownSupplier = Supplier::create(['company_id' => 1, 'name' => 'Acme Search Supplier', 'status' => 'active']);
        $foreignSupplier = Supplier::create(['company_id' => 2, 'name' => 'Acme Search Supplier', 'status' => 'active']);
        $ownInvoice = PurchaseInvoice::create([
            'company_id' => 1, 'financial_year_id' => 1, 'supplier_id' => $ownSupplier->id,
            'invoice_no' => 'PU-OWN-NEEDLE', 'purchase_date' => '2026-06-01', 'status' => 1,
        ]);
        $foreignInvoice = PurchaseInvoice::create([
            'company_id' => 2, 'financial_year_id' => 1, 'supplier_id' => $foreignSupplier->id,
            'invoice_no' => 'PU-FOREIGN-NEEDLE', 'purchase_date' => '2026-06-01', 'status' => 1,
        ]);

        $invoiceResponse = $this->actingAs(User::findOrFail(1))
            ->get(route('company.purchases.index', ['search' => 'NEEDLE']));

        $invoiceResponse->assertOk()->assertViewHas('invoices', function ($invoices) use ($ownInvoice, $foreignInvoice): bool {
            return $invoices->pluck('id')->contains($ownInvoice->id)
                && ! $invoices->pluck('id')->contains($foreignInvoice->id);
        });

        $supplierResponse = $this->actingAs(User::findOrFail(1))
            ->get(route('company.purchases.index', ['search' => 'Acme Search']));

        $supplierResponse->assertOk()->assertViewHas('invoices', function ($invoices) use ($ownInvoice, $foreignInvoice): bool {
            return $invoices->pluck('id')->contains($ownInvoice->id)
                && ! $invoices->pluck('id')->contains($foreignInvoice->id);
        });
    }

    public function test_cancelled_purchase_cannot_be_edited(): void
    {
        $invoice = $this->invoice(1);
        $invoice->update(['status' => 0]);

        $this->actingAs(User::find(1))
            ->put(route('company.purchases.update', $invoice->id), $this->updatePayload())
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_fifty_purchase_rows_survive_a_late_row_validation_failure(): void
    {
        $rows = 50;
        $payload = [
            'supplier_id' => 1,
            'purchase_date' => '2026-06-15',
            'item_type' => array_fill(0, $rows, 'product'),
            'product_id' => array_fill(0, $rows, '1'),
            'service_id' => array_fill(0, $rows, ''),
            'quantity' => array_fill(0, $rows, '2'),
            'unit_price' => array_fill(0, $rows, '10.50'),
            'vat_rate' => array_fill(0, $rows, '13'),
            'vat_amount' => array_fill(0, $rows, '2.73'),
            'total_price' => array_fill(0, $rows, '23.73'),
            'paid_amount' => '0',
            'discount_amount' => '5.00',
            'subtotal' => '1050.00',
            'total_vat' => '136.50',
            'grand_total' => '1181.50',
            'note' => 'Preserve every entered purchase line.',
        ];
        $payload['quantity'][49] = '';

        $response = $this->actingAs(User::findOrFail(1))
            ->from(route('company.purchases.create'))
            ->post(route('company.purchases.store'), $payload);

        $response->assertRedirect(route('company.purchases.create'))
            ->assertSessionHasErrors(['quantity.49'])
            ->assertSessionHasInput('item_type', $payload['item_type'])
            ->assertSessionHasInput('product_id', $payload['product_id'])
            ->assertSessionHasInput('service_id', $payload['service_id'])
            ->assertSessionHasInput('quantity', $payload['quantity'])
            ->assertSessionHasInput('unit_price', $payload['unit_price'])
            ->assertSessionHasInput('vat_rate', $payload['vat_rate'])
            ->assertSessionHasInput('vat_amount', $payload['vat_amount'])
            ->assertSessionHasInput('total_price', $payload['total_price'])
            ->assertSessionHasInput('supplier_id', 1)
            ->assertSessionHasInput('note', $payload['note']);

        $this->assertSame(
            'Row 50: Quantity is required.',
            session('errors')->get('quantity.49')[0]
        );
        $this->assertCount(50, session()->getOldInput('item_type'));
        $this->assertSame('2', session()->getOldInput('quantity')[0]);
        $this->assertNull(session()->getOldInput('quantity')[49]);
    }

    private function routes(PurchaseInvoice $invoice): array
    {
        return [
            ['get', 'company.purchases.index', [], []],
            ['get', 'company.purchases.create', [], []],
            ['get', 'company.purchases.show', [$invoice->id], []],
            ['get', 'company.purchases.edit', [$invoice->id], []],
            ['put', 'company.purchases.update', [$invoice->id], $this->updatePayload()],
            ['get', 'company.purchases.print-list', [], []],
            ['get', 'company.purchases.print', [$invoice->id], []],
            ['post', 'company.purchases.cancel', [$invoice->id], $this->cancelPayload()],
        ];
    }

    private function invoice(int $companyId): PurchaseInvoice
    {
        $supplierId = Supplier::create([
            'company_id' => $companyId,
            'name' => 'Supplier ' . $companyId,
            'status' => 'active',
        ])->id;

        return PurchaseInvoice::create([
            'company_id' => $companyId,
            'financial_year_id' => 1,
            'supplier_id' => $supplierId,
            'invoice_no' => 'PU-' . $companyId . '-1-00001',
            'purchase_date' => '2026-06-01',
            'grand_total' => 100,
            'paid_amount' => 0,
            'due_amount' => 100,
            'payment_status' => 'unpaid',
            'status' => 1,
        ]);
    }

    private function updatePayload(): array
    {
        return [
            'purchase_date' => '2026-06-02',
            'note' => 'Updated note',
        ];
    }

    private function cancelPayload(): array
    {
        return [
            'cancel_date' => '2026-06-15',
            'cancel_reason' => 'Audit test cancellation',
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
