<?php

namespace Tests\Feature;

use App\Mail\CompanyPermanentDeletionOtpMail;
use App\Models\Company;
use App\Models\CompanyIrdCbmsSetting;
use App\Models\CompanyDestructiveChallenge;
use App\Models\Country;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\CompanyDestructiveChallengeService;
use App\Services\CompanyPermanentDeletionService;
use App\Services\FiscalDocumentPolicyService;
use App\Services\PlatformMailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Tests\TestCase;

class CompanyPermanentDeletionTest extends TestCase
{
    use RefreshDatabase;

    private string $fileRoot;

    protected function setUp(): void
    {
        parent::setUp();
        foreach ([
            [Role::SUPER_ADMIN_ID, 'Super Admin'], [Role::COMPANY_ADMIN_ID, 'Company Admin'],
            [Role::COMPANY_STAFF_ID, 'Company Staff'], [Role::SUPER_STAFF_ID, 'Super Staff'],
        ] as [$id, $name]) {
            DB::table('roles')->insert(['id' => $id, 'name' => $name, 'created_at' => now(), 'updated_at' => now()]);
        }

        $this->fileRoot = storage_path('framework/testing/company-destruction-'.bin2hex(random_bytes(5)));
        config([
            'dg-erp.destructive_files.public_company_root' => $this->fileRoot.'/public-companies',
            'dg-erp.destructive_files.storage_company_root' => $this->fileRoot.'/storage-companies',
            'dg-erp.destructive_files.crm_root' => $this->fileRoot.'/crm',
            'dg-erp.destructive_files.storage_public_root' => $this->fileRoot.'/storage-public',
        ]);
    }

    protected function tearDown(): void
    {
        if (isset($this->fileRoot) && File::isDirectory($this->fileRoot)) {
            File::deleteDirectory($this->fileRoot);
        }
        parent::tearDown();
    }

    public function test_only_canonical_active_super_admin_can_open_and_initiate_permanent_delete(): void
    {
        $company = $this->company('Target');
        $superAdmin = $this->user(Role::SUPER_ADMIN_ID, null, 'super@example.test');
        $companyAdmin = $this->user(Role::COMPANY_ADMIN_ID, $company->id, 'owner@example.test');
        $staff = $this->user(Role::COMPANY_STAFF_ID, $company->id, 'staff@example.test');
        $superStaff = $this->superStaffWithDeletePermission();

        $this->actingAs($superAdmin)->get(route('admin.company.permanent-delete.show', $company))->assertOk();
        $this->actingAs($companyAdmin)->get(route('admin.company.permanent-delete.show', $company))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.company.permanent-delete.show', $company))->assertForbidden();
        $this->actingAs($superStaff)->get(route('admin.company.permanent-delete.show', $company))->assertForbidden();
        auth()->logout();
        $this->get(route('admin.company.permanent-delete.show', $company))->assertRedirect();
    }

    public function test_otp_is_sent_only_to_authenticated_super_admin_and_plaintext_is_not_stored(): void
    {
        $company = $this->company('Target Company');
        $superAdmin = $this->user(Role::SUPER_ADMIN_ID, null, 'canonical@example.test');
        $this->user(Role::COMPANY_ADMIN_ID, $company->id, 'company-admin@example.test');
        $capturedOtp = null;
        $mail = Mockery::mock(PlatformMailService::class);
        $mail->shouldReceive('send')->once()->with(
            'canonical@example.test',
            Mockery::on(function ($mailable) use (&$capturedOtp): bool {
                if (! $mailable instanceof CompanyPermanentDeletionOtpMail) return false;
                $capturedOtp = $mailable->otp;
                return $mailable->companyName === 'Target Company';
            })
        );
        $this->app->instance(PlatformMailService::class, $mail);

        $this->actingAs($superAdmin)->post(route('admin.company.permanent-delete.otp', $company), [
            'company_name_confirmation' => $company->company_name,
            'email' => 'attacker@example.test',
        ])->assertRedirect()->assertSessionHas('success');

        $challenge = CompanyDestructiveChallenge::query()->sole();
        $this->assertNotNull($capturedOtp);
        $this->assertTrue(Hash::check($capturedOtp, $challenge->otp_hash));
        $this->assertStringNotContainsString($capturedOtp, $challenge->otp_hash);
        $this->assertDatabaseHas('companies', ['id' => $company->id]);
    }

    public function test_mail_failure_invalidates_challenge_and_never_reports_success(): void
    {
        $company = $this->company('Mail Failure');
        $superAdmin = $this->user(Role::SUPER_ADMIN_ID, null, 'super@example.test');
        $mail = Mockery::mock(PlatformMailService::class);
        $mail->shouldReceive('send')->once()->andThrow(new \RuntimeException('secret provider output'));
        $this->app->instance(PlatformMailService::class, $mail);

        $this->actingAs($superAdmin)->post(route('admin.company.permanent-delete.otp', $company), [
            'company_name_confirmation' => $company->company_name,
        ])->assertRedirect()->assertSessionHas('error')->assertSessionMissing('success');

        $this->assertNotNull(CompanyDestructiveChallenge::query()->sole()->invalidated_at);
        $this->assertDatabaseHas('companies', ['id' => $company->id]);
    }

    public function test_otp_expiry_wrong_attempt_limit_resend_and_replay_are_enforced(): void
    {
        $company = $this->company('Challenge');
        $admin = $this->user(Role::SUPER_ADMIN_ID, null, 'super@example.test');
        $service = app(CompanyDestructiveChallengeService::class);
        [$challenge, $otp] = $service->issue($company->id, $admin->id, CompanyDestructiveChallenge::PURPOSE_PERMANENT_DELETE);

        for ($attempt = 1; $attempt <= CompanyDestructiveChallengeService::MAX_ATTEMPTS; $attempt++) {
            try { $service->consume($challenge->id, $company->id, $admin->id, CompanyDestructiveChallenge::PURPOSE_PERMANENT_DELETE, '000000'); } catch (\RuntimeException) {}
        }
        $this->assertNotNull($challenge->fresh()->invalidated_at);
        $this->assertDatabaseHas('companies', ['id' => $company->id]);

        $this->travel(61)->seconds();
        [$old] = $service->issue($company->id, $admin->id, CompanyDestructiveChallenge::PURPOSE_PERMANENT_DELETE);
        $this->travel(61)->seconds();
        [$new, $newOtp] = $service->issue($company->id, $admin->id, CompanyDestructiveChallenge::PURPOSE_PERMANENT_DELETE);
        $this->assertNotNull($old->fresh()->invalidated_at);

        $service->consume($new->id, $company->id, $admin->id, CompanyDestructiveChallenge::PURPOSE_PERMANENT_DELETE, $newOtp);
        $this->expectException(\RuntimeException::class);
        $service->consume($new->id, $company->id, $admin->id, CompanyDestructiveChallenge::PURPOSE_PERMANENT_DELETE, $newOtp);
    }

    public function test_expired_otp_cannot_delete_any_data(): void
    {
        $company = $this->company('Expired');
        $admin = $this->user(Role::SUPER_ADMIN_ID, null, 'super@example.test');
        [$challenge, $otp] = app(CompanyDestructiveChallengeService::class)->issue($company->id, $admin->id, CompanyDestructiveChallenge::PURPOSE_PERMANENT_DELETE);
        $challenge->update(['expires_at' => now()->subSecond()]);

        try {
            app(CompanyDestructiveChallengeService::class)->consume($challenge->id, $company->id, $admin->id, CompanyDestructiveChallenge::PURPOSE_PERMANENT_DELETE, $otp);
        } catch (\RuntimeException) {
        }
        $this->assertDatabaseHas('companies', ['id' => $company->id]);
    }

    public function test_company_a_permanent_delete_leaves_company_b_and_global_data_unchanged(): void
    {
        $companyA = $this->company('Company A');
        $companyB = $this->company('Company B');
        $superAdmin = $this->user(Role::SUPER_ADMIN_ID, null, 'super@example.test');
        $this->user(Role::SUPER_STAFF_ID, null, 'superstaff@example.test');
        $adminA = $this->user(Role::COMPANY_ADMIN_ID, $companyA->id, 'admin-a@example.test');
        $this->user(Role::COMPANY_STAFF_ID, $companyA->id, 'staff-a@example.test');
        $adminB = $this->user(Role::COMPANY_ADMIN_ID, $companyB->id, 'admin-b@example.test');
        $staffB = $this->user(Role::COMPANY_STAFF_ID, $companyB->id, 'staff-b@example.test');
        DB::table('customers')->insert([
            $this->customerRow($companyA->id, 'Customer A'),
            $this->customerRow($companyB->id, 'Customer B'),
        ]);
        DB::table('company_whatsapp_settings')->insert([
            ['company_id' => $companyA->id, 'is_enabled' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['company_id' => $companyB->id, 'is_enabled' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('company_cbms_api_configurations')->insert([
            ['company_id'=>$companyA->id,'environment'=>'test','encrypted_credential'=>'encrypted-a','configured_by'=>$adminA->id,'updated_by'=>$adminA->id,'created_at'=>now(),'updated_at'=>now()],
            ['company_id'=>$companyB->id,'environment'=>'test','encrypted_credential'=>'encrypted-b','configured_by'=>$adminB->id,'updated_by'=>$adminB->id,'created_at'=>now(),'updated_at'=>now()],
        ]);
        DB::table('sessions')->insert([
            ['id' => 'session-a', 'user_id' => $adminA->id, 'ip_address' => '127.0.0.1', 'user_agent' => 'test', 'payload' => 'x', 'last_activity' => time()],
            ['id' => 'session-b', 'user_id' => $adminB->id, 'ip_address' => '127.0.0.1', 'user_agent' => 'test', 'payload' => 'y', 'last_activity' => time()],
        ]);
        $companyBUpdatedAt = $companyB->updated_at->toDateTimeString();
        $pathA = config('dg-erp.destructive_files.public_company_root').'/'.$companyA->id;
        $pathB = config('dg-erp.destructive_files.public_company_root').'/'.$companyB->id;
        File::ensureDirectoryExists($pathA); File::put($pathA.'/a.txt', 'A');
        File::ensureDirectoryExists($pathB); File::put($pathB.'/b.txt', 'B');

        [$challenge, $otp] = app(CompanyDestructiveChallengeService::class)->issue($companyA->id, $superAdmin->id, CompanyDestructiveChallenge::PURPOSE_PERMANENT_DELETE);
        app(CompanyDestructiveChallengeService::class)->consume($challenge->id, $companyA->id, $superAdmin->id, CompanyDestructiveChallenge::PURPOSE_PERMANENT_DELETE, $otp);
        app(CompanyPermanentDeletionService::class)->delete($companyA, $superAdmin->id, $challenge->id);

        $this->assertDatabaseMissing('companies', ['id' => $companyA->id]);
        $this->assertDatabaseMissing('users', ['company_id' => $companyA->id]);
        $this->assertDatabaseMissing('customers', ['company_id' => $companyA->id]);
        $this->assertDatabaseMissing('company_whatsapp_settings', ['company_id' => $companyA->id]);
        $this->assertDatabaseMissing('company_cbms_api_configurations', ['company_id'=>$companyA->id]);
        $this->assertFalse(File::exists($pathA));

        $this->assertDatabaseHas('companies', ['id' => $companyB->id, 'company_name' => 'Company B']);
        $this->assertSame($companyBUpdatedAt, Company::findOrFail($companyB->id)->updated_at->toDateTimeString());
        $this->assertDatabaseHas('users', ['id' => $adminB->id, 'company_id' => $companyB->id]);
        $this->assertDatabaseHas('users', ['id' => $staffB->id, 'company_id' => $companyB->id]);
        $this->assertDatabaseHas('customers', ['company_id' => $companyB->id, 'name' => 'Customer B']);
        $this->assertDatabaseHas('sessions', ['id' => 'session-b']);
        $this->assertDatabaseHas('company_whatsapp_settings', ['company_id' => $companyB->id]);
        $this->assertDatabaseHas('company_cbms_api_configurations', ['company_id'=>$companyB->id,'encrypted_credential'=>'encrypted-b']);
        $this->assertTrue(File::exists($pathB.'/b.txt'));
        $this->assertDatabaseHas('users', ['id' => $superAdmin->id, 'company_id' => null]);
        $this->assertDatabaseHas('roles', ['id' => Role::COMPANY_ADMIN_ID]);
    }

    public function test_nepal_cbms_issued_invoice_blocks_permanent_delete_before_any_data_is_removed(): void
    {
        $country = Country::create(['name' => 'Nepal', 'iso_code' => 'NP', 'is_active' => true]);
        $company = $this->company('Protected Fiscal Company');
        $company->update(['country_id' => $country->id]);
        $admin = $this->user(Role::COMPANY_ADMIN_ID, $company->id, 'protected-admin@example.test');
        $superAdmin = $this->user(Role::SUPER_ADMIN_ID, null, 'protected-super@example.test');
        $financialYearId = DB::table('financial_years')->insertGetId([
            'company_id' => $company->id, 'name' => '2026', 'start_date' => '2026-01-01',
            'end_date' => '2026-12-31', 'is_active' => 1, 'is_closed' => 0, 'is_locked' => 0,
            'created_by' => $admin->id, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $customerId = DB::table('customers')->insertGetId($this->customerRow($company->id, 'Protected Customer'));
        $invoiceId = DB::table('sales_invoices')->insertGetId([
            'created_by' => $admin->id, 'company_id' => $company->id, 'financial_year_id' => $financialYearId,
            'customer_id' => $customerId, 'invoice_no' => 'SI-PROTECTED', 'sale_date' => '2026-06-15',
            'subtotal' => 100, 'discount' => 0, 'total_vat' => 0, 'grand_total' => 100,
            'paid_amount' => 0, 'due_amount' => 100, 'payment_status' => 'unpaid', 'status' => 1,
            'fiscal_issued_at' => now(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        CompanyIrdCbmsSetting::create(['company_id' => $company->id, 'is_enabled' => true, 'updated_by' => $admin->id]);

        try {
            app(CompanyPermanentDeletionService::class)->delete($company->fresh(), $superAdmin->id);
            $this->fail('Permanent deletion did not preserve the issued fiscal invoice.');
        } catch (\RuntimeException $e) {
            $this->assertSame(FiscalDocumentPolicyService::ISSUED_INVOICE_MUTATION_MESSAGE, $e->getMessage());
        }

        $this->assertDatabaseHas('companies', ['id' => $company->id]);
        $this->assertDatabaseHas('sales_invoices', ['id' => $invoiceId, 'status' => 1]);
        $this->assertDatabaseHas('customers', ['id' => $customerId]);
        $this->assertDatabaseHas('company_ird_cbms_settings', ['company_id' => $company->id, 'is_enabled' => 1]);
        $this->assertDatabaseHas('fiscal_document_audit_events', [
            'company_id' => $company->id,
            'document_type' => 'sales_invoice',
            'document_id' => $invoiceId,
            'event_type' => 'protected_company_deletion_blocked',
        ]);

        try {
            $company->fresh()->delete();
            $this->fail('Direct Company model deletion bypassed protected fiscal history.');
        } catch (\RuntimeException $e) {
            $this->assertSame(FiscalDocumentPolicyService::ISSUED_INVOICE_MUTATION_MESSAGE, $e->getMessage());
        }
        $this->assertDatabaseHas('companies', ['id' => $company->id]);
        $this->assertSame(2, DB::table('fiscal_document_audit_events')
            ->where('company_id', $company->id)
            ->where('document_id', $invoiceId)
            ->where('event_type', 'protected_company_deletion_blocked')->count());
    }

    private function company(string $name): Company
    {
        static $mobileSuffix = 0;
        $mobileSuffix++;
        return Company::create([
            'company_name' => $name,
            'email' => strtolower(str_replace(' ', '-', $name)).'@example.test',
            'mobile' => '980000'.str_pad((string) $mobileSuffix, 4, '0', STR_PAD_LEFT),
            'status' => 'active',
        ]);
    }

    private function user(int $roleId, ?int $companyId, string $email): User
    {
        return User::create(['name' => $email, 'email' => $email, 'password' => 'Password!123', 'role_id' => $roleId, 'company_id' => $companyId, 'account_status' => 'active']);
    }

    private function superStaffWithDeletePermission(): User
    {
        $user = $this->user(Role::SUPER_STAFF_ID, null, 'superstaff-delete@example.test');
        foreach (['platform_module_companies', 'platform_companies_delete'] as $name) {
            $permission = Permission::create(['name' => $name, 'scope' => Permission::SCOPE_PLATFORM]);
            $user->permissions()->attach($permission->id, ['is_allowed' => true]);
        }
        return $user;
    }

    private function customerRow(int $companyId, string $name): array
    {
        return ['company_id' => $companyId, 'name' => $name, 'status' => 'active', 'opening_balance' => 0, 'credit_days' => 0, 'current_balance' => 0, 'created_at' => now(), 'updated_at' => now()];
    }
}
