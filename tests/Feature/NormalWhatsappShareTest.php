<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckSubscription;
use App\Http\Middleware\EnsureCompanyUser;
use App\Http\Middleware\UpdateLastSeen;
use App\Models\Company;
use App\Models\CompanyWhatsappSetting;
use App\Models\Customer;
use App\Models\Permission;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use App\Models\Supplier;
use App\Models\User;
use App\Services\WhatsappShareService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class NormalWhatsappShareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['purchase_invoices', 'sales_invoices', 'suppliers', 'company_whatsapp_settings', 'customers', 'user_permissions', 'permissions', 'users', 'roles', 'companies', 'countries'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('countries', fn (Blueprint $t) => [$t->id(), $t->string('name'), $t->char('iso_code', 2), $t->boolean('is_active')->default(true), $t->timestamps()]);
        Schema::create('companies', fn (Blueprint $t) => [$t->id(), $t->string('company_name'), $t->unsignedBigInteger('country_id')->nullable(), $t->string('status')->default('active'), $t->timestamps()]);
        Schema::create('roles', fn (Blueprint $t) => [$t->id(), $t->string('name'), $t->timestamps()]);
        Schema::create('users', fn (Blueprint $t) => [$t->id(), $t->string('name'), $t->string('email'), $t->string('password'), $t->unsignedBigInteger('company_id'), $t->unsignedBigInteger('role_id'), $t->string('account_status')->default('active'), $t->rememberToken(), $t->timestamps()]);
        Schema::create('permissions', fn (Blueprint $t) => [$t->id(), $t->string('name'), $t->string('scope')->default('company'), $t->timestamps()]);
        Schema::create('user_permissions', fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('user_id'), $t->unsignedBigInteger('permission_id'), $t->boolean('is_allowed'), $t->timestamps()]);
        Schema::create('company_whatsapp_settings', fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('company_id')->unique(), $t->boolean('is_enabled')->default(false), $t->timestamps()]);
        Schema::create('customers', fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('company_id'), $t->string('name'), $t->string('mobile')->nullable(), $t->decimal('opening_balance', 10, 2)->default(0), $t->decimal('current_balance', 10, 2)->default(0), $t->string('status')->default('active'), $t->timestamps()]);
        Schema::create('suppliers', fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('company_id'), $t->string('name'), $t->string('mobile')->nullable(), $t->string('status')->default('active'), $t->timestamps()]);
        Schema::create('sales_invoices', fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('company_id'), $t->unsignedBigInteger('customer_id'), $t->string('invoice_no'), $t->date('sale_date'), $t->decimal('paid_amount', 20, 2), $t->decimal('due_amount', 20, 2), $t->integer('status')->default(1), $t->timestamps()]);
        Schema::create('purchase_invoices', fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('company_id'), $t->unsignedBigInteger('supplier_id'), $t->string('invoice_no'), $t->date('purchase_date'), $t->decimal('paid_amount', 20, 2), $t->decimal('due_amount', 20, 2), $t->integer('status')->default(1), $t->timestamps()]);

        DB::table('countries')->insert(['id' => 1, 'name' => 'Nepal', 'iso_code' => 'NP']);
        DB::table('companies')->insert([
            ['id' => 1, 'company_name' => 'Company A', 'country_id' => 1],
            ['id' => 2, 'company_name' => 'Company B', 'country_id' => 1],
        ]);
        DB::table('roles')->insert([['id' => 2, 'name' => 'company_admin'], ['id' => 3, 'name' => 'company_staff']]);
        DB::table('users')->insert([
            ['id' => 1, 'name' => 'Admin A', 'email' => 'a@example.test', 'password' => Hash::make('x'), 'company_id' => 1, 'role_id' => 2],
            ['id' => 2, 'name' => 'Admin B', 'email' => 'b@example.test', 'password' => Hash::make('x'), 'company_id' => 2, 'role_id' => 2],
            ['id' => 3, 'name' => 'Staff A', 'email' => 'staff@example.test', 'password' => Hash::make('x'), 'company_id' => 1, 'role_id' => 3],
        ]);

        foreach (['module_company_profile', 'view_company_profile', 'edit_company_profile', 'module_customer', 'view_customer', 'module_sales', 'view_sales', 'module_purchase', 'view_purchase'] as $name) {
            Permission::create(['name' => $name, 'scope' => Permission::SCOPE_COMPANY]);
        }

        foreach (['module_company_profile', 'view_company_profile', 'edit_company_profile', 'module_customer', 'view_customer'] as $name) {
            DB::table('user_permissions')->insert(['user_id' => 3, 'permission_id' => Permission::where('name', $name)->value('id'), 'is_allowed' => true]);
        }

        $this->withoutMiddleware([EnsureCompanyUser::class, CheckSubscription::class, UpdateLastSeen::class, VerifyCsrfToken::class]);
    }

    public function test_company_admin_can_enable_and_disable_only_own_setting(): void
    {
        $this->actingAs(User::find(1))->put(route('company.settings.whatsapp.update'), ['is_enabled' => 1])->assertRedirect();
        $this->assertDatabaseHas('company_whatsapp_settings', ['company_id' => 1, 'is_enabled' => 1]);
        $this->assertDatabaseMissing('company_whatsapp_settings', ['company_id' => 2]);

        $this->actingAs(User::find(1))->put(route('company.settings.whatsapp.update'), ['company_id' => 2, 'is_enabled' => 0])->assertRedirect();
        $this->assertDatabaseHas('company_whatsapp_settings', ['company_id' => 1, 'is_enabled' => 0]);
        $this->assertDatabaseMissing('company_whatsapp_settings', ['company_id' => 2]);
    }

    public function test_company_staff_cannot_manage_settings_even_when_profile_permissions_are_assigned(): void
    {
        $staff = User::find(3);
        $this->actingAs($staff)->get(route('company.settings.whatsapp.edit'))->assertForbidden();
        $this->actingAs($staff)->put(route('company.settings.whatsapp.update'), ['is_enabled' => 1])->assertForbidden();
    }

    public function test_share_url_is_normalized_and_message_is_encoded_without_api_credentials(): void
    {
        $company = Company::find(1);
        $customer = $this->customer(1, '9800000001');
        CompanyWhatsappSetting::create(['company_id' => 1, 'is_enabled' => true]);

        $url = app(WhatsappShareService::class)->customerShareUrl($company, $customer, $customer->current_balance);

        $this->assertStringStartsWith('https://wa.me/9779800000001?text=', $url);
        $this->assertStringContainsString(rawurlencode("Hello Customer A,\n\nGreetings from Company A.\n\nCurrent Balance : 0.00\n\nThank you for being our valued customer.\n\nRegards,\nCompany A"), $url);
        $this->assertStringNotContainsString('DG ERP', urldecode($url));
        $this->assertStringNotContainsString('through DG ERP', urldecode($url));
        $this->assertStringNotContainsString('token', strtolower($url));
        $this->assertStringNotContainsString('sent', strtolower($url));
        $this->assertStringNotContainsString('delivered', strtolower($url));
    }

    public function test_non_zero_approved_customer_balance_uses_existing_erp_formatting(): void
    {
        $company = Company::find(1);
        $customer = $this->customer(1, '+9779800000001', '12345.5');
        CompanyWhatsappSetting::create(['company_id' => 1, 'is_enabled' => true]);

        $url = app(WhatsappShareService::class)->customerShareUrl($company, $customer, $customer->current_balance);

        $this->assertStringContainsString(rawurlencode('Current Balance : 12,345.50'), $url);
    }

    public function test_international_numbers_are_supported_and_ambiguous_numbers_fail_safely(): void
    {
        $service = app(WhatsappShareService::class);
        $this->assertSame('971501234567', $service->normalizeRecipient('+971 50 123 4567'));
        $this->assertSame('971501234567', $service->normalizeRecipient('00971-50-123-4567'));

        $this->expectException(ValidationException::class);
        $service->normalizeRecipient('501234567', 'AE');
    }

    public function test_disabled_setting_blocks_share_and_does_not_claim_delivery(): void
    {
        CompanyWhatsappSetting::create(['company_id' => 1, 'is_enabled' => false]);

        try {
            $customer = $this->customer(1, '+9779800000001');
            app(WhatsappShareService::class)->customerShareUrl(Company::find(1), $customer, $customer->current_balance);
            $this->fail('Disabled WhatsApp Share did not fail.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('disabled', $exception->getMessage());
            $this->assertDatabaseCount('company_whatsapp_settings', 1);
        }
    }

    public function test_share_action_cannot_use_another_company_customer(): void
    {
        CompanyWhatsappSetting::create(['company_id' => 1, 'is_enabled' => true]);
        $foreign = $this->customer(2, '+9779800000002');

        $this->actingAs(User::find(1))
            ->get(route('company.customers.whatsapp-share', $foreign->id))
            ->assertNotFound();
    }

    public function test_each_company_message_uses_only_its_authenticated_company_name_and_data(): void
    {
        $companyB = Company::find(2);
        $customerB = $this->customer(2, '+9779800000002', '250.00');
        CompanyWhatsappSetting::create(['company_id' => 2, 'is_enabled' => true]);

        $messageUrl = app(WhatsappShareService::class)->customerShareUrl(
            $companyB,
            $customerB,
            $customerB->current_balance
        );
        $decodedUrl = urldecode($messageUrl);

        $this->assertStringContainsString('Greetings from Company B.', $decodedUrl);
        $this->assertStringContainsString("Regards,\nCompany B", $decodedUrl);
        $this->assertStringContainsString('Current Balance : 250.00', $decodedUrl);
        $this->assertStringNotContainsString('Company A', $decodedUrl);
        $this->assertStringNotContainsString('DG ERP', $decodedUrl);
    }

    public function test_sales_invoice_share_uses_persisted_approved_fields_and_customer_recipient(): void
    {
        CompanyWhatsappSetting::create(['company_id' => 1, 'is_enabled' => true]);
        $customer = $this->customer(1, '+9779800000001');
        $invoice = $this->salesInvoice(1, $customer->id, 'SI-1001', '2026-08-20', '125.50', '74.50');

        $response = $this->actingAs(User::find(1))->get(route('company.sales.whatsapp-share', $invoice->id));
        $response->assertRedirect();
        $decodedUrl = urldecode((string) $response->headers->get('Location'));

        $this->assertStringStartsWith('https://wa.me/9779800000001?text=', $decodedUrl);
        $this->assertStringContainsString('Greetings from Company A.', $decodedUrl);
        $this->assertStringContainsString('Invoice No : SI-1001', $decodedUrl);
        $this->assertStringContainsString('Invoice Date : 20-08-2026', $decodedUrl);
        $this->assertStringContainsString('Paid Amount : 125.50', $decodedUrl);
        $this->assertStringContainsString('Due Amount : 74.50', $decodedUrl);
        $this->assertStringNotContainsString('DG ERP', $decodedUrl);
    }

    public function test_purchase_share_uses_supplier_and_persisted_approved_fields(): void
    {
        CompanyWhatsappSetting::create(['company_id' => 1, 'is_enabled' => true]);
        $supplier = Supplier::create(['company_id' => 1, 'name' => 'Supplier A', 'mobile' => '+9779811111111', 'status' => 'active']);
        $invoice = $this->purchaseInvoice(1, $supplier->id, 'PI-2001', '2026-08-21', '500', '250.75');

        $response = $this->actingAs(User::find(1))->get(route('company.purchases.whatsapp-share', $invoice->id));
        $response->assertRedirect();
        $decodedUrl = urldecode((string) $response->headers->get('Location'));

        $this->assertStringStartsWith('https://wa.me/9779811111111?text=', $decodedUrl);
        $this->assertStringContainsString('Hello Supplier A,', $decodedUrl);
        $this->assertStringContainsString('Purchase No : PI-2001', $decodedUrl);
        $this->assertStringContainsString('Purchase Date : 21-08-2026', $decodedUrl);
        $this->assertStringContainsString('Paid Amount : 500.00', $decodedUrl);
        $this->assertStringContainsString('Due Amount : 250.75', $decodedUrl);
        $this->assertStringNotContainsString('DG ERP', $decodedUrl);
    }

    public function test_sales_and_purchase_backend_share_actions_are_blocked_when_disabled(): void
    {
        CompanyWhatsappSetting::create(['company_id' => 1, 'is_enabled' => false]);
        $customer = $this->customer(1, '+9779800000001');
        $supplier = Supplier::create(['company_id' => 1, 'name' => 'Supplier A', 'mobile' => '+9779811111111', 'status' => 'active']);

        $this->actingAs(User::find(1))
            ->get(route('company.sales.whatsapp-share', $this->salesInvoice(1, $customer->id, 'SI-1', '2026-08-20', '0', '10')->id))
            ->assertSessionHasErrors('whatsapp');
        $this->actingAs(User::find(1))
            ->get(route('company.purchases.whatsapp-share', $this->purchaseInvoice(1, $supplier->id, 'PI-1', '2026-08-20', '0', '10')->id))
            ->assertSessionHasErrors('whatsapp');
    }

    public function test_sales_and_purchase_share_routes_block_cross_company_documents(): void
    {
        CompanyWhatsappSetting::create(['company_id' => 1, 'is_enabled' => true]);
        $foreignCustomer = $this->customer(2, '+9779800000002');
        $foreignSupplier = Supplier::create(['company_id' => 2, 'name' => 'Supplier B', 'mobile' => '+9779811111112', 'status' => 'active']);

        $this->actingAs(User::find(1))->get(route('company.sales.whatsapp-share', $this->salesInvoice(2, $foreignCustomer->id, 'SI-B', '2026-08-20', '0', '10')->id))->assertNotFound();
        $this->actingAs(User::find(1))->get(route('company.purchases.whatsapp-share', $this->purchaseInvoice(2, $foreignSupplier->id, 'PI-B', '2026-08-20', '0', '10')->id))->assertNotFound();
    }

    public function test_invoice_buttons_are_conditioned_on_the_company_whatsapp_setting(): void
    {
        foreach (['resources/views/company/sales/show.blade.php', 'resources/views/company/purchases/show.blade.php'] as $view) {
            $source = file_get_contents(base_path($view));
            $this->assertStringContainsString('@if($whatsappShareEnabled ?? false)', $source);
            $this->assertStringContainsString('Open WhatsApp', $source);
        }
    }

    private function customer(int $companyId, string $mobile, string $currentBalance = '0'): Customer
    {
        return Customer::create([
            'company_id' => $companyId,
            'name' => $companyId === 1 ? 'Customer A' : 'Customer B',
            'mobile' => $mobile,
            'opening_balance' => 0,
            'current_balance' => $currentBalance,
            'status' => 'active',
        ]);
    }

    private function salesInvoice(int $companyId, int $customerId, string $number, string $date, string $paid, string $due): SalesInvoice
    {
        return SalesInvoice::create(['company_id' => $companyId, 'customer_id' => $customerId, 'invoice_no' => $number, 'sale_date' => $date, 'paid_amount' => $paid, 'due_amount' => $due, 'status' => 1]);
    }

    private function purchaseInvoice(int $companyId, int $supplierId, string $number, string $date, string $paid, string $due): PurchaseInvoice
    {
        return PurchaseInvoice::create(['company_id' => $companyId, 'supplier_id' => $supplierId, 'invoice_no' => $number, 'purchase_date' => $date, 'paid_amount' => $paid, 'due_amount' => $due, 'status' => 1]);
    }
}
