<?php

namespace Tests\Feature;

use App\Mail\CompanyRegistrationApprovedMail;
use App\Models\Company;
use App\Models\CompanyRegistration;
use App\Models\Country;
use App\Models\PlatformSetting;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\PlatformMailService;
use App\Services\SubscriptionService;
use App\Services\DefaultChartAccountBootstrapService;
use App\Models\ChartAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

class CompanyRegistrationApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::query()->create(['id' => Role::SUPER_ADMIN_ID, 'name' => 'Super Admin']);
        Role::query()->create(['id' => Role::COMPANY_ADMIN_ID, 'name' => 'Company Admin']);

        PlatformSetting::query()->create([
            'platform_name' => 'DG ERP',
            'owner_name' => 'DG ERP',
            'primary_email' => 'support@example.test',
            'primary_mobile' => '9800000000',
        ])->smtpSetting()->create([
            'mailer' => 'smtp',
            'host' => 'smtp.example.test',
            'port' => 587,
            'username' => 'smtp-user',
            'password' => 'smtp-secret-password',
            'encryption' => 'tls',
            'from_address' => 'no-reply@example.test',
            'from_name' => 'DG ERP',
            'is_active' => true,
        ]);

        SubscriptionPlan::query()->create([
            'code' => 'trial',
            'name' => 'Trial',
            'staff_limit' => 5,
            'is_active' => true,
            'sort_order' => 0,
        ]);
    }

    public function test_approval_preserves_registered_password_and_sends_platform_email_to_authoritative_address(): void
    {
        Mail::fake();
        $admin = $this->superAdmin();
        $country = Country::query()->create(['name' => 'Nepal', 'iso_code' => 'NP', 'is_active' => true]);
        $plainPassword = 'Secure-Approval-2026!';

        $this->post(route('company.register.post'), [
            'company_name' => 'Approved Company',
            'full_name' => 'Approved Owner',
            'email' => 'approved-owner@example.test',
            'username' => 'approved-owner',
            'password' => $plainPassword,
            'mobile_no' => '9800000001',
            'country_id' => $country->id,
        ])->assertRedirect(route('login'));

        $registration = CompanyRegistration::query()->sole();
        $this->assertNotSame($plainPassword, $registration->password);
        $this->assertTrue(Hash::check($plainPassword, $registration->password));

        File::partialMock();
        File::shouldReceive('exists')
            ->once()
            ->with(\Mockery::on(fn (string $path): bool => str_starts_with($path, public_path('companies/'))))
            ->andReturnFalse();
        File::shouldReceive('makeDirectory')->once()->withArgs(fn (string $path, int $mode, bool $recursive): bool => $mode === 0755 && $recursive);

        $this->actingAs($admin)
            ->post(route('admin.approve', $registration->id), ['email' => 'attacker@example.test'])
            ->assertRedirect(route('admin.registrations'))
            ->assertSessionHas('success', 'Company Approved Successfully');

        $company = Company::query()->where('email', $registration->email)->sole();
        $companyAdmin = User::query()->where('email', $registration->email)->sole();

        $requiredSystemCodes = app(DefaultChartAccountBootstrapService::class)->requiredSystemCodes();
        $seededAccounts = ChartAccount::query()
            ->where('company_id', $company->id)
            ->where('is_system', true)
            ->whereIn('system_code', $requiredSystemCodes)
            ->get();
        $this->assertContains('INVENTORY', $seededAccounts->pluck('system_code')->all());
        $this->assertEqualsCanonicalizing($requiredSystemCodes, $seededAccounts->pluck('system_code')->all());

        $accountSnapshot = $seededAccounts->map->getAttributes()->all();
        app(DefaultChartAccountBootstrapService::class)->seedForCompany($company->id);
        $this->assertSame($seededAccounts->count(), ChartAccount::query()->where('company_id', $company->id)->count());
        $this->assertSame($accountSnapshot, ChartAccount::query()->where('company_id', $company->id)->orderBy('id')->get()->map->getAttributes()->all());

        $this->assertSame('approved', $registration->fresh()->status);
        $this->assertSame('active', $company->status);
        $this->assertSame($company->id, $companyAdmin->company_id);
        $this->assertSame(Role::COMPANY_ADMIN_ID, $companyAdmin->role_id);
        $this->assertNotSame($plainPassword, $companyAdmin->password);
        $this->assertTrue(Hash::check($plainPassword, $companyAdmin->password));
        $this->assertSame($registration->password, $companyAdmin->password, 'The registration hash must not be hashed a second time.');

        Mail::assertSent(CompanyRegistrationApprovedMail::class, function (CompanyRegistrationApprovedMail $mail) use ($registration, $company, $plainPassword): bool {
            $content = $mail->render();

            return $mail->hasTo($registration->email)
                && ! $mail->hasTo('attacker@example.test')
                && $mail->usesMailer(PlatformMailService::MAILER_NAME)
                && str_contains($content, $company->company_name)
                && str_contains($content, $registration->email)
                && ! str_contains($content, $plainPassword)
                && ! str_contains($content, $registration->password);
        });

        $this->actingAs($admin)
            ->post(route('admin.approve', $registration->id))
            ->assertSessionHas('error', 'Already processed!');
        Mail::assertSentCount(1);

        auth()->logout();
        $this->post(route('login.post'), [
            'email' => $registration->email,
            'password' => $plainPassword,
        ])->assertSessionDoesntHaveErrors();
        $this->assertAuthenticatedAs($companyAdmin);
    }

    public function test_invalid_registration_password_is_rejected_and_failed_approval_sends_no_email(): void
    {
        Mail::fake();
        $country = Country::query()->create(['name' => 'Nepal', 'iso_code' => 'NP', 'is_active' => true]);

        $this->post(route('company.register.post'), [
            'company_name' => 'Invalid Password Company',
            'full_name' => 'Invalid Owner',
            'email' => 'invalid-owner@example.test',
            'username' => 'invalid-owner',
            'password' => 'short',
            'mobile_no' => '9800000002',
            'country_id' => $country->id,
        ])->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('company_registrations', ['email' => 'invalid-owner@example.test']);
        Mail::assertNothingSent();

        $registration = CompanyRegistration::query()->create([
            'company_name' => 'Failed Approval Company',
            'full_name' => 'Failed Owner',
            'email' => 'failed-owner@example.test',
            'username' => 'failed-owner',
            'password' => Hash::make('Secure-Approval-2026!'),
            'mobile_no' => '9800000003',
            'country_id' => $country->id,
            'status' => 'pending',
        ]);

        File::partialMock();
        File::shouldReceive('exists')
            ->once()
            ->with(\Mockery::on(fn (string $path): bool => str_starts_with($path, public_path('companies/'))))
            ->andReturnFalse();
        File::shouldReceive('makeDirectory')->once();
        $subscription = $this->mock(SubscriptionService::class);
        $subscription->shouldReceive('startRegisterTrial')->once()->andThrow(new RuntimeException('Approval transaction failed safely.'));

        $this->actingAs($this->superAdmin())
            ->post(route('admin.approve', $registration->id))
            ->assertSessionHas('error', 'Approval transaction failed safely.');

        $this->assertSame('pending', $registration->fresh()->status);
        $this->assertDatabaseMissing('companies', ['email' => $registration->email]);
        Mail::assertNothingSent();
    }

    public function test_mail_failure_keeps_successful_approval_and_returns_safe_warning(): void
    {
        $admin = $this->superAdmin();
        $country = Country::query()->create(['name' => 'Nepal', 'iso_code' => 'NP', 'is_active' => true]);
        $registration = CompanyRegistration::query()->create([
            'company_name' => 'Mail Failure Company',
            'full_name' => 'Mail Failure Owner',
            'email' => 'mail-failure@example.test',
            'username' => 'mail-failure-owner',
            'password' => Hash::make('Secure-Approval-2026!'),
            'mobile_no' => '9800000004',
            'country_id' => $country->id,
            'status' => 'pending',
        ]);

        File::partialMock();
        File::shouldReceive('exists')
            ->once()
            ->with(\Mockery::on(fn (string $path): bool => str_starts_with($path, public_path('companies/'))))
            ->andReturnFalse();
        File::shouldReceive('makeDirectory')->once();
        $mail = $this->mock(PlatformMailService::class);
        $mail->shouldReceive('send')->once()->andThrow(new RuntimeException('smtp-secret-password'));

        $this->actingAs($admin)
            ->post(route('admin.approve', $registration->id))
            ->assertRedirect(route('admin.registrations'))
            ->assertSessionHas('warning', 'Company approved successfully, but the approval email could not be sent.')
            ->assertSessionMissing('error');

        $this->assertSame('approved', $registration->fresh()->status);
        $this->assertDatabaseHas('companies', ['email' => $registration->email]);
        $this->assertDatabaseHas('users', ['email' => $registration->email, 'role_id' => Role::COMPANY_ADMIN_ID]);
    }

    private function superAdmin(): User
    {
        return User::query()->firstOrCreate(['email' => 'platform-admin@example.test'], [
            'name' => 'Platform Admin',
            'password' => Hash::make('Admin-Password-2026!'),
            'role_id' => Role::SUPER_ADMIN_ID,
            'company_id' => null,
            'account_status' => 'active',
        ]);
    }
}
