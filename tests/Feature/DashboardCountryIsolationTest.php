<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanyRegistration;
use App\Models\Country;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DashboardCountryIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Country $nepal;
    private Country $uae;
    private User $global;
    private User $nepalAdmin;
    private User $uaeAdmin;
    private User $uaeStaff;
    private Company $nepalCompany;
    private Company $uaeCompany;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([1 => 'super_admin', 2 => 'company_admin', 3 => 'staff', 4 => 'super_staff', 5 => 'country_admin'] as $id => $name) {
            DB::table('roles')->insertOrIgnore(['id' => $id, 'name' => $name, 'created_at' => now(), 'updated_at' => now()]);
        }

        $this->nepal = Country::create(['name' => 'Nepal', 'iso_code' => 'NP', 'is_active' => true]);
        $this->uae = Country::create(['name' => 'United Arab Emirates', 'iso_code' => 'AE', 'is_active' => true]);
        $this->global = $this->user('Global', Role::SUPER_ADMIN_ID, null);
        $this->nepalAdmin = $this->user('Nepal Admin', Role::COUNTRY_ADMIN_ID, $this->nepal->id);
        $this->uaeAdmin = $this->user('UAE Admin', Role::COUNTRY_ADMIN_ID, $this->uae->id);
        $this->uaeStaff = $this->user('UAE Staff', Role::SUPER_STAFF_ID, $this->uae->id);

        foreach (['platform_module_dashboard', 'platform_dashboard_view'] as $name) {
            $permission = Permission::firstOrCreate(['name' => $name], ['scope' => Permission::SCOPE_PLATFORM]);
            DB::table('user_permissions')->insert([
                'user_id' => $this->uaeStaff->id,
                'permission_id' => $permission->id,
                'is_allowed' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->nepalCompany = $this->company('Nepal Company', $this->nepal);
        $this->uaeCompany = $this->company('UAE Company', $this->uae);
        $this->user('Nepal Tenant Staff', Role::COMPANY_STAFF_ID, null, $this->nepalCompany->id);
        $this->user('UAE Tenant Staff', Role::COMPANY_STAFF_ID, null, $this->uaeCompany->id);

        CompanyRegistration::create($this->registrationPayload('Nepal Registration', $this->nepal, $this->uaeStaff));
        CompanyRegistration::create($this->registrationPayload('UAE Registration', $this->uae, $this->global));

        $plan = SubscriptionPlan::create(['code' => 'dashboard-test', 'name' => 'Dashboard Test', 'staff_limit' => 5, 'is_active' => true, 'sort_order' => 0]);
        $this->payment($this->nepalCompany, $plan, 'approved');
        $this->payment($this->uaeCompany, $plan, 'pending');
    }

    public function test_global_super_admin_dashboard_remains_global(): void
    {
        $this->actingAs($this->global)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('totalCompanies', 2)
            ->assertViewHas('totalRegistrations', 2)
            ->assertViewHas('totalPayments', 2)
            ->assertViewHas('approvedPayments', 1)
            ->assertViewHas('pendingPayments', 1);
    }

    public function test_country_admin_dashboard_uses_record_country_and_ignores_creator_country(): void
    {
        $this->actingAs($this->nepalAdmin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('totalCompanies', 1)
            ->assertViewHas('totalRegistrations', 1)
            ->assertViewHas('totalPayments', 1)
            ->assertViewHas('approvedPayments', 1)
            ->assertViewHas('pendingPayments', 0)
            ->assertViewHas('staff', 1);

        $this->actingAs($this->uaeAdmin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('totalCompanies', 1)
            ->assertViewHas('totalRegistrations', 1)
            ->assertViewHas('totalPayments', 1)
            ->assertViewHas('approvedPayments', 0)
            ->assertViewHas('pendingPayments', 1)
            ->assertViewHas('staff', 1);
    }

    public function test_authorized_platform_staff_dashboard_is_country_scoped(): void
    {
        $this->actingAs($this->uaeStaff)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('totalCompanies', 1)
            ->assertViewHas('totalRegistrations', 1)
            ->assertViewHas('totalPayments', 1)
            ->assertViewHas('pendingPayments', 1);
    }

    public function test_request_country_tampering_cannot_change_dashboard_scope(): void
    {
        $this->actingAs($this->nepalAdmin)->get(route('admin.dashboard', ['country_id' => $this->uae->id]))
            ->assertOk()
            ->assertViewHas('totalCompanies', 1)
            ->assertViewHas('totalRegistrations', 1)
            ->assertViewHas('approvedPayments', 1)
            ->assertViewHas('pendingPayments', 0);
    }

    private function user(string $name, int $roleId, ?int $countryId, ?int $companyId = null): User
    {
        return User::create([
            'name' => $name,
            'email' => str($name)->slug().uniqid().'@example.test',
            'password' => Hash::make('password'),
            'role_id' => $roleId,
            'company_id' => $companyId,
            'country_id' => $countryId,
            'account_status' => 'active',
        ]);
    }

    private function company(string $name, Country $country): Company
    {
        return Company::create([
            'company_name' => $name,
            'mobile' => uniqid(),
            'email' => str($name)->slug().uniqid().'@example.test',
            'status' => 'active',
            'country_id' => $country->id,
        ]);
    }

    private function registrationPayload(string $name, Country $country, User $creator): array
    {
        $key = str($name)->slug().uniqid();

        return [
            'company_name' => $name,
            'full_name' => $name.' Owner',
            'email' => $key.'@example.test',
            'mobile_no' => uniqid(),
            'username' => $key,
            'password' => Hash::make('password'),
            'country_id' => $country->id,
            'registered_by_user_id' => $creator->id,
            'status' => 'pending',
        ];
    }

    private function payment(Company $company, SubscriptionPlan $plan, string $status): void
    {
        SubscriptionPayment::create([
            'company_id' => $company->id,
            'subscription_plan_id' => $plan->id,
            'action_type' => 'assign',
            'amount' => 1000,
            'currency_code' => 'NPR',
            'payment_method' => 'cash',
            'status' => $status,
        ]);
    }
}
