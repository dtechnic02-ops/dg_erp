<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckSubscription;
use App\Models\CbmsTransmission;
use App\Models\Company;
use App\Models\CompanyCbmsApiConfiguration;
use App\Models\CompanyIrdCbmsSetting;
use App\Models\Country;
use App\Models\FinancialYear;
use App\Models\Role;
use App\Models\SalesInvoice;
use App\Models\SalesReturn;
use App\Models\User;
use App\Services\Cbms\CbmsBillReturnPayloadBuilder;
use App\Services\Cbms\CbmsBillReturnResponseParser;
use App\Services\Cbms\CbmsFiscalYearFormatter;
use App\Services\Cbms\CbmsHttpTransport;
use App\Services\Cbms\CbmsReadinessResult;
use App\Services\Cbms\CbmsReadinessService;
use App\Services\Cbms\CbmsRealtimeClassifier;
use App\Services\Cbms\CbmsSalesBillPayloadBuilder;
use App\Services\Cbms\CbmsSalesResponseParser;
use App\Services\Cbms\CbmsTransmissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use LogicException;
use Tests\TestCase;

class CbmsPhaseOneFoundationTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private FinancialYear $financialYear;
    private User $admin;
    private User $auditor;
    private User $staff;
    private int $customerId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(CheckSubscription::class);
        foreach ([1 => 'super_admin', 2 => 'company_admin', 3 => 'staff', 6 => 'auditor'] as $id => $name) DB::table('roles')->insertOrIgnore(compact('id', 'name'));
        $nepal = Country::create(['name' => 'Nepal', 'iso_code' => 'NP', 'is_active' => true]);
        $this->company = Company::create(['company_name' => 'Frozen Seller', 'mobile' => '9800000000', 'email' => uniqid().'@test.local', 'status' => 'active', 'country_id' => $nepal->id, 'pan_number' => '123456789']);
        $this->financialYear = FinancialYear::create(['company_id' => $this->company->id, 'name' => '2081/82', 'start_date' => '2024-04-13', 'end_date' => '2025-04-14', 'is_active' => true]);
        $this->admin = $this->user('Admin', Role::COMPANY_ADMIN_ID);
        $this->auditor = $this->user('Auditor', Role::AUDITOR_ID);
        $this->staff = $this->user('Staff', Role::COMPANY_STAFF_ID);
        $this->customerId = DB::table('customers')->insertGetId(['company_id' => $this->company->id, 'name' => 'Frozen Buyer', 'tax_no' => '987654321', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        CompanyCbmsApiConfiguration::create(['company_id' => $this->company->id, 'environment' => 'test', 'client_identifier' => 'taxpayer-user', 'encrypted_credential' => 'phase-one-test-password', 'configured_by' => $this->admin->id, 'updated_by' => $this->admin->id]);
        CompanyIrdCbmsSetting::create(['company_id' => $this->company->id, 'is_enabled' => true, 'updated_by' => $this->admin->id]);
    }

    public function test_pure_vat_and_mixed_payloads_use_frozen_evidence_and_grand_total(): void
    {
        $pure = $this->invoice('SI-PURE', 1000, 130, 1130);
        $this->line($pure, 'vat_taxable', 1000, 130, 1130);
        $attempt = Carbon::parse('2024-04-13 10:04:59');
        $pure->update(['fiscal_issued_at' => '2024-04-13 10:00:00']);
        $payload = app(CbmsSalesBillPayloadBuilder::class)->build($pure->fresh(), $this->company->id, $attempt)['payload'];
        $this->assertSame(1130.0, $payload['total_sales']);
        $this->assertSame(1000.0, $payload['taxable_sales_vat']);
        $this->assertSame(130.0, $payload['vat']);
        $this->assertTrue($payload['isrealtime']);
        $this->assertSame('2024-04-13 10:04:59', $payload['datetimeClient']);
        $this->assertSame('2081.01.01', $payload['invoice_date']);

        $mixed = $this->invoice('SI-MIXED', 1012500, 130325, 1142825);
        $this->line($mixed, 'vat_taxable', 1002500, 130325, 1132825);
        $this->line($mixed, 'vat_exempt', 10000, 0, 10000);
        $payload = app(CbmsSalesBillPayloadBuilder::class)->build($mixed->fresh(), $this->company->id, Carbon::parse('2024-04-13 10:10:01'))['payload'];
        $this->assertSame(1142825.0, $payload['total_sales']);
        $this->assertSame(1002500.0, $payload['taxable_sales_vat']);
        $this->assertSame(10000.0, $payload['tax_exempted_sales']);
        $this->assertSame(130325.0, $payload['vat']);
        $this->assertFalse($payload['isrealtime']);

        DB::table('companies')->where('id', $this->company->id)->update(['company_name' => 'Changed Seller', 'pan_number' => '999999999']);
        DB::table('customers')->where('id', $this->customerId)->update(['name' => 'Changed Buyer', 'tax_no' => '111111111']);
        $payload = app(CbmsSalesBillPayloadBuilder::class)->build($mixed->fresh(), $this->company->id, Carbon::parse('2024-04-13 10:01:00'))['payload'];
        $this->assertSame('123456789', $payload['seller_pan']);
        $this->assertSame('Frozen Buyer', $payload['buyer_name']);
    }

    public function test_explicit_export_maps_but_unsupported_classifications_fail_closed(): void
    {
        $export = $this->invoice('SI-EXPORT', 500, 0, 500);
        $this->line($export, 'export', 500, 0, 500);
        $result = app(CbmsSalesBillPayloadBuilder::class)->build($export->fresh(), $this->company->id, now());
        $this->assertSame('READY', $result['readiness']['status']);
        $this->assertSame(500.0, $result['payload']['export_sales']);

        foreach (['zero_rated', 'out_of_scope', 'legacy_unclassified'] as $classification) {
            $invoice = $this->invoice('SI-'.strtoupper($classification), 200, 0, 200);
            $this->line($invoice, $classification, 200, 0, 200);
            $result = app(CbmsSalesBillPayloadBuilder::class)->build($invoice->fresh(), $this->company->id, now());
            $this->assertSame('NOT_READY', $result['readiness']['status']);
            $this->assertContains(CbmsReadinessService::UNRESOLVED_CBMS_TAX_CLASSIFICATION, $result['readiness']['reason_codes']);
            $this->assertNull($result['payload']);
        }
    }

    public function test_fiscal_year_date_and_realtime_rules_are_isolated_and_deterministic(): void
    {
        $this->assertSame('2081.082', app(CbmsFiscalYearFormatter::class)->format($this->financialYear));
        $classifier = app(CbmsRealtimeClassifier::class);
        $issued = Carbon::parse('2026-09-11 12:00:00');
        $this->assertTrue($classifier->isRealtime($issued, $issued->copy()->addSeconds(300)));
        $this->assertFalse($classifier->isRealtime($issued, $issued->copy()->addSeconds(301)));
        $this->assertFalse($classifier->isRealtime($issued, $issued->copy()->subSecond()));
    }

    public function test_credit_note_uses_original_reference_own_amounts_date_and_reason(): void
    {
        $invoice = $this->invoice('SI-ORIGINAL', 1000, 130, 1130);
        $salesItem = $this->line($invoice, 'vat_taxable', 1000, 130, 1130);
        CbmsTransmission::create(['company_id' => $this->company->id, 'transmittable_type' => $invoice->getMorphClass(), 'transmittable_id' => $invoice->id, 'endpoint_type' => CbmsTransmission::ENDPOINT_BILL, 'environment' => CbmsTransmission::ENVIRONMENT_TEST, 'transport_kind' => CbmsTransmission::TRANSPORT_DISABLED, 'status' => CbmsTransmission::STATUS_SUBMITTED, 'attempt_count' => 1, 'payload_hash' => str_repeat('a', 64), 'submitted_at' => now()]);
        $return = SalesReturn::create(['company_id' => $this->company->id, 'financial_year_id' => $this->financialYear->id, 'sales_invoice_id' => $invoice->id, 'return_no' => 'CN-0001', 'return_date' => '2024-04-13', 'subtotal' => 400, 'total_vat' => 52, 'grand_total' => 452, 'note' => 'Partial quantity returned', 'fiscal_issued_at' => '2024-04-13 11:00:00', 'status' => 1]);
        DB::table('sales_return_items')->insert(['company_id' => $this->company->id, 'financial_year_id' => $this->financialYear->id, 'sales_return_id' => $return->id, 'sales_item_id' => $salesItem, 'quantity' => 1, 'unit_price' => 400, 'vat_rate' => 13, 'vat_amount' => 52, 'fiscal_discount_amount' => 0, 'fiscal_net_base' => 400, 'tax_classification' => 'vat_taxable', 'total_price' => 452, 'status' => 1]);
        $payload = app(CbmsBillReturnPayloadBuilder::class)->build($return->fresh(), $this->company->id, Carbon::parse('2024-04-13 11:02:00'))['payload'];
        $this->assertSame('SI-ORIGINAL', $payload['ref_invoice_number']);
        $this->assertSame('CN-0001', $payload['credit_note_number']);
        $this->assertSame('2081.01.01', $payload['credit_note_date']);
        $this->assertSame('Partial quantity returned', $payload['reason_for_return']);
        $this->assertSame(452.0, $payload['total_sales']);
        $this->assertSame(400.0, $payload['taxable_sales_vat']);
        $this->assertSame(52.0, $payload['vat']);

        $return->update(['note' => null]);
        $notReady = app(CbmsBillReturnPayloadBuilder::class)->build($return->fresh(), $this->company->id, now());
        $this->assertContains(CbmsReadinessService::MISSING_RETURN_REASON, $notReady['readiness']['reason_codes']);
        $this->assertNull($notReady['payload']);
    }

    public function test_credentials_are_encrypted_hidden_masked_and_never_persisted_in_payload_evidence(): void
    {
        $configuration = $this->company->cbmsApiConfiguration;
        $this->assertNotSame('phase-one-test-password', DB::table('company_cbms_api_configurations')->where('id', $configuration->id)->value('encrypted_credential'));
        $this->assertArrayNotHasKey('encrypted_credential', $configuration->toArray());
        $this->actingAs($this->admin)->get(route('company.settings.cbms-api.edit'))->assertOk()->assertSee('************')->assertDontSee('phase-one-test-password');

        $invoice = $this->invoice('SI-REDACT', 100, 13, 113);
        $this->line($invoice, 'vat_taxable', 100, 13, 113);
        $built = app(CbmsSalesBillPayloadBuilder::class)->build($invoice->fresh(), $this->company->id, now());
        $transmission = app(CbmsTransmissionService::class)->record($invoice, CbmsTransmission::ENDPOINT_BILL, $built['payload'], new CbmsReadinessResult);
        $stored = json_encode($transmission->toArray());
        $this->assertStringNotContainsString('phase-one-test-password', $stored);
        $this->assertStringNotContainsString('taxpayer-user', $stored);
    }

    public function test_response_parsers_are_endpoint_specific_and_never_fake_success(): void
    {
        $sales = app(CbmsSalesResponseParser::class);
        $returns = app(CbmsBillReturnResponseParser::class);
        $this->assertSame(CbmsTransmission::STATUS_SUBMITTED, $sales->parse(200)['status']);
        $this->assertSame(CbmsTransmission::STATUS_DUPLICATE_REQUIRES_RECONCILIATION, $sales->parse(101)['status']);
        foreach ([100, 102, 103, 104] as $code) $this->assertNotSame(CbmsTransmission::STATUS_SUBMITTED, $sales->parse($code)['status']);
        $this->assertSame(CbmsTransmission::STATUS_SUBMITTED, $returns->parse(200)['status']);
        $this->assertSame('ambiguous_bill_return_101', $returns->parse(101)['category']);
        $this->assertSame('referenced_bill_not_found', $returns->parse(105)['category']);
        foreach ([100, 102, 103, 104] as $code) $this->assertNotSame(CbmsTransmission::STATUS_SUBMITTED, $returns->parse($code)['status']);

        $this->assertSame(CbmsTransmission::STATUS_RETRYABLE_FAILURE, $sales->parse('unexpected')['status']);
        $this->assertSame(CbmsTransmission::STATUS_RETRYABLE_FAILURE, $returns->parse('unexpected')['status']);
    }

    public function test_transmission_identity_is_idempotent_company_scoped_and_status_is_admin_auditor_read_only(): void
    {
        $invoice = $this->invoice('SI-IDEMPOTENT', 100, 13, 113);
        $this->line($invoice, 'vat_taxable', 100, 13, 113);
        $built = app(CbmsSalesBillPayloadBuilder::class)->build($invoice->fresh(), $this->company->id, now());
        $service = app(CbmsTransmissionService::class);
        $first = $service->record($invoice, CbmsTransmission::ENDPOINT_BILL, $built['payload'], new CbmsReadinessResult);
        $second = $service->record($invoice, CbmsTransmission::ENDPOINT_BILL, $built['payload'], new CbmsReadinessResult);
        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, CbmsTransmission::count());
        $this->actingAs($this->admin)->get(route('company.settings.cbms-transmissions.index'))->assertOk()->assertSee('SI-IDEMPOTENT', false);
        $this->actingAs($this->auditor)->get(route('company.settings.cbms-transmissions.index'))->assertOk()->assertDontSee('phase-one-test-password');
        $this->actingAs($this->staff)->get(route('company.settings.cbms-transmissions.index'))->assertForbidden();

        $otherCountry = Country::create(['name' => 'Other', 'iso_code' => 'AE', 'is_active' => true]);
        $other = Company::create(['company_name' => 'Other', 'mobile' => '1', 'email' => uniqid().'@test.local', 'status' => 'active', 'country_id' => $otherCountry->id]);
        $this->admin->update(['company_id' => $other->id]);
        $this->actingAs($this->admin->fresh())->get(route('company.settings.cbms-transmissions.index', ['company_id' => $this->company->id]))->assertOk()->assertDontSee('SI-IDEMPOTENT', false);
    }

    public function test_phase_one_transport_cannot_call_real_http(): void
    {
        Http::fake();
        $this->expectException(LogicException::class);
        try { app(CbmsHttpTransport::class)->post(CbmsTransmission::ENDPOINT_BILL, ['password' => 'never-send']); }
        finally { Http::assertNothingSent(); }
    }

    private function invoice(string $number, float $subtotal, float $vat, float $total): SalesInvoice
    {
        $id = DB::table('sales_invoices')->insertGetId(['created_by' => $this->admin->id, 'company_id' => $this->company->id, 'financial_year_id' => $this->financialYear->id, 'customer_id' => $this->customerId, 'invoice_no' => $number, 'sale_date' => '2024-04-13', 'subtotal' => $subtotal, 'discount' => 0, 'total_vat' => $vat, 'grand_total' => $total, 'paid_amount' => 0, 'due_amount' => $total, 'payment_status' => 'unpaid', 'status' => 1, 'fiscal_snapshot_captured_at' => now(), 'fiscal_issued_at' => '2024-04-13 10:00:00', 'seller_name_snapshot' => 'Frozen Seller', 'seller_pan_snapshot' => '123456789', 'buyer_name_snapshot' => 'Frozen Buyer', 'buyer_tax_no_snapshot' => '987654321', 'created_at' => now(), 'updated_at' => now()]);
        return SalesInvoice::findOrFail($id);
    }

    private function line(SalesInvoice $invoice, string $classification, float $net, float $vat, float $total): int
    {
        return DB::table('sales_items')->insertGetId(['created_by' => $this->admin->id, 'company_id' => $this->company->id, 'financial_year_id' => $this->financialYear->id, 'sales_invoice_id' => $invoice->id, 'item_type' => 'service', 'service_id' => null, 'quantity' => 1, 'returned_qty' => 0, 'unit_price' => $net, 'vat_rate' => $vat > 0 ? 13 : 0, 'vat_amount' => $vat, 'fiscal_discount_amount' => 0, 'fiscal_net_base' => $net, 'tax_classification' => $classification, 'total_price' => $total, 'item_name_snapshot' => 'Frozen Item', 'unit_name_snapshot' => 'Service', 'created_at' => now(), 'updated_at' => now()]);
    }

    private function user(string $name, int $role): User
    {
        return User::create(['name' => $name, 'email' => strtolower($name).uniqid().'@test.local', 'password' => Hash::make('password'), 'role_id' => $role, 'company_id' => $this->company->id, 'account_status' => 'active']);
    }
}
