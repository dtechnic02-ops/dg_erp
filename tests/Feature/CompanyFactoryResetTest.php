<?php

namespace Tests\Feature;

use App\Mail\CompanyFactoryResetOtpMail;
use App\Models\Company;
use App\Models\CompanyDestructiveChallenge;
use App\Models\CompanySubscription;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\CompanyDestructiveChallengeService;
use App\Services\CompanyFactoryResetService;
use App\Services\PlatformMailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Mockery;
use Tests\TestCase;

class CompanyFactoryResetTest extends TestCase
{
    use RefreshDatabase;

    private string $fileRoot;
    private SubscriptionPlan $plan;
    private int $mobileSuffix = 0;

    protected function setUp(): void
    {
        parent::setUp();
        foreach ([
            [Role::SUPER_ADMIN_ID, 'Super Admin'], [Role::COMPANY_ADMIN_ID, 'Company Admin'],
            [Role::COMPANY_STAFF_ID, 'Company Staff'], [Role::SUPER_STAFF_ID, 'Super Staff'],
        ] as [$id, $name]) DB::table('roles')->insert(['id' => $id, 'name' => $name, 'created_at' => now(), 'updated_at' => now()]);
        $this->plan = SubscriptionPlan::create(['code' => 'factory', 'name' => 'Factory Plan', 'staff_limit' => 10, 'is_active' => true, 'sort_order' => 0]);
        $this->fileRoot = storage_path('framework/testing/company-factory-'.bin2hex(random_bytes(5)));
        config([
            'dg-erp.destructive_files.public_company_root' => $this->fileRoot.'/public-companies',
            'dg-erp.destructive_files.storage_company_root' => $this->fileRoot.'/storage-companies',
            'dg-erp.destructive_files.crm_root' => $this->fileRoot.'/crm',
        ]);
    }

    protected function tearDown(): void
    {
        if (isset($this->fileRoot) && File::isDirectory($this->fileRoot)) File::deleteDirectory($this->fileRoot);
        parent::tearDown();
    }

    public function test_only_active_company_admin_can_access_own_factory_reset(): void
    {
        $companyA = $this->company('Company A');
        $companyB = $this->company('Company B');
        $adminA = $this->user(Role::COMPANY_ADMIN_ID, $companyA->id, 'admin-a@example.test');
        $adminB = $this->user(Role::COMPANY_ADMIN_ID, $companyB->id, 'admin-b@example.test');
        $staffA = $this->user(Role::COMPANY_STAFF_ID, $companyA->id, 'staff-a@example.test');
        $superStaff = $this->user(Role::SUPER_STAFF_ID, null, 'superstaff@example.test');

        $this->actingAs($adminA)->get(route('company.settings.factory-reset.show'))->assertOk()->assertSee('Company A');
        $this->actingAs($adminB)->get(route('company.settings.factory-reset.show'))->assertOk()->assertSee('Company B');
        $this->actingAs($staffA)->get(route('company.settings.factory-reset.show'))->assertForbidden();
        $this->actingAs($superStaff)->get(route('company.settings.factory-reset.show'))->assertForbidden();
        auth()->logout();
        $this->get(route('company.settings.factory-reset.show'))->assertRedirect();

        $adminA->update(['account_status' => 'blocked']);
        $this->actingAs($adminA->fresh())->get(route('company.settings.factory-reset.show'))->assertForbidden();
    }

    public function test_factory_otp_goes_only_to_initiating_admin_and_request_cannot_override_recipient(): void
    {
        $company = $this->company('OTP Company');
        $admin = $this->user(Role::COMPANY_ADMIN_ID, $company->id, 'owner@example.test');
        $otherAdmin = $this->user(Role::COMPANY_ADMIN_ID, $company->id, 'other@example.test');
        $capturedOtp = null;
        $mail = Mockery::mock(PlatformMailService::class);
        $mail->shouldReceive('send')->once()->with('owner@example.test', Mockery::on(function ($mailable) use (&$capturedOtp): bool {
            if (! $mailable instanceof CompanyFactoryResetOtpMail) return false;
            $capturedOtp = $mailable->otp;
            return $mailable->companyName === 'OTP Company';
        }));
        $this->app->instance(PlatformMailService::class, $mail);

        $this->actingAs($admin)->post(route('company.settings.factory-reset.otp'), [
            'confirmation_phrase' => 'RESET MY COMPANY',
            'email' => $otherAdmin->email,
            'company_id' => 999999,
        ])->assertRedirect()->assertSessionHas('success');

        $challenge = CompanyDestructiveChallenge::query()->sole();
        $this->assertSame($admin->id, $challenge->requested_by);
        $this->assertSame($company->id, $challenge->company_id);
        $this->assertNotSame($capturedOtp, $challenge->otp_hash);
    }

    public function test_wrong_password_phrase_or_otp_causes_zero_reset(): void
    {
        $company = $this->company('Protected');
        $admin = $this->user(Role::COMPANY_ADMIN_ID, $company->id, 'admin@example.test');
        DB::table('customers')->insert($this->customerRow($company->id, 'Protected Customer'));
        [$challenge, $otp] = app(CompanyDestructiveChallengeService::class)->issue($company->id, $admin->id, CompanyDestructiveChallenge::PURPOSE_FACTORY_RESET);

        $this->actingAs($admin)->withSession(['company_factory_reset_challenge' => $challenge->id])
            ->post(route('company.settings.factory-reset.execute'), [
                'challenge_id' => $challenge->id, 'current_password' => 'wrong',
                'confirmation_phrase' => 'RESET MY COMPANY', 'otp' => $otp,
            ])->assertSessionHasErrors('current_password');
        $this->assertDatabaseHas('customers', ['company_id' => $company->id, 'name' => 'Protected Customer']);

        $this->actingAs($admin)->withSession(['company_factory_reset_challenge' => $challenge->id])
            ->post(route('company.settings.factory-reset.execute'), [
                'challenge_id' => $challenge->id, 'current_password' => 'Password!123',
                'confirmation_phrase' => 'WRONG', 'otp' => $otp,
            ])->assertSessionHasErrors('confirmation_phrase');
        $this->assertDatabaseHas('customers', ['company_id' => $company->id]);

        $this->actingAs($admin)->withSession(['company_factory_reset_challenge' => $challenge->id])
            ->post(route('company.settings.factory-reset.execute'), [
                'challenge_id' => $challenge->id, 'current_password' => 'Password!123',
                'confirmation_phrase' => 'RESET MY COMPANY', 'otp' => '000000',
            ])->assertSessionHas('error');
        $this->assertDatabaseHas('customers', ['company_id' => $company->id]);
    }

    public function test_company_a_factory_reset_preserves_approved_data_and_leaves_company_b_identical(): void
    {
        $companyA = $this->company('Company A');
        $companyB = $this->company('Company B');
        $adminA = $this->user(Role::COMPANY_ADMIN_ID, $companyA->id, 'admin-a@example.test');
        $adminA2 = $this->user(Role::COMPANY_ADMIN_ID, $companyA->id, 'admin-a2@example.test');
        $staffA = $this->user(Role::COMPANY_STAFF_ID, $companyA->id, 'staff-a@example.test');
        $adminB = $this->user(Role::COMPANY_ADMIN_ID, $companyB->id, 'admin-b@example.test');
        $staffB = $this->user(Role::COMPANY_STAFF_ID, $companyB->id, 'staff-b@example.test');

        $permission = Permission::create(['name' => 'module_customer', 'scope' => Permission::SCOPE_COMPANY]);
        DB::table('company_permission')->insert([
            ['company_id' => $companyA->id, 'permission_id' => $permission->id],
            ['company_id' => $companyB->id, 'permission_id' => $permission->id],
        ]);
        $adminA->permissions()->attach($permission->id, ['is_allowed' => true]);
        $staffA->permissions()->attach($permission->id, ['is_allowed' => true]);
        DB::table('company_whatsapp_settings')->insert([
            ['company_id' => $companyA->id, 'is_enabled' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['company_id' => $companyB->id, 'is_enabled' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('customers')->insert([$this->customerRow($companyA->id, 'Customer A'), $this->customerRow($companyB->id, 'Customer B')]);
        DB::table('financial_years')->insert([
            ['company_id' => $companyA->id, 'name' => 'FY A', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => 1, 'created_by' => $adminA->id, 'created_at' => now(), 'updated_at' => now()],
            ['company_id' => $companyB->id, 'name' => 'FY B', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => 1, 'created_by' => $adminB->id, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('sessions')->insert([
            ['id' => 'current-a', 'user_id' => $adminA->id, 'ip_address' => '127.0.0.1', 'user_agent' => 'test', 'payload' => 'current', 'last_activity' => time()],
            ['id' => 'other-a', 'user_id' => $adminA2->id, 'ip_address' => '127.0.0.1', 'user_agent' => 'test', 'payload' => 'other', 'last_activity' => time()],
            ['id' => 'staff-a', 'user_id' => $staffA->id, 'ip_address' => '127.0.0.1', 'user_agent' => 'test', 'payload' => 'staff', 'last_activity' => time()],
            ['id' => 'session-b', 'user_id' => $adminB->id, 'ip_address' => '127.0.0.1', 'user_agent' => 'test', 'payload' => 'b', 'last_activity' => time()],
        ]);
        $operationalA = config('dg-erp.destructive_files.storage_company_root').'/'.$companyA->id;
        $profileA = config('dg-erp.destructive_files.public_company_root').'/'.$companyA->id.'/profile/logo.jpg';
        $fileB = config('dg-erp.destructive_files.storage_company_root').'/'.$companyB->id.'/keep.txt';
        File::ensureDirectoryExists($operationalA); File::put($operationalA.'/remove.txt', 'remove');
        File::ensureDirectoryExists(dirname($profileA)); File::put($profileA, 'profile');
        File::ensureDirectoryExists(dirname($fileB)); File::put($fileB, 'company-b');
        $companyA->update(['logo_path' => 'companies/'.$companyA->id.'/profile/logo.jpg']);

        $companyBefore = Company::findOrFail($companyA->id)->toArray();
        $adminHashes = User::whereIn('id', [$adminA->id, $adminA2->id])->pluck('password', 'id')->all();
        $subscriptionA = CompanySubscription::where('company_id', $companyA->id)->firstOrFail()->toArray();
        $companyBSnapshot = $this->snapshot($companyB->id);

        app(CompanyFactoryResetService::class)->reset($companyA->fresh(), $adminA->id, 'current-a');

        $this->assertSame($companyBefore, Company::findOrFail($companyA->id)->toArray());
        $this->assertSame($adminHashes, User::whereIn('id', [$adminA->id, $adminA2->id])->pluck('password', 'id')->all());
        $this->assertDatabaseMissing('users', ['id' => $staffA->id]);
        $this->assertDatabaseMissing('user_permissions', ['user_id' => $staffA->id]);
        $this->assertDatabaseHas('user_permissions', ['user_id' => $adminA->id, 'permission_id' => $permission->id]);
        $this->assertDatabaseHas('company_permission', ['company_id' => $companyA->id, 'permission_id' => $permission->id]);
        $this->assertDatabaseHas('company_whatsapp_settings', ['company_id' => $companyA->id]);
        $this->assertSame($subscriptionA, CompanySubscription::where('company_id', $companyA->id)->firstOrFail()->toArray());
        $this->assertDatabaseMissing('customers', ['company_id' => $companyA->id]);
        $this->assertDatabaseMissing('financial_years', ['company_id' => $companyA->id]);
        $this->assertDatabaseMissing('accounts', ['company_id' => $companyA->id]);
        $this->assertDatabaseMissing('vats', ['company_id' => $companyA->id]);
        $this->assertDatabaseHas('sessions', ['id' => 'current-a', 'user_id' => $adminA->id]);
        $this->assertDatabaseMissing('sessions', ['id' => 'other-a']);
        $this->assertFalse(File::exists($operationalA));
        $this->assertTrue(File::exists($profileA));
        $this->assertGreaterThan(0, DB::table('chart_accounts')->where('company_id', $companyA->id)->where('is_system', true)->count());
        $this->assertSame(0, DB::table('chart_accounts')->where('company_id', $companyA->id)->whereNotNull('system_code')->select('system_code')->groupBy('system_code')->havingRaw('COUNT(*) > 1')->count());

        $this->assertSame($companyBSnapshot, $this->snapshot($companyB->id));
        $this->assertTrue(File::exists($fileB));
        $this->assertDatabaseHas('users', ['id' => $staffB->id, 'company_id' => $companyB->id]);
    }

    private function company(string $name): Company
    {
        $this->mobileSuffix++;
        $company = Company::create(['company_name' => $name, 'email' => strtolower(str_replace(' ', '-', $name)).'@example.test', 'mobile' => '981000'.str_pad((string) $this->mobileSuffix, 4, '0', STR_PAD_LEFT), 'status' => 'active']);
        CompanySubscription::create(['company_id' => $company->id, 'subscription_type' => 'paid', 'subscription_plan_id' => $this->plan->id, 'status' => 'active', 'start_date' => now()->subDay()->toDateString(), 'expiry_date' => now()->addYear()->toDateString(), 'staff_limit' => 10, 'is_all_modules_enabled' => true, 'activated_at' => now()]);
        return $company;
    }

    private function user(int $roleId, ?int $companyId, string $email): User
    {
        return User::create(['name' => $email, 'email' => $email, 'password' => 'Password!123', 'role_id' => $roleId, 'company_id' => $companyId, 'account_status' => 'active']);
    }

    private function customerRow(int $companyId, string $name): array
    {
        return ['company_id' => $companyId, 'name' => $name, 'status' => 'active', 'opening_balance' => 0, 'credit_days' => 0, 'current_balance' => 0, 'created_at' => now(), 'updated_at' => now()];
    }

    private function snapshot(int $companyId): array
    {
        return [
            'company' => (array) DB::table('companies')->where('id', $companyId)->first(),
            'users' => DB::table('users')->where('company_id', $companyId)->orderBy('id')->get()->map(fn ($r) => (array) $r)->all(),
            'permissions' => DB::table('company_permission')->where('company_id', $companyId)->orderBy('id')->get()->map(fn ($r) => (array) $r)->all(),
            'subscriptions' => DB::table('company_subscriptions')->where('company_id', $companyId)->orderBy('id')->get()->map(fn ($r) => (array) $r)->all(),
            'whatsapp' => DB::table('company_whatsapp_settings')->where('company_id', $companyId)->orderBy('id')->get()->map(fn ($r) => (array) $r)->all(),
            'customers' => DB::table('customers')->where('company_id', $companyId)->orderBy('id')->get()->map(fn ($r) => (array) $r)->all(),
            'financial_years' => DB::table('financial_years')->where('company_id', $companyId)->orderBy('id')->get()->map(fn ($r) => (array) $r)->all(),
            'chart_accounts' => DB::table('chart_accounts')->where('company_id', $companyId)->orderBy('id')->get()->map(fn ($r) => (array) $r)->all(),
        ];
    }
}
