<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckSubscription;
use App\Http\Middleware\EnsureCompanyUser;
use App\Http\Middleware\UpdateLastSeen;
use App\Models\Company;
use App\Models\CompanyIrdCbmsSetting;
use App\Models\CompanyTaxSetting;
use App\Models\Country;
use App\Models\Permission;
use App\Models\SalesInvoice;
use App\Models\User;
use App\Services\NepalIrdCbmsModeService;
use App\Services\NepaliDateService;
use App\Services\PlatformAuthorizationService;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class NepalIrdCbmsModeFoundationTest extends TestCase
{
    use RefreshDatabase;

    private Company $nepalCompany;
    private Company $otherNepalCompany;
    private Company $nonNepalCompany;
    private User $admin;
    private User $staff;
    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $nepal = Country::create(['name' => 'Nepal', 'iso_code' => 'NP', 'is_active' => true]);
        $uae = Country::create(['name' => 'United Arab Emirates', 'iso_code' => 'AE', 'is_active' => true]);

        $this->nepalCompany = $this->company('Nepal Company A', $nepal->id, 'a');
        $this->otherNepalCompany = $this->company('Nepal Company B', $nepal->id, 'b');
        $this->nonNepalCompany = $this->company('UAE Company', $uae->id, 'c');

        DB::table('roles')->insert([
            ['id' => 1, 'name' => 'super_admin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'company_admin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'company_staff', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'super_staff', 'created_at' => now(), 'updated_at' => now()],
        ]);
        foreach (['module_company_profile', 'view_company_profile', 'edit_company_profile'] as $name) {
            Permission::create(['name' => $name, 'scope' => Permission::SCOPE_COMPANY]);
        }

        $this->admin = $this->user('Admin', 'admin@example.test', 2, $this->nepalCompany->id);
        $this->staff = $this->user('Staff', 'staff@example.test', 3, $this->nepalCompany->id);
        $this->superAdmin = $this->user('Platform Admin', 'platform@example.test', 1, null);
        foreach (['module_company_profile', 'view_company_profile', 'edit_company_profile'] as $name) {
            DB::table('user_permissions')->insert([
                'user_id' => $this->staff->id,
                'permission_id' => Permission::where('name', $name)->value('id'),
                'is_allowed' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->withoutMiddleware([
            EnsureCompanyUser::class,
            CheckSubscription::class,
            UpdateLastSeen::class,
            VerifyCsrfToken::class,
        ]);
    }

    public function test_effective_mode_is_country_gated_and_defaults_off_without_a_setting(): void
    {
        $mode = app(NepalIrdCbmsModeService::class);

        $this->assertFalse($mode->isActiveForCompany($this->nepalCompany));
        $this->assertFalse($mode->isActiveForCompany($this->nonNepalCompany));

        CompanyIrdCbmsSetting::create([
            'company_id' => $this->nepalCompany->id,
            'is_enabled' => true,
            'updated_by' => $this->admin->id,
        ]);
        CompanyIrdCbmsSetting::create([
            'company_id' => $this->nonNepalCompany->id,
            'is_enabled' => true,
            'updated_by' => $this->admin->id,
        ]);

        $this->assertTrue($mode->isActiveForCompany($this->nepalCompany->fresh()));
        $this->assertFalse($mode->isActiveForCompany($this->nonNepalCompany->fresh()));
        $this->assertFalse($mode->isActiveForCompany($this->otherNepalCompany));
    }

    public function test_platform_admin_can_enable_and_disable_an_eligible_nepal_company(): void
    {
        DB::table('companies')->where('id', $this->nepalCompany->id)->update([
            'pan_number' => '123456789', 'vat_number' => '987654321',
        ]);
        CompanyTaxSetting::create([
            'company_id' => $this->nepalCompany->id,
            'is_vat_registered' => true,
            'updated_by' => $this->admin->id,
        ]);
        $this->actingAs($this->superAdmin)->get(route('admin.company.show', $this->nepalCompany))
            ->assertOk()->assertSee('Platform-controlled status')->assertSee('name="is_enabled"', false);
        $this->actingAs($this->superAdmin)->put(route('admin.company.ird-cbms.update', $this->nepalCompany), [
            'is_enabled' => 1,
            'company_id' => $this->otherNepalCompany->id,
            'country_id' => $this->nonNepalCompany->country_id,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('company_ird_cbms_settings', [
            'company_id' => $this->nepalCompany->id,
            'is_enabled' => 1,
            'updated_by' => $this->superAdmin->id,
        ]);
        $this->assertDatabaseMissing('company_ird_cbms_settings', ['company_id' => $this->otherNepalCompany->id]);
        $this->assertTrue(app(NepalIrdCbmsModeService::class)->isActiveForCompany($this->nepalCompany->fresh()));

        $this->put(route('admin.company.ird-cbms.update', $this->nepalCompany), ['is_enabled' => 0])
            ->assertRedirect()->assertSessionHasNoErrors();
        $setting = CompanyIrdCbmsSetting::where('company_id', $this->nepalCompany->id)->sole();
        $this->assertFalse($setting->is_enabled);
        $this->assertSame($this->superAdmin->id, (int) $setting->updated_by);
        $this->assertNotNull($setting->updated_at);
    }

    public function test_cbms_disable_is_permanently_locked_only_after_successful_fiscal_issuance(): void
    {
        DB::table('companies')->where('id', $this->nepalCompany->id)->update([
            'pan_number' => '123456789', 'vat_number' => '987654321',
        ]);
        CompanyTaxSetting::create([
            'company_id' => $this->nepalCompany->id,
            'is_vat_registered' => true,
            'updated_by' => $this->admin->id,
        ]);
        $this->actingAs($this->superAdmin)
            ->put(route('admin.company.ird-cbms.update', $this->nepalCompany), ['is_enabled' => 1])
            ->assertSessionHasNoErrors();

        $legacy = $this->salesInvoice($this->nepalCompany, $this->admin, 'SI-LEGACY');
        $this->put(route('admin.company.ird-cbms.update', $this->nepalCompany), ['is_enabled' => 0])
            ->assertSessionHasNoErrors();
        $this->assertFalse(CompanyIrdCbmsSetting::where('company_id', $this->nepalCompany->id)->sole()->is_enabled);

        $this->put(route('admin.company.ird-cbms.update', $this->nepalCompany), ['is_enabled' => 1])
            ->assertSessionHasNoErrors();
        $legacy->forceFill(['fiscal_issued_at' => now()])->save();

        $this->from(route('admin.company.show', $this->nepalCompany))
            ->put(route('admin.company.ird-cbms.update', $this->nepalCompany), ['is_enabled' => 0])
            ->assertRedirect(route('admin.company.show', $this->nepalCompany))
            ->assertSessionHasErrors('is_enabled');
        $this->assertTrue(CompanyIrdCbmsSetting::where('company_id', $this->nepalCompany->id)->sole()->is_enabled);
        $this->get(route('admin.company.show', $this->nepalCompany))
            ->assertOk()->assertSee('CBMS is permanently active because fiscal invoices have already been issued.');
    }

    public function test_non_nepal_and_failed_or_rolled_back_issuance_evidence_do_not_lock_cbms(): void
    {
        CompanyIrdCbmsSetting::create(['company_id' => $this->nonNepalCompany->id, 'is_enabled' => true, 'updated_by' => $this->superAdmin->id]);
        $this->salesInvoice($this->nonNepalCompany, $this->user('Foreign Admin', 'foreign-mode@example.test', 2, $this->nonNepalCompany->id), 'SI-FOREIGN');
        $this->assertFalse(app(\App\Services\FiscalDocumentPolicyService::class)->companyHasFiscallyIssuedSalesInvoice($this->nonNepalCompany));

        CompanyIrdCbmsSetting::create(['company_id' => $this->nepalCompany->id, 'is_enabled' => true, 'updated_by' => $this->superAdmin->id]);
        try {
            DB::transaction(function () {
                $rolledBack = $this->salesInvoice($this->nepalCompany, $this->admin, 'SI-ROLLBACK');
                $rolledBack->forceFill(['fiscal_issued_at' => now()])->save();
                throw new \RuntimeException('Force rollback');
            });
        } catch (\RuntimeException $exception) {
            $this->assertSame('Force rollback', $exception->getMessage());
        }
        $this->assertFalse(app(\App\Services\FiscalDocumentPolicyService::class)->companyHasFiscallyIssuedSalesInvoice($this->nepalCompany));
        $this->actingAs($this->superAdmin)
            ->put(route('admin.company.ird-cbms.update', $this->nepalCompany), ['is_enabled' => 0])
            ->assertSessionHasNoErrors();
    }

    public function test_non_nepal_tampered_enable_request_is_rejected_and_remains_off(): void
    {
        $this->actingAs($this->superAdmin)->from(route('admin.company.show', $this->nonNepalCompany))
            ->put(route('admin.company.ird-cbms.update', $this->nonNepalCompany), [
                'is_enabled' => 1,
                'country_id' => $this->nepalCompany->country_id,
                'company_id' => $this->nepalCompany->id,
            ])
            ->assertRedirect(route('admin.company.show', $this->nonNepalCompany))
            ->assertSessionHasErrors('is_enabled');

        $this->assertDatabaseMissing('company_ird_cbms_settings', ['company_id' => $this->nonNepalCompany->id]);
        $this->assertFalse(app(NepalIrdCbmsModeService::class)->isActiveForCompany($this->nonNepalCompany->fresh()));
        $this->get(route('admin.company.show', $this->nonNepalCompany))
            ->assertOk()->assertDontSee('name="is_enabled"', false);
    }

    public function test_company_admin_and_staff_cannot_change_platform_cbms_status(): void
    {
        CompanyIrdCbmsSetting::create(['company_id' => $this->nepalCompany->id, 'is_enabled' => true, 'updated_by' => $this->superAdmin->id]);

        $this->actingAs($this->admin)->put(route('company.settings.ird-cbms.update'), ['is_enabled' => 0])->assertForbidden();
        $this->put(route('admin.company.ird-cbms.update', $this->nepalCompany), ['is_enabled' => 0])->assertForbidden();
        $this->actingAs($this->staff)->put(route('company.settings.ird-cbms.update'), ['is_enabled' => 0])->assertForbidden();
        $this->assertDatabaseHas('company_ird_cbms_settings', ['company_id' => $this->nepalCompany->id, 'is_enabled' => 1]);
    }

    public function test_compliance_capability_is_currently_super_admin_only_and_company_aware(): void
    {
        $authorization = app(PlatformAuthorizationService::class);
        $superStaff = $this->user('Platform Staff', 'platform-staff@example.test', 4, null);

        $this->assertTrue($authorization->can($this->superAdmin, PlatformAuthorizationService::COMPLIANCE_MANAGE));
        $this->assertTrue($authorization->canManageCompanyCompliance($this->superAdmin, $this->nepalCompany));
        $this->assertFalse($authorization->can($superStaff, PlatformAuthorizationService::COMPLIANCE_MANAGE));
        $this->assertFalse($authorization->canManageCompanyCompliance($superStaff, $this->nepalCompany));
        $this->actingAs($superStaff)
            ->put(route('admin.company.ird-cbms.update', $this->nepalCompany), ['is_enabled' => 1])
            ->assertForbidden();
        $this->assertContains(
            'platform.permission:'.PlatformAuthorizationService::COMPLIANCE_MANAGE,
            app('router')->getRoutes()->getByName('admin.company.ird-cbms.update')->gatherMiddleware()
        );
    }

    public function test_ui_uses_server_country_and_nepal_date_behavior_is_independent_of_toggle(): void
    {
        $this->actingAs($this->admin)->get(route('company.settings.ird-cbms.edit'))
            ->assertOk()
            ->assertSee('Nepal Tax Settings')
            ->assertDontSee('name="is_enabled"', false)
            ->assertDontSee('>IRD / CBMS</a>', false);

        $foreignAdmin = $this->user('Foreign UI Admin', 'foreign-ui@example.test', 2, $this->nonNepalCompany->id);
        $this->actingAs($foreignAdmin)->get(route('company.settings.ird-cbms.edit'))
            ->assertNotFound();

        $dateService = app(NepaliDateService::class);
        $before = $dateService->adToBsForCompany($this->nepalCompany->fresh(), '2026-09-05');
        CompanyIrdCbmsSetting::create([
            'company_id' => $this->nepalCompany->id,
            'is_enabled' => true,
            'updated_by' => $this->admin->id,
        ]);
        $after = $dateService->adToBsForCompany($this->nepalCompany->fresh(), '2026-09-05');

        $this->actingAs($this->admin)->get(route('company.settings.ird-cbms.edit'))
            ->assertOk()->assertSee('Nepal IRD/CBMS status')->assertSee('managed by platform administration')
            ->assertDontSee('name="is_enabled"', false)->assertSee('>IRD / CBMS</a>', false);

        $this->assertNotNull($before);
        $this->assertSame($before, $after);
    }

    public function test_nepal_cbms_off_preserves_normal_company_profile_behavior(): void
    {
        $this->actingAs($this->admin)->get(route('company.profile'))
            ->assertOk()
            ->assertSee($this->nepalCompany->company_name);
        $this->assertFalse(app(NepalIrdCbmsModeService::class)->isActiveForCompany($this->nepalCompany));
    }

    private function company(string $name, int $countryId, string $suffix): Company
    {
        $company = Company::create([
            'company_name' => $name,
            'mobile' => '98000000'.$suffix,
            'email' => $suffix.'@company.test',
            'status' => 'active',
        ]);
        DB::table('companies')->where('id', $company->id)->update(['country_id' => $countryId]);
        return $company->fresh();
    }

    private function user(string $name, string $email, int $roleId, ?int $companyId): User
    {
        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make('password'),
            'role_id' => $roleId,
            'company_id' => $companyId,
            'account_status' => 'active',
        ]);
    }

    private function salesInvoice(Company $company, User $creator, string $number): SalesInvoice
    {
        $fy = DB::table('financial_years')->insertGetId(['company_id' => $company->id, 'name' => $number, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => 1, 'is_closed' => 0, 'is_locked' => 0, 'created_by' => $creator->id, 'created_at' => now(), 'updated_at' => now()]);
        $customer = DB::table('customers')->insertGetId(['company_id' => $company->id, 'created_by' => $creator->id, 'name' => $number, 'opening_balance' => 0, 'current_balance' => 0, 'credit_days' => 0, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);

        return SalesInvoice::create(['created_by' => $creator->id, 'company_id' => $company->id, 'financial_year_id' => $fy, 'customer_id' => $customer, 'invoice_no' => $number, 'sale_date' => '2026-06-15', 'subtotal' => 100, 'grand_total' => 100, 'paid_amount' => 0, 'due_amount' => 100, 'payment_status' => 'unpaid', 'status' => 1]);
    }
}
