<?php

namespace Tests\Feature;

use App\Mail\AdminUserPasswordResetCompletedMail;
use App\Mail\AdminUserPasswordResetOtpMail;
use App\Mail\UserPasswordResetLinkMail;
use App\Models\Company;
use App\Models\PasswordResetRequest;
use App\Models\PlatformSetting;
use App\Models\PlatformSmtpSetting;
use App\Models\Role;
use App\Models\User;
use App\Services\PlatformMailService;
use App\Services\UserPasswordResetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminInitiatedPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            Role::SUPER_ADMIN_ID => 'Super Admin',
            Role::COMPANY_ADMIN_ID => 'Company Admin',
            Role::COMPANY_STAFF_ID => 'Company Staff',
            Role::SUPER_STAFF_ID => 'Super Staff',
        ] as $id => $name) {
            Role::query()->create(['id' => $id, 'name' => $name]);
        }

        DB::table('permissions')->insert([
            ['name' => 'module_users', 'scope' => 'company', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'reset_password', 'scope' => 'company', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->createPlatformSmtpSetting();
    }

    public function test_super_admin_initiates_reset_and_user_completes_link_then_otp_workflow(): void
    {
        Mail::fake();
        $admin = $this->user('admin@example.test', Role::SUPER_ADMIN_ID);
        $target = $this->user('target@example.test', Role::COMPANY_STAFF_ID, 10);
        $oldPasswordHash = $target->password;

        $this->actingAs($admin)
            ->post(route('admin.user.reset', $target))
            ->assertRedirect()
            ->assertSessionHas('success');

        $resetMail = null;
        Mail::assertSent(UserPasswordResetLinkMail::class, function (UserPasswordResetLinkMail $mail) use ($target, &$resetMail): bool {
            $resetMail = $mail;

            return $mail->hasTo($target->email)
                && $mail->usesMailer(PlatformMailService::MAILER_NAME);
        });

        $resetRequest = PasswordResetRequest::query()->sole();
        $this->assertNotSame(basename(parse_url($resetMail->resetUrl, PHP_URL_PATH)), $resetRequest->token_hash);
        $this->assertSame($oldPasswordHash, $target->fresh()->password);
        $this->assertNull($resetRequest->pending_password_hash);

        $token = basename(parse_url($resetMail->resetUrl, PHP_URL_PATH));
        $this->get($resetMail->resetUrl)
            ->assertOk()
            ->assertSee('Set New Password')
            ->assertSee($target->email)
            ->assertSee('Send Verification Code')
            ->assertSee(route('login'), false)
            ->assertSee(route('password-reset.password.submit', ['token' => $token]), false)
            ->assertSee('name="password"', false)
            ->assertSee('name="password_confirmation"', false)
            ->assertSee('name="_token"', false);

        $newPassword = 'DG-Secure-Password-2026!';
        $passwordResponse = $this->post(route('password-reset.password.submit', ['token' => $token]), [
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ])->assertRedirect();

        $otp = null;
        Mail::assertSent(AdminUserPasswordResetOtpMail::class, function (AdminUserPasswordResetOtpMail $mail) use ($target, &$otp): bool {
            $otp = $mail->otp;

            return $mail->hasTo($target->email)
                && $mail->usesMailer(PlatformMailService::MAILER_NAME);
        });

        $resetRequest->refresh();
        $this->assertNotNull($resetRequest->token_consumed_at);
        $this->assertNotNull($resetRequest->otp_sent_at);
        $this->assertNotSame($otp, $resetRequest->otp_hash);
        $this->assertNotSame($newPassword, $resetRequest->pending_password_hash);
        $this->assertSame($oldPasswordHash, $target->fresh()->password);

        $challenge = basename(parse_url($passwordResponse->headers->get('Location'), PHP_URL_PATH));
        DB::table('sessions')->insert([
            'id' => 'target-session',
            'user_id' => $target->id,
            'payload' => 'test',
            'last_activity' => now()->timestamp,
        ]);

        $this->post(route('password-reset.otp.verify', ['challenge' => $challenge]), ['otp' => $otp])
            ->assertRedirect(route('login'))
            ->assertSessionHas('success');

        $this->assertTrue(Hash::check($newPassword, $target->fresh()->password));
        $this->assertDatabaseMissing('sessions', ['user_id' => $target->id]);
        $resetRequest->refresh();
        $this->assertNotNull($resetRequest->otp_verified_at);
        $this->assertNotNull($resetRequest->password_changed_at);
        $this->assertNotNull($resetRequest->used_at);
        $this->assertNull($resetRequest->pending_password_hash);
        $this->assertNull($resetRequest->otp_hash);
        Mail::assertSent(AdminUserPasswordResetCompletedMail::class, fn (AdminUserPasswordResetCompletedMail $mail): bool => $mail->hasTo($target->email)
            && $mail->usesMailer(PlatformMailService::MAILER_NAME));

        $this->get($resetMail->resetUrl)->assertNotFound();
        $this->post(route('password-reset.otp.verify', ['challenge' => $challenge]), ['otp' => $otp])->assertNotFound();
    }

    public function test_company_admin_can_initiate_only_for_active_staff_in_own_company(): void
    {
        Mail::fake();
        [$company, $admin, $staff] = $this->companyUsers('own');
        [, , $otherStaff] = $this->companyUsers('other');

        $this->actingAs($admin)
            ->post(route('company.users.reset', $staff->id))
            ->assertRedirect()
            ->assertSessionHas('success');

        Mail::assertSent(UserPasswordResetLinkMail::class, fn (UserPasswordResetLinkMail $mail): bool => $mail->hasTo($staff->email)
            && $mail->usesMailer(PlatformMailService::MAILER_NAME));
        $this->assertDatabaseHas('password_reset_requests', [
            'user_id' => $staff->id,
            'initiated_by' => $admin->id,
            'user_email' => $staff->email,
        ]);

        $this->actingAs($admin)
            ->post(route('company.users.reset', $otherStaff->id))
            ->assertNotFound();

        $platformAdmin = $this->user('platform-admin@example.test', Role::SUPER_ADMIN_ID);
        $this->actingAs($admin)
            ->post(route('company.users.reset', $platformAdmin->id))
            ->assertNotFound();
    }

    public function test_staff_and_public_users_cannot_initiate_resets(): void
    {
        Mail::fake();
        [, , $staff] = $this->companyUsers('staff-auth');
        $target = $this->user('another-staff@example.test', Role::COMPANY_STAFF_ID, $staff->company_id);

        $this->actingAs($staff)
            ->post(route('company.users.reset', $target->id))
            ->assertForbidden();

        auth()->logout();
        $this->post(route('company.users.reset', $target->id))->assertRedirect(route('login'));
        $this->post('/password-reset')->assertNotFound();
        Mail::assertNothingSent();
    }

    public function test_expired_links_and_otp_attempt_limit_do_not_change_password(): void
    {
        Mail::fake();
        $admin = $this->user('expiry-admin@example.test', Role::SUPER_ADMIN_ID);
        $target = $this->user('expiry-target@example.test', Role::COMPANY_STAFF_ID, 20);
        [$expiredRequest, $expiredToken] = app(UserPasswordResetService::class)->initiate($target, $admin);
        $expiredRequest->update(['expires_at' => now()->subSecond()]);

        $this->get(route('password-reset.password.show', ['token' => $expiredToken]))->assertNotFound();

        [, $token] = app(UserPasswordResetService::class)->initiate($target, $admin);
        [, $challenge, $issuedOtp] = app(UserPasswordResetService::class)->beginOtp($token, 'Another-Secure-Password-2026!');
        $oldPasswordHash = $target->password;
        $incorrectOtp = $issuedOtp === '000000' ? '000001' : '000000';

        for ($attempt = 1; $attempt <= UserPasswordResetService::MAX_OTP_ATTEMPTS; $attempt++) {
            $result = app(UserPasswordResetService::class)->verifyOtp($challenge, $incorrectOtp);
        }

        $this->assertSame('locked', $result);
        $this->assertSame($oldPasswordHash, $target->fresh()->password);
        $this->assertNotNull(PasswordResetRequest::query()->latest('id')->first()->invalidated_at);
    }

    public function test_disabled_platform_smtp_prevents_initiation_without_default_mailer_fallback(): void
    {
        Mail::fake();
        PlatformSmtpSetting::query()->sole()->update(['is_active' => false]);
        $admin = $this->user('disabled-smtp-admin@example.test', Role::SUPER_ADMIN_ID);
        $target = $this->user('disabled-smtp-target@example.test', Role::COMPANY_STAFF_ID, 30);

        $this->actingAs($admin)
            ->post(route('admin.user.reset', $target))
            ->assertRedirect()
            ->assertSessionHas('error', 'Unable to send password reset instructions. No password was changed.')
            ->assertSessionMissing('success');

        Mail::assertNothingSent();
        $request = PasswordResetRequest::query()->sole();
        $this->assertNotNull($request->invalidated_at);
        $this->assertNull($request->pending_password_hash);
    }

    public function test_incomplete_platform_smtp_fails_without_exposing_encrypted_password(): void
    {
        Mail::fake();
        $smtp = PlatformSmtpSetting::query()->sole();
        $storedCiphertext = $smtp->getRawOriginal('password');
        $this->assertNotSame('smtp-secret-password', $storedCiphertext);
        $this->assertArrayNotHasKey('password', $smtp->toArray());
        $smtp->update(['host' => '']);

        $admin = $this->user('incomplete-smtp-admin@example.test', Role::SUPER_ADMIN_ID);
        $target = $this->user('incomplete-smtp-target@example.test', Role::COMPANY_STAFF_ID, 31);
        $response = $this->actingAs($admin)->post(route('admin.user.reset', $target));

        $response->assertRedirect()
            ->assertSessionHas('error', 'Unable to send password reset instructions. No password was changed.')
            ->assertSessionMissing('success');
        $this->assertStringNotContainsString('smtp-secret-password', $response->getContent());
        Mail::assertNothingSent();
    }

    public function test_stored_encryption_maps_to_supported_symfony_smtp_transport_schemes(): void
    {
        $smtp = PlatformSmtpSetting::query()->sole();
        $configureMailer = new \ReflectionMethod(PlatformMailService::class, 'configureMailer');

        foreach ([
            'tls' => ['smtp', true],
            'starttls' => ['smtp', true],
            'ssl' => ['smtps', false],
        ] as $storedEncryption => [$expectedScheme, $requiresTls]) {
            $smtp->update(['encryption' => $storedEncryption]);
            $configureMailer->invoke(app(PlatformMailService::class));

            $mailerConfig = Config::get('mail.mailers.'.PlatformMailService::MAILER_NAME);
            $this->assertSame($expectedScheme, $mailerConfig['scheme']);
            $this->assertSame($requiresTls, $mailerConfig['require_tls']);

            $transport = app('mail.manager')->mailer(PlatformMailService::MAILER_NAME)->getSymfonyTransport();
            $this->assertStringStartsWith($expectedScheme.'://', (string) $transport);
            app('mail.manager')->purge(PlatformMailService::MAILER_NAME);
        }
    }

    private function user(string $email, int $roleId, ?int $companyId = null): User
    {
        return User::query()->create([
            'name' => strtok($email, '@'),
            'email' => $email,
            'password' => Hash::make('Old-Password-2026!'),
            'role_id' => $roleId,
            'company_id' => $companyId,
            'account_status' => 'active',
        ]);
    }

    private function companyUsers(string $suffix): array
    {
        $company = Company::query()->create([
            'company_name' => "Company {$suffix}",
            'mobile' => '98'.str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
            'email' => "company-{$suffix}@example.test",
            'status' => 'active',
        ]);
        $admin = $this->user("admin-{$suffix}@example.test", Role::COMPANY_ADMIN_ID, $company->id);
        $staff = $this->user("staff-{$suffix}@example.test", Role::COMPANY_STAFF_ID, $company->id);

        $planId = DB::table('subscription_plans')->insertGetId([
            'code' => "plan-{$suffix}",
            'name' => "Plan {$suffix}",
            'staff_limit' => 10,
            'is_active' => true,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('company_subscriptions')->insert([
            'company_id' => $company->id,
            'subscription_type' => 'paid',
            'subscription_plan_id' => $planId,
            'status' => 'active',
            'start_date' => now()->subDay()->toDateString(),
            'expiry_date' => now()->addYear()->toDateString(),
            'staff_limit' => 10,
            'is_all_modules_enabled' => true,
            'activated_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$company, $admin, $staff];
    }

    private function createPlatformSmtpSetting(): PlatformSmtpSetting
    {
        $setting = PlatformSetting::query()->create([
            'platform_name' => 'DG ERP',
            'owner_name' => 'DG ERP',
            'primary_email' => 'support@example.test',
            'primary_mobile' => '9800000000',
        ]);

        return $setting->smtpSetting()->create([
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
    }
}
