<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckSubscription;
use App\Http\Middleware\EnsureCompanyUser;
use App\Http\Middleware\UpdateLastSeen;
use App\Models\Company;
use App\Models\CompanyIrdCbmsSetting;
use App\Models\FiscalDocumentAuditEvent;
use App\Models\FinancialYear;
use App\Models\Role;
use App\Models\SalesInvoice;
use App\Models\SalesReturn;
use App\Models\User;
use App\Services\CompanyFactoryResetService;
use App\Services\CompanyPermanentDeletionService;
use App\Services\FiscalDocumentPolicyService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FiscalHistoryDeletionProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_four_fiscal_history_foreign_keys_are_restrictive(): void
    {
        $this->assertSame('RESTRICT', $this->deleteRule('sales_invoices', 'financial_year_id'));
        $this->assertSame('RESTRICT', $this->deleteRule('sales_returns', 'financial_year_id'));
        $this->assertSame('RESTRICT', $this->deleteRule('financial_years', 'company_id'));
        $this->assertSame('RESTRICT', $this->deleteRule('fiscal_document_audit_events', 'company_id'));
    }

    public function test_migration_down_restores_exact_previous_rules_and_up_is_reversible(): void
    {
        $migration = require database_path('migrations/2026_09_10_000014_restrict_fiscal_history_foreign_key_deletes.php');
        $migration->down();

        $this->assertSame('SET NULL', $this->deleteRule('sales_invoices', 'financial_year_id'));
        $this->assertSame('CASCADE', $this->deleteRule('sales_returns', 'financial_year_id'));
        $this->assertSame('CASCADE', $this->deleteRule('financial_years', 'company_id'));
        $this->assertSame('CASCADE', $this->deleteRule('fiscal_document_audit_events', 'company_id'));

        $migration->up();
        $this->assertSame('RESTRICT', $this->deleteRule('sales_invoices', 'financial_year_id'));
        $this->assertSame('RESTRICT', $this->deleteRule('sales_returns', 'financial_year_id'));
        $this->assertSame('RESTRICT', $this->deleteRule('financial_years', 'company_id'));
        $this->assertSame('RESTRICT', $this->deleteRule('fiscal_document_audit_events', 'company_id'));
    }

    public function test_fiscal_invoice_and_credit_note_block_all_material_fy_changes_and_delete_after_cbms_off(): void
    {
        [$company, $user, $fy] = $this->context('Protected FY');
        [$invoice, $return] = $this->fiscalDocuments($company, $user, $fy, true, true);
        CompanyIrdCbmsSetting::where('company_id', $company->id)->update(['is_enabled' => false]);
        $this->withoutCompanyMiddleware()->actingAs($user);

        foreach ([
            ['Protected FY Renamed', '2026-01-01', '2026-12-31'],
            ['Protected FY', '2026-01-02', '2026-12-31'],
            ['Protected FY', '2026-01-01', '2026-12-30'],
        ] as [$name, $start, $end]) {
            $this->from('/company/financial-years')->patch(route('company.financial-years.update', $fy), [
                'name' => $name, 'start_date' => $start, 'end_date' => $end,
            ])->assertRedirect('/company/financial-years')
                ->assertSessionHas('error', 'This fiscal year contains issued fiscal documents and cannot be materially changed.');
        }

        $this->from('/company/financial-years')->delete(route('company.financial-years.destroy', $fy))
            ->assertRedirect('/company/financial-years')
            ->assertSessionHas('error', 'This fiscal year contains issued fiscal documents and cannot be deleted.');

        $this->assertDatabaseHas('financial_years', ['id' => $fy->id, 'name' => 'Protected FY', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        $this->assertDatabaseHas('sales_invoices', ['id' => $invoice->id, 'financial_year_id' => $fy->id, 'invoice_no' => 'SI-PROTECTED']);
        $this->assertDatabaseHas('sales_returns', ['id' => $return->id, 'financial_year_id' => $fy->id, 'return_no' => 'CN-PROTECTED']);

        try {
            $fy->fresh()->update(['name' => 'Direct Model Forgery']);
            $this->fail('Direct FinancialYear model update bypassed fiscal protection.');
        } catch (\RuntimeException) {
            $this->addToAssertionCount(1);
        }
        try {
            $fy->fresh()->delete();
            $this->fail('Direct FinancialYear model delete bypassed fiscal protection.');
        } catch (\RuntimeException) {
            $this->addToAssertionCount(1);
        }
    }

    public function test_credit_note_only_and_audit_only_evidence_block_company_destructive_services(): void
    {
        [$creditCompany, $creditUser, $creditFy] = $this->context('Credit Only');
        [, $creditNote] = $this->fiscalDocuments($creditCompany, $creditUser, $creditFy, false, true);
        $this->assertTrue(app(FiscalDocumentPolicyService::class)->companyHasPermanentFiscalHistory($creditCompany));
        $this->assertBlocked(fn () => app(CompanyFactoryResetService::class)->reset($creditCompany, $creditUser->id, 'session-credit'));
        $this->assertBlocked(fn () => app(CompanyPermanentDeletionService::class)->delete($creditCompany, $creditUser->id));
        $this->assertDatabaseHas('sales_returns', ['id' => $creditNote->id]);

        [$auditCompany, $auditUser] = $this->context('Audit Only');
        FiscalDocumentAuditEvent::create(['company_id' => $auditCompany->id, 'document_type' => 'sales_invoice', 'document_id' => 999999, 'document_number' => 'SI-MISSING', 'event_type' => 'invoice_issued', 'actor_id' => $auditUser->id, 'event_at' => now()]);
        $this->assertTrue(app(FiscalDocumentPolicyService::class)->companyHasPermanentFiscalHistory($auditCompany));
        $this->assertBlocked(fn () => app(CompanyFactoryResetService::class)->reset($auditCompany, $auditUser->id, 'session-audit'));
        $this->assertBlocked(fn () => app(CompanyPermanentDeletionService::class)->delete($auditCompany, $auditUser->id));
        $this->assertDatabaseHas('fiscal_document_audit_events', ['company_id' => $auditCompany->id, 'document_number' => 'SI-MISSING']);
    }

    public function test_non_fiscal_fy_preserves_legacy_detach_delete_update_and_company_isolation(): void
    {
        [$company, $user, $fy] = $this->context('Legacy FY');
        [$invoice, $return] = $this->fiscalDocuments($company, $user, $fy, false, false);
        [, , $foreignFy] = $this->context('Foreign FY');
        $this->withoutCompanyMiddleware()->actingAs($user);

        $this->patch(route('company.financial-years.update', $fy), [
            'name' => 'Legacy FY Updated', 'start_date' => '2026-01-02', 'end_date' => '2026-12-30',
        ])->assertRedirect(route('company.financial-years.index'));
        $fy->refresh()->update(['is_active' => false]);
        $this->delete(route('company.financial-years.destroy', $fy))->assertRedirect();

        $this->assertDatabaseMissing('financial_years', ['id' => $fy->id]);
        $this->assertDatabaseHas('sales_invoices', ['id' => $invoice->id, 'financial_year_id' => null]);
        $this->assertDatabaseMissing('sales_returns', ['id' => $return->id]);
        $this->patch(route('company.financial-years.update', $foreignFy), ['name' => 'Forged', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31'])->assertNotFound();
        $this->delete(route('company.financial-years.destroy', $foreignFy))->assertNotFound();
    }

    public function test_low_level_deletes_cannot_detach_invoice_cascade_credit_note_or_erase_audit(): void
    {
        [$company, $user, $fy] = $this->context('Database Restriction');
        [$invoice, $return] = $this->fiscalDocuments($company, $user, $fy, true, true);
        FiscalDocumentAuditEvent::create(['company_id' => $company->id, 'document_type' => 'sales_invoice', 'document_id' => $invoice->id, 'document_number' => $invoice->invoice_no, 'event_type' => 'invoice_issued', 'actor_id' => $user->id, 'event_at' => now()]);

        try {
            DB::table('financial_years')->where('id', $fy->id)->delete();
            $this->fail('Low-level FY deletion bypassed restrictive fiscal foreign keys.');
        } catch (QueryException) {
            $this->addToAssertionCount(1);
        }
        try {
            DB::table('companies')->where('id', $company->id)->delete();
            $this->fail('Low-level Company deletion bypassed restrictive fiscal foreign keys.');
        } catch (QueryException) {
            $this->addToAssertionCount(1);
        }

        $this->assertDatabaseHas('financial_years', ['id' => $fy->id]);
        $this->assertDatabaseHas('sales_invoices', ['id' => $invoice->id, 'financial_year_id' => $fy->id, 'invoice_no' => 'SI-PROTECTED']);
        $this->assertDatabaseHas('sales_returns', ['id' => $return->id, 'financial_year_id' => $fy->id, 'return_no' => 'CN-PROTECTED']);
        $this->assertDatabaseHas('fiscal_document_audit_events', ['company_id' => $company->id, 'event_type' => 'invoice_issued']);
    }

    private function context(string $fyName): array
    {
        DB::table('roles')->insertOrIgnore(['id' => Role::COMPANY_ADMIN_ID, 'name' => 'company_admin', 'created_at' => now(), 'updated_at' => now()]);
        $company = Company::create(['company_name' => $fyName, 'email' => strtolower(str_replace(' ', '-', $fyName)).uniqid().'@example.test', 'mobile' => '98'.random_int(10000000, 99999999), 'status' => 'active']);
        $user = User::create(['name' => $fyName, 'email' => 'user-'.uniqid().'@example.test', 'password' => Hash::make('password'), 'role_id' => Role::COMPANY_ADMIN_ID, 'company_id' => $company->id, 'account_status' => 'active']);
        $fy = FinancialYear::create(['company_id' => $company->id, 'name' => $fyName, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => false, 'created_by' => $user->id]);
        CompanyIrdCbmsSetting::create(['company_id' => $company->id, 'is_enabled' => true, 'updated_by' => $user->id]);
        return [$company, $user, $fy];
    }

    private function fiscalDocuments(Company $company, User $user, FinancialYear $fy, bool $issuedInvoice, bool $issuedReturn): array
    {
        $customer = DB::table('customers')->insertGetId(['company_id' => $company->id, 'created_by' => $user->id, 'name' => 'Protected Buyer', 'opening_balance' => 0, 'current_balance' => 0, 'credit_days' => 0, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        $invoice = SalesInvoice::create(['created_by' => $user->id, 'company_id' => $company->id, 'financial_year_id' => $fy->id, 'customer_id' => $customer, 'invoice_no' => 'SI-PROTECTED', 'sale_date' => '2026-06-01', 'subtotal' => 100, 'discount' => 0, 'total_vat' => 0, 'grand_total' => 100, 'paid_amount' => 0, 'due_amount' => 100, 'payment_status' => 'unpaid', 'status' => 1]);
        if ($issuedInvoice) $invoice->forceFill(['fiscal_issued_at' => now()])->save();
        $return = SalesReturn::create(['company_id' => $company->id, 'financial_year_id' => $fy->id, 'sales_invoice_id' => $invoice->id, 'customer_id' => $customer, 'return_no' => 'CN-PROTECTED', 'return_date' => '2026-06-02', 'subtotal' => 10, 'total_vat' => 0, 'grand_total' => 10, 'adjust_amount' => 0, 'refund_amount' => 10, 'note' => 'Fiscal correction', 'status' => 1]);
        if ($issuedReturn) $return->forceFill(['fiscal_issued_at' => now()])->save();
        return [$invoice->fresh(), $return->fresh()];
    }

    private function deleteRule(string $table, string $column): string
    {
        $row = collect(DB::select("PRAGMA foreign_key_list('{$table}')"))->first(fn ($fk) => $fk->from === $column);
        $this->assertNotNull($row, "Missing {$table}.{$column} foreign key.");
        return strtoupper($row->on_delete);
    }

    private function withoutCompanyMiddleware(): self
    {
        return $this->withoutMiddleware([EnsureCompanyUser::class, CheckSubscription::class, UpdateLastSeen::class, VerifyCsrfToken::class]);
    }

    private function assertBlocked(callable $operation): void
    {
        try {
            $operation();
            $this->fail('Permanent fiscal history was not protected.');
        } catch (\RuntimeException) {
            $this->addToAssertionCount(1);
        }
    }
}
