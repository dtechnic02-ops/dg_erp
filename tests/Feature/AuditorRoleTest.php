<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\ChartAccount;
use App\Models\Customer;
use App\Models\Country;
use App\Models\FinancialYear;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SalesInvoice;
use App\Models\SalesReturn;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use App\Services\DefaultChartAccountBootstrapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuditorRoleTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $auditor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);

        $nepal = Country::firstOrCreate(['iso_code' => 'NP'], ['name' => 'Nepal', 'is_active' => true]);
        $this->company = Company::create([
            'company_name' => 'Audited Company',
            'email' => 'audited@example.test',
            'mobile' => '9800000000',
            'status' => 'active',
            'country_id' => $nepal->id,
        ]);
        app(DefaultChartAccountBootstrapService::class)->seedForCompany((int) $this->company->id);

        $plan = SubscriptionPlan::create([
            'code' => 'auditor-test',
            'name' => 'Auditor Test',
            'staff_limit' => 10,
            'is_active' => true,
        ]);

        CompanySubscription::create([
            'company_id' => $this->company->id,
            'subscription_type' => 'paid',
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'start_date' => now()->subDay()->toDateString(),
            'expiry_date' => now()->addYear()->toDateString(),
            'staff_limit' => 10,
            'is_all_modules_enabled' => true,
            'activated_at' => now(),
        ]);

        $this->auditor = User::create([
            'name' => 'Company Auditor',
            'email' => 'auditor@example.test',
            'password' => Hash::make('AuditorPass123!'),
            'role_id' => Role::AUDITOR_ID,
            'company_id' => $this->company->id,
            'job_role' => 'auditor',
            'account_status' => 'active',
        ]);
    }

    public function test_auditor_role_is_bootstrapped_with_only_approved_read_permissions(): void
    {
        $role = Role::query()->findOrFail(Role::AUDITOR_ID);
        $names = $role->permissions()->pluck('name');

        $this->assertSame('auditor', $role->name);
        foreach (['view_sales', 'view_purchase', 'view_stock', 'view_accounts', 'view_vat', 'view_reports', 'journal.view', 'view_company_profile'] as $permission) {
            $this->assertTrue($names->contains($permission), $permission);
            $this->assertTrue($this->auditor->hasPermission($permission, $this->company->id), $permission);
        }
        foreach (['create_sales', 'edit_sales', 'cancel_sales', 'create_purchase', 'create_sales_payment', 'create_expense', 'journal.post', 'edit_company_profile', 'manage_users', 'system_maintenance'] as $permission) {
            $this->assertFalse($names->contains($permission), $permission);
            $this->assertFalse($this->auditor->hasPermission($permission, $this->company->id), $permission);
        }
        $this->assertFalse($this->auditor->hasPermission('view_sales', $this->company->id + 1));
    }

    public function test_separate_auditor_can_login_and_read_company_audit_surfaces(): void
    {
        $year = $this->financialYear($this->company);

        $this->post(route('login.post'), [
            'email' => $this->auditor->email,
            'password' => 'AuditorPass123!',
        ])->assertRedirect(route('company.dashboard'));

        $this->assertAuthenticatedAs($this->auditor);
        $this->assertSame($this->company->id, auth()->user()->company_id);

        foreach ([
            'dashboard' => route('company.dashboard'),
            'sales' => route('company.sales.index'),
            'returns' => route('company.sales-return.index'),
            'purchases' => route('company.purchases.index'),
            'sales payments' => route('company.sales-payment.index'),
            'purchase payments' => route('company.purchase-payments.index'),
            'journals' => route('company.journal.index'),
            'ledger' => route('company.accounting-reports.general-ledger', [
                'chart_account_id' => ChartAccount::where('company_id', $this->company->id)->value('id'),
            ]),
            'fiscal sales' => route('company.vat-report.fiscal-sales'),
            'financial years' => route('company.financial-years.index'),
            'profile' => route('company.profile'),
            'cbms status' => route('company.settings.ird-cbms.edit'),
        ] as $area => $url) {
            $response = $this->get($url);
            $this->assertSame(200, $response->status(), "Auditor read failed for {$area}: {$url}");
        }

        $this->get(route('company.sales.index'))
            ->assertOk()
            ->assertSee('data-auditor-readonly="true"', false);
        $this->assertStringContainsString(
            'body[data-auditor-readonly="true"] #dgPage a[href*="/create"]',
            file_get_contents(public_path('assets/company/css/common.css'))
        );
    }

    public function test_auditor_direct_mutation_and_mutation_form_attempts_are_denied_server_side(): void
    {
        $year = $this->financialYear($this->company);
        $this->actingAs($this->auditor);

        foreach ([
            route('company.sales.create'),
            route('company.sales.edit', 999),
            route('company.sales-return.create', 999),
            route('company.purchases.create'),
            route('company.sales-payment.create', 999),
            route('company.purchase-payments.create', 999),
            route('company.expense.create'),
            route('company.income.create'),
            route('company.journal.create'),
            route('company.financial-years.create'),
            route('company.financial-years.edit', $year),
            route('company.settings.factory-reset.show'),
            route('company.users.index'),
        ] as $url) {
            $this->get($url)->assertForbidden();
        }

        $this->post(route('company.sales.store'))->assertForbidden();
        $this->put(route('company.sales.update', 999))->assertForbidden();
        $this->post(route('company.sales.cancel', 999))->assertForbidden();
        $this->post(route('company.sales-return.store'))->assertForbidden();
        $this->post(route('company.purchases.store'))->assertForbidden();
        $this->put(route('company.settings.ird-cbms.update'))->assertForbidden();
        $this->post(route('company.profile.update'))->assertForbidden();
        $this->post(route('company.settings.factory-reset.execute'))->assertForbidden();
        $this->get(route('admin.company.permanent-delete.show', $this->company))->assertForbidden();
    }

    public function test_auditor_cannot_read_another_company_sales_returns_or_fiscal_evidence(): void
    {
        $foreignCompany = Company::create([
            'company_name' => 'Foreign Audit Target',
            'email' => 'foreign@example.test',
            'mobile' => '9800000001',
            'status' => 'active',
        ]);
        $foreignYear = FinancialYear::create([
            'company_id' => $foreignCompany->id,
            'name' => 'FOREIGN-FY-EVIDENCE',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);
        $customer = Customer::create(['company_id' => $foreignCompany->id, 'name' => 'FOREIGN-EVIDENCE', 'status' => 'active']);
        $invoice = SalesInvoice::create([
            'created_by' => $this->auditor->id,
            'company_id' => $foreignCompany->id,
            'financial_year_id' => $foreignYear->id,
            'customer_id' => $customer->id,
            'invoice_no' => 'FOREIGN-SALE-1',
            'sale_date' => '2026-06-01',
            'subtotal' => 100,
            'discount' => 0,
            'total_vat' => 0,
            'grand_total' => 100,
            'paid_amount' => 0,
            'due_amount' => 100,
            'payment_status' => 'unpaid',
            'status' => 1,
        ]);
        $return = SalesReturn::create([
            'created_by' => $this->auditor->id,
            'company_id' => $foreignCompany->id,
            'financial_year_id' => $foreignYear->id,
            'sales_invoice_id' => $invoice->id,
            'customer_id' => $customer->id,
            'return_no' => 'FOREIGN-CN-1',
            'return_date' => '2026-06-02',
            'subtotal' => 10,
            'total_vat' => 0,
            'grand_total' => 10,
            'adjust_amount' => 0,
            'refund_amount' => 10,
            'status' => 1,
        ]);

        $this->actingAs($this->auditor);
        $this->get(route('company.sales.show', $invoice->id))->assertNotFound();
        $this->get(route('company.sales-return.show', $return->id))->assertNotFound();
        $this->get(route('company.financial-years.index', ['company_id' => $foreignCompany->id]))
            ->assertOk()
            ->assertDontSee('FOREIGN-FY-EVIDENCE');
        $this->get(route('company.vat-report.fiscal-sales', ['company_id' => $foreignCompany->id]))
            ->assertOk()
            ->assertDontSee('FOREIGN-EVIDENCE');
    }

    public function test_company_admin_can_create_auditor_but_auditor_cannot_receive_custom_permissions(): void
    {
        $admin = User::create([
            'name' => 'Company Admin',
            'email' => 'admin@example.test',
            'password' => Hash::make('password'),
            'role_id' => Role::COMPANY_ADMIN_ID,
            'company_id' => $this->company->id,
            'account_status' => 'active',
        ]);

        $this->actingAs($admin)->post(route('company.users.store'), [
            'name' => 'Assigned Auditor',
            'email' => 'assigned-auditor@example.test',
            'password' => 'AuditorPass123!',
            'job_role' => 'auditor',
        ])->assertRedirect();

        $assigned = User::where('email', 'assigned-auditor@example.test')->firstOrFail();
        $this->assertSame(Role::AUDITOR_ID, (int) $assigned->role_id);
        $this->assertSame($this->company->id, (int) $assigned->company_id);
        $this->get(route('company.staff-permissions.edit', $assigned))->assertForbidden();
    }

    private function financialYear(Company $company): FinancialYear
    {
        return FinancialYear::create([
            'company_id' => $company->id,
            'name' => 'FY 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);
    }
}
