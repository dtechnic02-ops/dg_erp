<?php

namespace Tests\Feature;

use App\Http\Controllers\Company\SalesController;
use App\Models\Company;
use App\Models\ChartAccount;
use App\Models\Customer;
use App\Models\FinancialYear;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use App\Http\Middleware\CheckSubscription;
use App\Http\Middleware\EnsureCompanyUser;
use App\Http\Middleware\EnsurePermission;
use App\Http\Middleware\UpdateLastSeen;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\CreatesCompanyRouteTestFoundation;
use Tests\TestCase;

class QuotationModuleTest extends TestCase
{
    use CreatesCompanyRouteTestFoundation;

    private Company $company;
    private User $user;
    private FinancialYear $financialYear;
    private Customer $customer;
    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createCompanyRouteTestSchema();

        Schema::create('countries', function (Blueprint $table): void {
            $table->id(); $table->string('name'); $table->char('iso_code', 2)->unique();
            $table->boolean('is_active')->default(true); $table->timestamps();
        });
        Schema::create('company_ird_cbms_settings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id')->unique();
            $table->boolean('is_enabled')->default(false);
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
        Schema::table('companies', fn (Blueprint $table) => $table->unsignedBigInteger('country_id')->nullable());
        Schema::create('vats', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('company_id'); $table->string('name');
            $table->decimal('rate', 10, 4); $table->string('type')->nullable();
            $table->boolean('is_default')->default(false); $table->string('status')->default('active'); $table->timestamps();
        });
        Schema::create('services', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('company_id'); $table->unsignedBigInteger('financial_year_id')->nullable();
            $table->unsignedBigInteger('service_category_id')->nullable(); $table->string('name');
            $table->string('service_code')->nullable(); $table->string('slug')->nullable();
            $table->decimal('price', 20, 4); $table->unsignedBigInteger('vat_id')->nullable();
            $table->string('upload_path')->nullable(); $table->text('description')->nullable();
            $table->string('status')->default('active'); $table->unsignedBigInteger('created_by')->nullable(); $table->timestamps();
        });
        foreach (['quotations', 'quotation_items'] as $tableName) {
            $matches = glob(database_path("migrations/*_create_{$tableName}_table.php")) ?: [];
            $this->assertCount(1, $matches, "Expected one final {$tableName} migration.");
            (require $matches[0])->up();
        }

        $np = DB::table('countries')->insertGetId(['name' => 'Nepal', 'iso_code' => 'NP', 'is_active' => 1]);
        $role = $this->createCompanyDashboardRole();
        $this->company = $this->createActiveCompany();
        $this->company->update(['country_id' => $np]);
        $this->user = $this->createCompanyAdmin($this->company, $role);
        $this->financialYear = $this->createActiveFinancialYear($this->company, $this->user);
        $this->customer = $this->createCustomer($this->company, $this->user);
        $vatId = DB::table('vats')->insertGetId(['company_id' => $this->company->id, 'name' => 'VAT 13', 'rate' => 13, 'status' => 'active']);
        $this->service = Service::create(['company_id' => $this->company->id, 'financial_year_id' => $this->financialYear->id, 'name' => 'Consulting', 'price' => 100, 'vat_id' => $vatId, 'status' => 'active']);
        $this->createSalesChartAccounts($this->company, $this->user);
        ChartAccount::create([
            'company_id' => $this->company->id,
            'code' => '4200',
            'name' => 'Service Revenue',
            'account_class' => 'income',
            'normal_balance' => 'credit',
            'system_code' => 'SERVICE_REVENUE',
            'is_control' => false,
            'is_system' => true,
            'allow_manual_entry' => false,
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);
        ChartAccount::create([
            'company_id' => $this->company->id,
            'code' => '2200',
            'name' => 'Output Tax Payable',
            'account_class' => 'liability',
            'normal_balance' => 'credit',
            'system_code' => 'OUTPUT_TAX_PAYABLE',
            'is_control' => false,
            'is_system' => true,
            'allow_manual_entry' => false,
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);
        $this->actingAs($this->user);
        $this->withoutMiddleware([
            EnsureCompanyUser::class,
            CheckSubscription::class,
            EnsurePermission::class,
            UpdateLastSeen::class,
        ]);
    }

    public function test_draft_create_edit_delete_are_non_financial_and_company_scoped(): void
    {
        $this->post(route('company.quotations.store'), $this->payload())->assertRedirect()->assertSessionHasNoErrors();
        $quotation = Quotation::with('items')->sole();
        $this->assertSame(Quotation::STATUS_DRAFT, $quotation->status);
        $this->assertSame('200.0000', $quotation->subtotal);
        $this->assertSame('26.0000', $quotation->total_vat);
        $this->assertSame('226.0000', $quotation->grand_total);
        $this->assertSame('2026-05-01', $quotation->quotation_date->toDateString());
        $this->assertSame(1, $quotation->items->count());
        $this->assertNoFinancialEffects();

        $this->put(route('company.quotations.update', $quotation), $this->payload(['discount_amount' => 10, 'note' => 'Updated']))->assertRedirect();
        $this->assertSame('216.0000', $quotation->fresh()->grand_total);
        $this->assertSame('Updated', $quotation->fresh()->note);
        $this->assertNoFinancialEffects();

        $this->delete(route('company.quotations.destroy', $quotation))->assertRedirect(route('company.quotations.index'));
        $this->assertDatabaseCount('quotations', 0);
        $this->assertDatabaseCount('quotation_items', 0);
        $this->assertNoFinancialEffects();
    }

    public function test_approval_freezes_content_and_has_no_financial_or_stock_effect(): void
    {
        $quotation = $this->draft();
        $this->post(route('company.quotations.approve', $quotation))->assertRedirect();
        $quotation->refresh();
        $this->assertSame(Quotation::STATUS_APPROVED, $quotation->status);
        $this->assertSame($this->user->id, $quotation->approved_by);
        $this->assertNotNull($quotation->approved_at);
        $this->get(route('company.quotations.edit', $quotation))->assertStatus(409);
        $this->put(route('company.quotations.update', $quotation), $this->payload(['note' => 'Forbidden']))->assertStatus(409);
        $this->delete(route('company.quotations.destroy', $quotation))->assertStatus(409);
        $this->assertSame('Test quotation', $quotation->fresh()->note);
        $this->assertNoFinancialEffects();
    }

    public function test_approved_quotation_generates_one_sales_invoice_through_existing_sales_workflow(): void
    {
        $quotation = $this->approved();
        $response = $this->post(route('company.quotations.convert', $quotation), ['invoice_date' => '2026-06-15']);
        $invoice = DB::table('sales_invoices')->sole();

        $response->assertRedirect(route('company.sales.show', $invoice->id));
        $this->assertStringStartsWith('SI-' . $this->company->id . '-' . $this->financialYear->id . '-', $invoice->invoice_no);
        $this->assertSame('2026-06-15', substr($invoice->sale_date, 0, 10));
        $this->assertSame($this->customer->id, $invoice->customer_id);
        $this->assertEqualsWithDelta(226.0, (float) $invoice->grand_total, 0.0001);
        $this->assertDatabaseHas('sales_items', ['sales_invoice_id' => $invoice->id, 'service_id' => $this->service->id, 'quantity' => 2, 'vat_rate' => 13]);
        $this->assertDatabaseHas('quotations', ['id' => $quotation->id, 'status' => Quotation::STATUS_CONVERTED, 'sales_invoice_id' => $invoice->id, 'converted_by' => $this->user->id]);
        $this->assertDatabaseCount('customer_transactions', 1);
        $this->assertDatabaseCount('accounting_entries', 1);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_product_quotation_conversion_runs_the_complete_sales_pipeline(): void
    {
        $product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Quotation Product',
            'cost_price' => '40.00000000',
            'current_stock' => '5.000000',
            'status' => 'active',
        ]);
        $movementId = DB::table('stock_movements')->insertGetId([
            'company_id' => $this->company->id,
            'financial_year_id' => $this->financialYear->id,
            'transaction_date' => '2026-01-01',
            'product_id' => $product->id,
            'type' => 'opening_stock',
            'quantity' => '5.000000',
            'before_stock' => '0.000000',
            'after_stock' => '5.000000',
            'unit_price' => '40.00000000',
            'reference_no' => 'TEST-OPENING',
            'created_by' => $this->user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('inventory_valuations')->insert([
            'company_id' => $this->company->id,
            'product_id' => $product->id,
            'stock_movement_id' => $movementId,
            'valuation_sequence' => 1,
            'movement_type' => 'opening_stock',
            'source_module' => 'product',
            'source_type' => 'product_opening_stock',
            'source_id' => $product->id,
            'source_event' => 'created',
            'quantity_before' => '0.000000',
            'quantity_change' => '5.000000',
            'quantity_after' => '5.000000',
            'inventory_value_before' => '0.0000',
            'inventory_value_change' => '200.0000',
            'inventory_value_after' => '200.0000',
            'average_cost_before' => '0.00000000',
            'movement_unit_cost' => '40.00000000',
            'average_cost_after' => '40.00000000',
            'valued_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $quotation = $this->draftFromPayload($this->payload([
            'item_type' => ['product'],
            'product_id' => [$product->id],
            'service_id' => [null],
            'quantity' => [2],
            'unit_price' => [100],
            'vat_rate' => [13],
        ]));
        $this->post(route('company.quotations.approve', $quotation))->assertRedirect();
        $this->post(route('company.quotations.convert', $quotation), ['invoice_date' => '2026-06-15'])->assertRedirect();

        $invoice = DB::table('sales_invoices')->sole();
        $quotation->refresh();
        $this->assertSame(Quotation::STATUS_CONVERTED, $quotation->status);
        $this->assertSame($invoice->id, $quotation->sales_invoice_id);
        $this->assertDatabaseHas('sales_items', ['sales_invoice_id' => $invoice->id, 'product_id' => $product->id, 'quantity' => 2]);
        $this->assertEqualsWithDelta(3.0, (float) $product->fresh()->current_stock, 0.000001);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $product->id, 'reference_no' => $invoice->invoice_no, 'quantity' => -2]);
        $this->assertDatabaseHas('sales_cost_snapshots', ['sales_invoice_id' => $invoice->id, 'product_id' => $product->id]);
        $this->assertDatabaseHas('customer_transactions', ['reference_type' => 'sales_invoice', 'reference_id' => $invoice->id, 'debit' => 226]);
        $this->assertEqualsWithDelta(226.0, (float) $this->customer->fresh()->current_balance, 0.0001);
        $this->assertDatabaseCount('accounting_entries', 2);
        $salesList = app(SalesController::class)->index(Request::create(route('company.sales.index'), 'GET'));
        $this->assertTrue($salesList->getData()['invoices']->contains('id', $invoice->id));

        $this->post(route('company.quotations.convert', $quotation), ['invoice_date' => '2026-06-16'])
            ->assertRedirect()->assertSessionHas('error');
        $this->assertDatabaseCount('sales_invoices', 1);
        $this->assertDatabaseCount('sales_items', 1);
        $this->assertDatabaseCount('sales_cost_snapshots', 1);
    }

    public function test_duplicate_conversion_is_blocked_and_generated_invoice_link_remains_single(): void
    {
        $quotation = $this->approved();
        $this->post(route('company.quotations.convert', $quotation), ['invoice_date' => '2026-06-15'])->assertRedirect();
        $invoiceId = $quotation->fresh()->sales_invoice_id;

        $this->post(route('company.quotations.convert', $quotation), ['invoice_date' => '2026-06-16'])
            ->assertRedirect()->assertSessionHas('error');
        $this->assertDatabaseCount('sales_invoices', 1);
        $this->assertSame($invoiceId, $quotation->fresh()->sales_invoice_id);
        $this->assertDatabaseCount('accounting_entries', 1);
    }

    public function test_failed_conversion_rolls_back_and_leaves_quotation_approved(): void
    {
        $quotation = $this->approved();
        $this->post(route('company.quotations.convert', $quotation), ['invoice_date' => '2027-01-01'])
            ->assertRedirect()->assertSessionHas('error');

        $this->assertSame(Quotation::STATUS_APPROVED, $quotation->fresh()->status);
        $this->assertNull($quotation->fresh()->sales_invoice_id);
        $this->assertDatabaseCount('sales_invoices', 0);
        $this->assertDatabaseCount('sales_items', 0);
        $this->assertDatabaseCount('customer_transactions', 0);
        $this->assertDatabaseCount('accounting_entries', 0);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertFalse(session()->has('pending_sales_invoice'));
    }

    public function test_cross_company_access_and_cross_company_masters_are_rejected(): void
    {
        $quotation = $this->draft();
        $other = Company::create(['company_name' => 'Other', 'email' => 'other-q@example.test', 'mobile' => '999', 'status' => 'active']);
        $otherUser = User::create(['name' => 'Other', 'email' => 'other-user-q@example.test', 'password' => 'secret', 'role_id' => Role::COMPANY_ADMIN_ID, 'company_id' => $other->id]);

        $this->actingAs($otherUser)->get(route('company.quotations.show', $quotation))->assertNotFound();
        $this->actingAs($otherUser)->post(route('company.quotations.approve', $quotation))->assertNotFound();
        $this->post(route('company.quotations.store'), $this->payload())->assertSessionHasErrors('customer_id');
        $this->assertDatabaseCount('quotations', 1);
    }

    public function test_permissions_are_registered_on_every_quotation_route(): void
    {
        $expected = [
            'company.quotations.index' => 'view_quotation', 'company.quotations.create' => 'create_quotation',
            'company.quotations.store' => 'create_quotation', 'company.quotations.show' => 'view_quotation',
            'company.quotations.edit' => 'edit_quotation', 'company.quotations.update' => 'edit_quotation',
            'company.quotations.destroy' => 'delete_quotation', 'company.quotations.approve' => 'approve_quotation',
            'company.quotations.convert' => 'generate_quotation_invoice', 'company.quotations.print' => 'print_quotation',
        ];
        foreach ($expected as $routeName => $permission) {
            $this->assertContains('permission:' . $permission, app('router')->getRoutes()->getByName($routeName)->gatherMiddleware());
        }
        $seeder = file_get_contents(database_path('seeders/PermissionSeeder.php'));
        foreach (array_unique($expected) as $permission) $this->assertStringContainsString("'{$permission}'", $seeder);
    }

    public function test_quotation_date_uses_central_nepal_bs_and_has_no_bs_database_column(): void
    {
        $source = file_get_contents(resource_path('views/company/quotations/partials/form.blade.php'));
        $this->assertStringContainsString("'adInputId' => 'quotation_date'", $source);
        $this->assertStringNotContainsString('name="quotation_date_bs"', $source);
        $this->assertFalse(Schema::hasColumn('quotations', 'quotation_date_bs'));

        $this->actingAs($this->user);
        $display = Blade::render("@include('company.components.nepali-date-display',['adDate'=>'2024-04-13'])");
        $this->assertStringContainsString('2081-01-01 BS', $display);
    }

    public function test_non_nepal_company_does_not_render_bs_date(): void
    {
        $otherCountry = DB::table('countries')->insertGetId([
            'name' => 'United Arab Emirates', 'iso_code' => 'AE', 'is_active' => 1,
        ]);
        $this->company->update(['country_id' => $otherCountry]);
        $this->user->setRelation('company', $this->company->fresh());

        $display = Blade::render("@include('company.components.nepali-date-display',['adDate'=>'2024-04-13'])");

        $this->assertStringNotContainsString('BS', $display);
        $this->assertStringNotContainsString('2081-01-01', $display);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'customer_id' => $this->customer->id, 'quotation_date' => '2026-05-01', 'valid_until' => '2026-05-31',
            'reference_no' => 'REF-1', 'note' => 'Test quotation', 'item_type' => ['service'],
            'product_id' => [null], 'service_id' => [$this->service->id], 'quantity' => [2],
            'unit_price' => [100], 'vat_rate' => [13], 'discount_amount' => 0,
        ], $overrides);
    }

    private function draft(): Quotation
    {
        return $this->draftFromPayload($this->payload());
    }

    private function draftFromPayload(array $payload): Quotation
    {
        $this->post(route('company.quotations.store'), $payload)->assertRedirect()->assertSessionHasNoErrors();
        return Quotation::sole();
    }

    private function approved(): Quotation
    {
        $quotation = $this->draft();
        $this->post(route('company.quotations.approve', $quotation))->assertRedirect();
        return $quotation->fresh();
    }

    private function assertNoFinancialEffects(): void
    {
        foreach (['sales_invoices', 'sales_items', 'customer_transactions', 'account_transactions', 'stock_movements', 'inventory_valuations', 'sales_cost_snapshots', 'accounting_entries', 'accounting_entry_lines'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
    }
}
