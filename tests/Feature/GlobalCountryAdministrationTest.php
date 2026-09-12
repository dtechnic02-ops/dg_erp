<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Company;
use App\Models\CompanyIrdCbmsSetting;
use App\Models\CompanyRegistration;
use App\Models\CompanyTaxSetting;
use App\Models\Country;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\PlatformAuthorizationService;
use App\Services\PlatformMailService;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class GlobalCountryAdministrationTest extends TestCase
{
    use RefreshDatabase;

    private Country $nepal;
    private Country $uae;
    private User $global;
    private User $nepalAdmin;
    private User $uaeAdmin;
    private User $uaeStaff;

    protected function setUp(): void
    {
        parent::setUp();
        foreach ([1 => 'super_admin', 2 => 'company_admin', 3 => 'staff', 4 => 'super_staff', 5 => 'country_admin'] as $id => $name) {
            DB::table('roles')->insertOrIgnore(['id' => $id, 'name' => $name, 'created_at' => now(), 'updated_at' => now()]);
        }
        $this->nepal = Country::create(['name' => 'Nepal', 'iso_code' => 'NP', 'is_active' => true]);
        $this->uae = Country::create(['name' => 'United Arab Emirates', 'iso_code' => 'AE', 'is_active' => true]);
        $this->global = $this->user('Global', 1, null);
        $this->nepalAdmin = $this->user('Nepal Admin', 5, $this->nepal->id);
        $this->uaeAdmin = $this->user('UAE Admin', 5, $this->uae->id);
        $this->uaeStaff = $this->user('UAE Staff', 4, $this->uae->id);
        foreach (['platform_module_registrations', 'platform_registrations_create', 'platform_registrations_view', 'platform_module_companies', 'platform_companies_view'] as $name) {
            $permission = Permission::firstOrCreate(['name' => $name], ['scope' => Permission::SCOPE_PLATFORM]);
            DB::table('user_permissions')->insert(['user_id' => $this->uaeStaff->id, 'permission_id' => $permission->id, 'is_allowed' => true, 'created_at' => now(), 'updated_at' => now()]);
        }
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function test_public_registration_is_disabled_without_deleting_history(): void
    {
        $historical = $this->registration('Historical', $this->nepal, $this->global);
        $this->get('/company/register')->assertNotFound();
        $this->post('/company/register', $this->payload('Guest', $this->nepal))->assertNotFound();
        $this->assertDatabaseHas('company_registrations', ['id' => $historical->id]);
        $this->get(route('login'))->assertOk()->assertDontSee('Register Company');
    }

    public function test_platform_creators_can_register_cross_country_and_cannot_forge_creator(): void
    {
        $this->actingAs($this->global)->post(route('company.register.post'), $this->payload('Global Nepal', $this->nepal) + ['registered_by_user_id' => $this->uaeStaff->id])->assertRedirect(route('admin.registrations'));
        $this->actingAs($this->nepalAdmin)->post(route('company.register.post'), $this->payload('Nepal Admin UAE', $this->uae))->assertRedirect(route('admin.registrations'));
        $this->actingAs($this->uaeStaff)->post(route('company.register.post'), $this->payload('UAE Staff Nepal', $this->nepal))->assertRedirect(route('admin.registrations'));

        $this->assertDatabaseHas('company_registrations', ['company_name' => 'Global Nepal', 'country_id' => $this->nepal->id, 'registered_by_user_id' => $this->global->id]);
        $this->assertDatabaseHas('company_registrations', ['company_name' => 'Nepal Admin UAE', 'country_id' => $this->uae->id, 'registered_by_user_id' => $this->nepalAdmin->id]);
        $this->assertDatabaseHas('company_registrations', ['company_name' => 'UAE Staff Nepal', 'country_id' => $this->nepal->id, 'registered_by_user_id' => $this->uaeStaff->id]);

        $company = Company::create(['company_name' => 'Tenant', 'mobile' => '999', 'email' => 'tenant@example.test', 'status' => 'active', 'country_id' => $this->nepal->id]);
        $tenantAdmin = $this->user('Tenant Admin', 2, null, $company->id);
        $this->actingAs($tenantAdmin)->post(route('company.register.post'), $this->payload('Blocked', $this->nepal))->assertForbidden();
    }

    public function test_country_scoped_registration_and_company_visibility_rejects_tampering(): void
    {
        $np = $this->registration('NP Visible', $this->nepal, $this->uaeStaff);
        $ae = $this->registration('AE Hidden', $this->uae, $this->uaeStaff);
        Company::create(['company_name' => 'NP Co', 'mobile' => '101', 'email' => 'np@example.test', 'status' => 'active', 'country_id' => $this->nepal->id]);
        $aeCompany = Company::create(['company_name' => 'AE Co', 'mobile' => '102', 'email' => 'ae@example.test', 'status' => 'active', 'country_id' => $this->uae->id]);

        $this->actingAs($this->nepalAdmin)->get(route('admin.registrations'))->assertOk()->assertSee('NP Visible')->assertDontSee('AE Hidden');
        $this->get(route('admin.registration.show', $ae))->assertNotFound();
        $this->get(route('admin.companies'))->assertOk()->assertSee('NP Co')->assertDontSee('AE Co');
        $this->get(route('admin.company.show', $aeCompany))->assertNotFound();
        $this->actingAs($this->global)->get(route('admin.registrations'))->assertOk()->assertSee('NP Visible')->assertSee('AE Hidden');
        $this->assertSame($this->uaeStaff->id, $np->registered_by_user_id);
    }

    public function test_approval_and_cbms_authority_are_country_scoped_and_super_staff_cannot_approve(): void
    {
        $np = $this->registration('NP Pending', $this->nepal, $this->uaeStaff);
        $ae = $this->registration('AE Pending', $this->uae, $this->uaeStaff);
        $this->actingAs($this->nepalAdmin)->post(route('admin.approve', $ae))->assertForbidden();
        $this->actingAs($this->uaeAdmin)->post(route('admin.approve', $np))->assertForbidden();
        $this->actingAs($this->uaeStaff)->post(route('admin.approve', $ae))->assertForbidden();

        $company = Company::create(['company_name' => 'Nepal CBMS', 'mobile' => '103', 'email' => 'cbms@example.test', 'status' => 'active', 'country_id' => $this->nepal->id, 'pan_number' => '123456789', 'vat_number' => '987654321']);
        CompanyTaxSetting::create(['company_id' => $company->id, 'is_vat_registered' => true, 'updated_by' => $this->global->id]);
        $this->actingAs($this->nepalAdmin)->put(route('admin.company.ird-cbms.update', $company), ['is_enabled' => 1])->assertSessionHasNoErrors();
        $this->assertTrue(CompanyIrdCbmsSetting::where('company_id', $company->id)->sole()->is_enabled);
        $this->actingAs($this->uaeAdmin)->put(route('admin.company.ird-cbms.update', $company), ['is_enabled' => 0])->assertForbidden();
        $this->actingAs($this->global)->put(route('admin.company.ird-cbms.update', $company), ['is_enabled' => 0])->assertSessionHasNoErrors();
    }

    public function test_country_admins_approve_only_own_country_and_global_admin_approves_any_country(): void
    {
        SubscriptionPlan::create(['code' => 'trial', 'name' => 'Trial', 'staff_limit' => 5, 'is_active' => true, 'sort_order' => 0]);
        $np = $this->registration('NP Approved', $this->nepal, $this->uaeStaff);
        $ae = $this->registration('AE Approved', $this->uae, $this->uaeStaff);
        $globalAe = $this->registration('Global AE Approved', $this->uae, $this->nepalAdmin);
        File::partialMock();
        File::shouldReceive('exists')->times(3)->andReturnFalse();
        File::shouldReceive('makeDirectory')->times(3);
        $this->mock(SubscriptionService::class)->shouldReceive('startRegisterTrial')->times(3);
        $this->mock(PlatformMailService::class)->shouldReceive('send')->times(3);

        $this->actingAs($this->nepalAdmin)->post(route('admin.approve', $np))->assertSessionHas('success');
        $this->actingAs($this->uaeAdmin)->post(route('admin.approve', $ae))->assertSessionHas('success');
        $this->actingAs($this->global)->post(route('admin.approve', $globalAe))->assertSessionHas('success');

        foreach ([[$np, $this->nepalAdmin], [$ae, $this->uaeAdmin], [$globalAe, $this->global]] as [$registration, $approver]) {
            $registration->refresh();
            $this->assertSame('approved', $registration->status);
            $this->assertSame($approver->id, (int) $registration->approved_by);
            $this->assertNotNull($registration->approved_at);
            $this->assertSame($registration->registered_by_user_id, $registration->fresh()->registered_by_user_id);
            $this->assertDatabaseHas('companies', ['email' => $registration->email, 'country_id' => $registration->country_id]);
        }
    }

    public function test_permanent_delete_remains_global_super_admin_only(): void
    {
        $company = Company::create(['company_name' => 'Protected', 'mobile' => '104', 'email' => 'protected@example.test', 'status' => 'active', 'country_id' => $this->nepal->id]);
        $this->actingAs($this->nepalAdmin)->get(route('admin.company.permanent-delete.show', $company))->assertForbidden();
        $this->actingAs($this->uaeStaff)->get(route('admin.company.permanent-delete.show', $company))->assertForbidden();
    }

    public function test_platform_staff_accounts_require_an_authoritative_country_assignment(): void
    {
        $this->actingAs($this->global)->post(route('admin.super-staff.store'), [
            'name' => 'New Nepal Country Admin', 'email' => 'new-country-admin@example.test',
            'password' => 'password1', 'password_confirmation' => 'password1',
            'role_id' => Role::COUNTRY_ADMIN_ID, 'country_id' => $this->nepal->id,
        ])->assertRedirect(route('admin.super-staff.index'));
        $this->assertDatabaseHas('users', ['email' => 'new-country-admin@example.test', 'role_id' => Role::COUNTRY_ADMIN_ID, 'country_id' => $this->nepal->id, 'company_id' => null]);

        $this->post(route('admin.super-staff.store'), [
            'name' => 'No Country Staff', 'email' => 'no-country@example.test',
            'password' => 'password1', 'password_confirmation' => 'password1',
            'role_id' => Role::SUPER_STAFF_ID,
        ])->assertSessionHasErrors('country_id');
        $this->assertDatabaseMissing('users', ['email' => 'no-country@example.test']);
    }

    public function test_platform_tax_identity_onboarding_is_country_scoped_and_enables_explicit_activation(): void
    {
        $nepalCompany = Company::create(['company_name'=>'Nepal Tax','mobile'=>'105','email'=>'nepal-tax@example.test','status'=>'active','country_id'=>$this->nepal->id]);
        $uaeCompany = Company::create(['company_name'=>'UAE Tax','mobile'=>'106','email'=>'uae-tax@example.test','status'=>'active','country_id'=>$this->uae->id]);

        $this->actingAs($this->nepalAdmin)->get(route('admin.company.show',$nepalCompany))->assertOk()->assertSee('Update PAN/VAT');
        $this->put(route('admin.company.tax-identity.update',$nepalCompany),['company_id'=>$uaeCompany->id,'pan_number'=>' 123-456-789 ','is_vat_registered'=>1,'vat_number'=>' 987 654 321 '])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('companies',['id'=>$nepalCompany->id,'pan_number'=>'123456789','vat_number'=>'123456789']);
        $this->assertDatabaseHas('company_tax_settings',['company_id'=>$nepalCompany->id,'is_vat_registered'=>1,'updated_by'=>$this->nepalAdmin->id]);
        $this->assertDatabaseMissing('company_tax_settings',['company_id'=>$uaeCompany->id]);
        $this->put(route('admin.company.ird-cbms.update',$nepalCompany),['is_enabled'=>1])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('company_ird_cbms_settings',['company_id'=>$nepalCompany->id,'is_enabled'=>1]);

        $this->actingAs($this->uaeAdmin)->put(route('admin.company.tax-identity.update',$nepalCompany),['pan_number'=>'111111111','is_vat_registered'=>1,'vat_number'=>'222222222'])->assertForbidden();
        $this->actingAs($this->nepalAdmin)->put(route('admin.company.tax-identity.update',$uaeCompany),['pan_number'=>'111111111','is_vat_registered'=>1,'vat_number'=>'222222222'])->assertForbidden();

        $globalCompany = Company::create(['company_name'=>'Global Nepal Tax','mobile'=>'107','email'=>'global-tax@example.test','status'=>'active','country_id'=>$this->nepal->id]);
        $this->actingAs($this->global)->put(route('admin.company.tax-identity.update',$globalCompany),['pan_number'=>'123456789','is_vat_registered'=>1,'vat_number'=>'987654321'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('companies',['id'=>$globalCompany->id,'pan_number'=>'123456789','vat_number'=>'123456789']);
        $this->put(route('admin.company.ird-cbms.update',$globalCompany),['is_enabled'=>1])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('company_ird_cbms_settings',['company_id'=>$globalCompany->id,'is_enabled'=>1]);
    }

    private function user(string $name, int $roleId, ?int $countryId, ?int $companyId = null): User
    {
        return User::create(['name' => $name, 'email' => str($name)->slug().uniqid().'@example.test', 'password' => Hash::make('password'), 'role_id' => $roleId, 'company_id' => $companyId, 'country_id' => $countryId, 'account_status' => 'active']);
    }

    private function payload(string $name, Country $country): array
    {
        $key = str($name)->slug().uniqid();
        return ['company_name' => $name, 'full_name' => $name.' Owner', 'email' => $key.'@example.test', 'mobile_no' => uniqid(), 'username' => $key, 'password' => 'password1', 'country_id' => $country->id];
    }

    private function registration(string $name, Country $country, User $creator): CompanyRegistration
    {
        return CompanyRegistration::create($this->payload($name, $country) + ['password' => Hash::make('password1'), 'registered_by_user_id' => $creator->id, 'status' => 'pending']);
    }
}
