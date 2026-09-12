<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckSubscription;
use App\Http\Middleware\EnsureCompanyUser;
use App\Http\Middleware\UpdateLastSeen;
use App\Models\Company;
use App\Models\CompanyCbmsApiConfiguration;
use App\Models\CompanyIrdCbmsSetting;
use App\Models\CompanyTaxSetting;
use App\Models\Country;
use App\Models\Customer;
use App\Models\FinancialYear;
use App\Models\Permission;
use App\Models\SalesInvoice;
use App\Models\User;
use App\Services\Cbms\CbmsSalesBillPayloadBuilder;
use App\Services\CompanyTaxIdentityService;
use App\Services\SalesFiscalSnapshotService;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class NepalTaxIdentityFoundationTest extends TestCase
{
    use RefreshDatabase;

    private Company $companyA;
    private Company $companyB;
    private Company $foreignCompany;
    private User $adminA;
    private User $staffA;
    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $np = Country::create(['name' => 'Nepal', 'iso_code' => 'NP', 'is_active' => true]);
        $ae = Country::create(['name' => 'United Arab Emirates', 'iso_code' => 'AE', 'is_active' => true]);
        $this->companyA = $this->company('Nepal A', 'npa', $np->id);
        $this->companyB = $this->company('Nepal B', 'npb', $np->id);
        $this->foreignCompany = $this->company('Foreign', 'foreign', $ae->id);

        DB::table('roles')->insert([
            ['id' => 1, 'name' => 'super_admin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'company_admin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'company_staff', 'created_at' => now(), 'updated_at' => now()],
        ]);
        foreach (['module_company_profile', 'view_company_profile', 'edit_company_profile', 'module_customer', 'create_customer', 'edit_customer'] as $name) {
            Permission::create(['name' => $name, 'scope' => Permission::SCOPE_COMPANY]);
        }
        $this->adminA = $this->user('Admin A', 'admin-a@example.test', 2, $this->companyA->id);
        $this->staffA = $this->user('Staff A', 'staff-a@example.test', 3, $this->companyA->id);
        $this->superAdmin = $this->user('Platform Admin', 'platform-tax@example.test', 1, null);
        foreach (['module_company_profile', 'view_company_profile', 'edit_company_profile'] as $name) {
            DB::table('user_permissions')->insert([
                'user_id' => $this->staffA->id,
                'permission_id' => Permission::where('name', $name)->value('id'),
                'is_allowed' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        $this->withoutMiddleware([EnsureCompanyUser::class, CheckSubscription::class, UpdateLastSeen::class, VerifyCsrfToken::class]);
    }

    public function test_effective_modes_use_persisted_server_side_identity(): void
    {
        $service = app(CompanyTaxIdentityService::class);
        DB::table('companies')->where('id', $this->companyA->id)->update(['pan_number' => '123456789']);
        $this->assertSame('nepal_pan', $service->resolve($this->companyA->fresh())['effective_tax_mode']);

        DB::table('companies')->where('id', $this->companyA->id)->update(['vat_number' => '987654321']);
        CompanyTaxSetting::create(['company_id' => $this->companyA->id, 'is_vat_registered' => true, 'updated_by' => $this->adminA->id]);
        $this->assertSame('nepal_vat', $service->resolve($this->companyA->fresh())['effective_tax_mode']);

        CompanyIrdCbmsSetting::create(['company_id' => $this->companyA->id, 'is_enabled' => true, 'updated_by' => $this->adminA->id]);
        $identity = $service->resolve($this->companyA->fresh());
        $this->assertSame('nepal_cbms', $identity['effective_tax_mode']);
        $this->assertSame('123456789', $identity['seller_pan']);
        $this->assertSame('987654321', $identity['seller_vat_number']);
        $this->assertSame('non_nepal', $service->resolve($this->foreignCompany)['effective_tax_mode']);
    }

    public function test_admin_updates_normalized_pan_and_mirrored_vat_without_cross_company_effect(): void
    {
        $this->actingAs($this->adminA)->put(route('company.settings.ird-cbms.tax-identity.update'), [
            'company_id' => $this->companyB->id,
            'pan_number' => ' 123-456-789 ',
            'is_vat_registered' => 1,
            'vat_number' => ' 987 654 321 ',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $company = $this->companyA->fresh();
        $this->assertSame('123456789', $company->pan_number);
        $this->assertSame('123456789', $company->vat_number);
        $this->assertDatabaseHas('company_tax_settings', ['company_id' => $company->id, 'is_vat_registered' => 1, 'updated_by' => $this->adminA->id]);
        $this->assertNull($this->companyB->fresh()->pan_number);
        $this->assertDatabaseMissing('company_tax_settings', ['company_id' => $this->companyB->id]);

        $this->post(route('company.profile.update'), [
            'company_name' => $company->company_name, 'email' => $company->email,
            'mobile' => $company->mobile, 'country_id' => $company->country_id,
            'language' => $company->language,
            'pan_number' => 'BYPASS', 'vat_number' => 'BYPASS',
        ])->assertRedirect();
        $this->assertSame('123456789', $this->companyA->fresh()->pan_number);
        $this->assertSame('123456789', $this->companyA->fresh()->vat_number);
    }

    public function test_nepal_vat_off_clears_vat_number(): void
    {
        $this->actingAs($this->adminA)->put(route('company.settings.ird-cbms.tax-identity.update'), [
            'pan_number' => '12345678',
            'is_vat_registered' => 0,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $company = $this->companyA->fresh();
        $this->assertSame('12345678', $company->pan_number);
        $this->assertNull($company->vat_number);
        $this->assertDatabaseHas('company_tax_settings', ['company_id' => $company->id, 'is_vat_registered' => 0]);
    }

    public function test_nepal_vat_on_mirrors_pan_without_requiring_submitted_vat_number(): void
    {
        $this->actingAs($this->adminA)->put(route('company.settings.ird-cbms.tax-identity.update'), [
            'pan_number' => '12345678',
            'is_vat_registered' => 1,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $company = $this->companyA->fresh();
        $this->assertSame('12345678', $company->pan_number);
        $this->assertSame('12345678', $company->vat_number);
    }

    public function test_malicious_different_vat_submission_is_ignored(): void
    {
        $this->actingAs($this->adminA)->put(route('company.settings.ird-cbms.tax-identity.update'), [
            'pan_number' => '12345678',
            'is_vat_registered' => 1,
            'vat_number' => '87654321',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $company = $this->companyA->fresh();
        $this->assertSame('12345678', $company->pan_number);
        $this->assertSame('12345678', $company->vat_number);
    }

    public function test_vat_on_to_off_and_back_on_follows_mirror_policy(): void
    {
        $this->actingAs($this->adminA)->put(route('company.settings.ird-cbms.tax-identity.update'), [
            'pan_number' => '12345678',
            'is_vat_registered' => 1,
        ])->assertSessionHasNoErrors();

        $this->put(route('company.settings.ird-cbms.tax-identity.update'), [
            'pan_number' => '12345678',
            'is_vat_registered' => 0,
        ])->assertSessionHasNoErrors();
        $this->assertNull($this->companyA->fresh()->vat_number);

        $this->put(route('company.settings.ird-cbms.tax-identity.update'), [
            'pan_number' => '12345678',
            'is_vat_registered' => 1,
        ])->assertSessionHasNoErrors();
        $this->assertSame('12345678', $this->companyA->fresh()->vat_number);
    }

    public function test_missing_or_malformed_nepal_seller_identity_is_rejected(): void
    {
        $this->actingAs($this->adminA)->put(route('company.settings.ird-cbms.tax-identity.update'), [
            'pan_number' => '', 'is_vat_registered' => 0,
        ])->assertSessionHasErrors('pan_number');
        $this->put(route('company.settings.ird-cbms.tax-identity.update'), [
            'pan_number' => '12@', 'is_vat_registered' => 0,
        ])->assertSessionHasErrors('pan_number');
        $this->put(route('company.settings.ird-cbms.tax-identity.update'), [
            'pan_number' => '123456789', 'is_vat_registered' => 1,
        ])->assertSessionHasNoErrors();
        $this->assertSame('123456789', $this->companyA->fresh()->pan_number);
        $this->assertSame('123456789', $this->companyA->fresh()->vat_number);
    }

    public function test_cbms_enable_requires_valid_pan_and_vat_registration(): void
    {
        $this->actingAs($this->superAdmin)->put(route('admin.company.ird-cbms.update', $this->companyA), ['is_enabled' => 1])
            ->assertSessionHasErrors('is_enabled');
        $this->assertDatabaseMissing('company_ird_cbms_settings', ['company_id' => $this->companyA->id]);

        $this->actingAs($this->adminA)->put(route('company.settings.ird-cbms.tax-identity.update'), [
            'pan_number' => '123456789', 'is_vat_registered' => 1,
        ])->assertSessionHasNoErrors();
        $this->actingAs($this->superAdmin)->put(route('admin.company.ird-cbms.update', $this->companyA), ['is_enabled' => 1])->assertSessionHasNoErrors();
        $this->assertSame('nepal_cbms', app(CompanyTaxIdentityService::class)->resolve($this->companyA->fresh())['effective_tax_mode']);
    }

    public function test_fiscal_snapshot_captures_mirrored_pan_and_vat_when_vat_registered(): void
    {
        CompanyIrdCbmsSetting::create(['company_id' => $this->companyA->id, 'is_enabled' => true, 'updated_by' => $this->adminA->id]);
        $this->actingAs($this->adminA)->put(route('company.settings.ird-cbms.tax-identity.update'), [
            'pan_number' => '12345678',
            'is_vat_registered' => 1,
        ])->assertSessionHasNoErrors();

        $company = $this->companyA->fresh();
        $customer = Customer::create(['company_id' => $company->id, 'name' => 'Buyer', 'status' => 'active']);
        $attrs = app(SalesFiscalSnapshotService::class)->invoiceAttributes($company, $customer);

        $this->assertSame('12345678', $attrs['seller_pan_snapshot']);
        $this->assertSame('12345678', $attrs['seller_vat_snapshot']);
    }

    public function test_cbms_payload_uses_seller_pan_only_not_vat_number(): void
    {
        CompanyIrdCbmsSetting::create(['company_id' => $this->companyA->id, 'is_enabled' => true, 'updated_by' => $this->adminA->id]);
        CompanyCbmsApiConfiguration::create([
            'company_id' => $this->companyA->id,
            'environment' => 'test',
            'client_identifier' => 'taxpayer-user',
            'encrypted_credential' => 'phase-one-test-password',
            'configured_by' => $this->adminA->id,
            'updated_by' => $this->adminA->id,
        ]);
        $this->actingAs($this->adminA)->put(route('company.settings.ird-cbms.tax-identity.update'), [
            'pan_number' => '123456789',
            'is_vat_registered' => 1,
        ])->assertSessionHasNoErrors();

        $fy = FinancialYear::create([
            'company_id' => $this->companyA->id,
            'name' => '2081/82',
            'start_date' => '2024-04-13',
            'end_date' => '2025-04-14',
            'is_active' => true,
        ]);
        $customerId = DB::table('customers')->insertGetId([
            'company_id' => $this->companyA->id,
            'name' => 'Buyer',
            'tax_no' => '111111111',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $invoiceId = DB::table('sales_invoices')->insertGetId([
            'created_by' => $this->adminA->id,
            'company_id' => $this->companyA->id,
            'financial_year_id' => $fy->id,
            'customer_id' => $customerId,
            'invoice_no' => 'SI-MIRROR',
            'sale_date' => '2024-04-13',
            'subtotal' => 100,
            'discount' => 0,
            'total_vat' => 13,
            'grand_total' => 113,
            'paid_amount' => 0,
            'due_amount' => 113,
            'payment_status' => 'unpaid',
            'status' => 1,
            'fiscal_snapshot_captured_at' => now(),
            'fiscal_issued_at' => '2024-04-13 10:00:00',
            'seller_name_snapshot' => 'Nepal A',
            'seller_pan_snapshot' => '123456789',
            'seller_vat_snapshot' => '123456789',
            'buyer_name_snapshot' => 'Buyer',
            'buyer_tax_no_snapshot' => '111111111',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $invoice = SalesInvoice::findOrFail($invoiceId);
        DB::table('sales_items')->insert([
            'created_by' => $this->adminA->id,
            'company_id' => $this->companyA->id,
            'financial_year_id' => $fy->id,
            'sales_invoice_id' => $invoiceId,
            'item_type' => 'service',
            'quantity' => 1,
            'returned_qty' => 0,
            'unit_price' => 100,
            'vat_rate' => 13,
            'vat_amount' => 13,
            'fiscal_discount_amount' => 0,
            'fiscal_net_base' => 100,
            'tax_classification' => 'vat_taxable',
            'total_price' => 113,
            'item_name_snapshot' => 'Service',
            'unit_name_snapshot' => 'Service',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $result = app(CbmsSalesBillPayloadBuilder::class)->build($invoice->fresh(), $this->companyA->id, Carbon::parse('2024-04-13 10:04:59'));
        $this->assertSame('READY', $result['readiness']['status']);
        $payload = $result['payload'];
        $this->assertNotNull($payload);
        $this->assertSame('123456789', $payload['seller_pan']);
        $this->assertArrayNotHasKey('vat_number', $payload);
        $this->assertArrayNotHasKey('seller_vat_snapshot', $payload);
    }

    public function test_non_nepal_tampering_and_ordinary_staff_are_rejected(): void
    {
        $foreignAdmin = $this->user('Foreign Admin', 'foreign-admin@example.test', 2, $this->foreignCompany->id);
        $this->actingAs($foreignAdmin)->put(route('company.settings.ird-cbms.tax-identity.update'), [
            'company_id' => $this->companyA->id, 'pan_number' => '123456789',
            'is_vat_registered' => 1, 'vat_number' => '987654321',
        ])->assertSessionHasErrors('pan_number');
        $this->assertDatabaseMissing('company_tax_settings', ['company_id' => $this->foreignCompany->id]);

        $this->actingAs($this->staffA)->put(route('company.settings.ird-cbms.tax-identity.update'), [
            'pan_number' => '123456789', 'is_vat_registered' => 0,
        ])->assertForbidden();
        $this->assertDatabaseMissing('company_tax_settings', ['company_id' => $this->companyA->id]);
    }

    public function test_buyer_pan_is_normalized_optional_validated_and_company_scoped(): void
    {
        $this->actingAs($this->adminA)->post(route('company.customers.store'), [
            'name' => 'Buyer One', 'tax_no' => ' 123-456-789 ', 'status' => 'active',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('customers', ['company_id' => $this->companyA->id, 'name' => 'Buyer One', 'tax_no' => '123456789']);

        $this->post(route('company.customers.store'), [
            'name' => 'Buyer Blank', 'tax_no' => '   ', 'status' => 'active',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('customers', ['company_id' => $this->companyA->id, 'name' => 'Buyer Blank', 'tax_no' => null]);

        $this->post(route('company.customers.store'), [
            'name' => 'Buyer Invalid', 'tax_no' => '12@', 'status' => 'active',
        ])->assertSessionHasErrors('tax_no');
        $this->assertDatabaseMissing('customers', ['name' => 'Buyer Invalid']);
        $this->assertDatabaseMissing('customers', ['company_id' => $this->companyB->id]);
    }

    public function test_existing_company_without_explicit_tax_setting_remains_backward_compatible(): void
    {
        $identity = app(CompanyTaxIdentityService::class)->resolve($this->companyA);
        $this->assertSame('nepal_pan', $identity['effective_tax_mode']);
        $this->assertFalse($identity['is_vat_registered']);
        $this->assertFalse($identity['is_nepal_ird_cbms_active']);
        $this->assertDatabaseMissing('company_tax_settings', ['company_id' => $this->companyA->id]);
    }

    private function company(string $name, string $key, int $countryId): Company
    {
        $company = Company::create(['company_name' => $name, 'mobile' => '98'.$key, 'email' => $key.'@company.test', 'status' => 'active']);
        DB::table('companies')->where('id', $company->id)->update(['country_id' => $countryId]);
        return $company->fresh();
    }

    private function user(string $name, string $email, int $roleId, ?int $companyId): User
    {
        return User::create(['name' => $name, 'email' => $email, 'password' => Hash::make('password'), 'role_id' => $roleId, 'company_id' => $companyId, 'account_status' => 'active']);
    }
}
