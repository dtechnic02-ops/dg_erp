<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckSubscription;
use App\Jobs\TransmitCbmsDocumentJob;
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
use App\Services\Cbms\CbmsHttpTransport;
use App\Services\Cbms\CbmsQueueService;
use App\Services\Cbms\CbmsReconciliationResult;
use App\Services\Cbms\CbmsResponseCodeExtractor;
use App\Services\Cbms\CbmsReconciliationService;
use App\Services\Cbms\CbmsReconciliationVerifier;
use App\Services\Cbms\CbmsTransmissionProcessor;
use App\Services\Cbms\CbmsTransmissionStateMachine;
use App\Services\Cbms\CbmsTransportResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use LogicException;
use RuntimeException;
use Tests\Fakes\FakeCbmsReconciliationVerifier;
use Tests\Fakes\FakeCbmsTransport;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class CbmsPhaseOneOperationalTest extends TestCase
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
        $this->company = Company::create(['company_name' => 'CBMS Seller', 'mobile' => '9800000000', 'email' => uniqid().'@test.local', 'status' => 'active', 'country_id' => $nepal->id, 'pan_number' => '123456789']);
        $this->financialYear = FinancialYear::create(['company_id' => $this->company->id, 'name' => '2081/82', 'start_date' => '2024-04-13', 'end_date' => '2025-04-14', 'is_active' => true]);
        $this->admin = $this->user('Admin', Role::COMPANY_ADMIN_ID);
        $this->auditor = $this->user('Auditor', Role::AUDITOR_ID);
        $this->staff = $this->user('Staff', Role::COMPANY_STAFF_ID);
        $this->customerId = DB::table('customers')->insertGetId(['company_id' => $this->company->id, 'name' => 'Buyer', 'tax_no' => '987654321', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        CompanyIrdCbmsSetting::create(['company_id' => $this->company->id, 'is_enabled' => true, 'updated_by' => $this->admin->id]);
        CompanyCbmsApiConfiguration::create(['company_id' => $this->company->id, 'environment' => 'test', 'client_identifier' => 'cbms-user', 'encrypted_credential' => 'top-secret', 'configured_by' => $this->admin->id, 'updated_by' => $this->admin->id]);
    }

    public function test_queue_is_idempotent_and_dispatches_only_once(): void
    {
        Queue::fake();
        $invoice = $this->invoice('SI-Q');
        $first = app(CbmsQueueService::class)->queue($invoice);
        $second = app(CbmsQueueService::class)->queue($invoice);
        $this->assertSame($first->id, $second->id);
        $this->assertSame(CbmsTransmission::STATUS_QUEUED, $second->status);
        $this->assertSame(1, CbmsTransmission::count());
        Queue::assertPushed(TransmitCbmsDocumentJob::class, 1);
    }

    public function test_after_commit_dispatches_and_rollback_does_not(): void
    {
        Queue::fake();
        $invoice = $this->invoice('SI-COMMIT');
        DB::transaction(fn () => app(CbmsQueueService::class)->queueAfterCommit($invoice));
        Queue::assertPushed(TransmitCbmsDocumentJob::class, 1);
        $rolledBack = $this->invoice('SI-ROLLBACK');
        try {
            DB::transaction(function () use ($rolledBack): void { app(CbmsQueueService::class)->queueAfterCommit($rolledBack); throw new RuntimeException('rollback'); });
        } catch (RuntimeException) {}
        Queue::assertPushed(TransmitCbmsDocumentJob::class, 1);
        $this->assertDatabaseMissing('cbms_transmissions', ['transmittable_id' => $rolledBack->id]);
    }

    #[DataProvider('responseCases')]
    public function test_endpoint_response_and_network_matrix(int $http, ?string $code, string $classification, string $expected): void
    {
        Queue::fake();
        $transmission = app(CbmsQueueService::class)->queue($this->invoice('SI-'.uniqid()));
        $fake = (new FakeCbmsTransport)->push(new CbmsTransportResult($classification, now(), $http, $code, $code === null ? '<html>bad</html>' : $code));
        $this->app->instance(CbmsHttpTransport::class, $fake);
        $result = app(CbmsTransmissionProcessor::class)->process($transmission->id);
        $this->assertSame($expected, $result->transmission->status);
        $this->assertSame(1, $result->transmission->attempt_count);
        $this->assertCount(1, $fake->calls);
        $this->assertDatabaseHas('cbms_transmission_attempts', ['cbms_transmission_id' => $transmission->id, 'result_status' => $expected]);
    }

    public static function responseCases(): array
    {
        return [
            'success' => [200, '200', 'response', 'submitted'],
            'sales duplicate' => [200, '101', 'response', 'duplicate_requires_reconciliation'],
            'saving exception' => [200, '102', 'response', 'retryable_failure'],
            'unknown api issue' => [200, '103', 'response', 'retryable_failure'],
            'credential failure' => [200, '100', 'response', 'permanent_failure'],
            'invalid model' => [200, '104', 'response', 'permanent_failure'],
            'unknown response code' => [200, '999', 'response', 'retryable_failure'],
            'empty body' => [200, null, 'response', 'retryable_failure'],
            'malformed body' => [200, null, 'malformed_response', 'retryable_failure'],
            'http 404' => [404, null, 'response', 'permanent_failure'],
            'http 408' => [408, null, 'response', 'retryable_failure'],
            'http 429' => [429, null, 'response', 'retryable_failure'],
            'http 500' => [500, null, 'response', 'retryable_failure'],
            'http 502' => [502, null, 'response', 'retryable_failure'],
            'http 503' => [503, null, 'response', 'retryable_failure'],
            'timeout' => [0, null, 'timeout', 'retryable_failure'],
            'connection refused' => [0, null, 'connection_error', 'retryable_failure'],
            'dns failure' => [0, null, 'dns_error', 'retryable_failure'],
            'unexpected exception' => [0, null, 'unexpected_exception', 'retryable_failure'],
            'delayed valid response' => [200, '200', 'response', 'submitted'],
        ];
    }

    #[DataProvider('responseBodyCases')]
    public function test_only_unambiguous_plain_or_explicit_json_response_codes_are_extracted(?string $body, ?string $expected): void
    {
        $this->assertSame($expected, app(CbmsResponseCodeExtractor::class)->extract($body));
    }

    public static function responseBodyCases(): array
    {
        return [
            'numeric plain text' => ['200', '200'],
            'json scalar' => ['101', '101'],
            'json response code' => ['{"response_code":102}', '102'],
            'json camel response code' => ['{"responseCode":"103"}', '103'],
            'json code' => ['{"code":104}', '104'],
            'html' => ['<html>500</html>', null],
            'malformed json' => ['{"code":', null],
            'empty' => ['', null],
        ];
    }

    #[DataProvider('forbiddenTransitionCases')]
    public function test_state_machine_rejects_invalid_transitions(string $from, string $to): void
    {
        $transmission = $this->transmission($from);
        $this->expectException(LogicException::class);
        app(CbmsTransmissionStateMachine::class)->move($transmission, $to);
    }

    public static function forbiddenTransitionCases(): array
    {
        return [
            ['submitted', 'pending'], ['submitted', 'queued'], ['permanent_failure', 'submitted'],
            ['pending', 'submitted'], ['not_ready', 'submitted'], ['queued', 'submitted'],
            ['retryable_failure', 'submitted'], ['processing', 'pending'],
            ['duplicate_requires_reconciliation', 'submitted'],
        ];
    }

    #[DataProvider('unsupportedClassifications')]
    public function test_unsupported_classifications_fail_closed(string $classification): void
    {
        Queue::fake();
        $invoice = $this->invoice('SI-UNSUPPORTED-'.uniqid(), $classification, 0);
        $transmission = app(CbmsQueueService::class)->queue($invoice);
        $this->assertSame(CbmsTransmission::STATUS_NOT_READY, $transmission->status);
        $this->assertContains('UNRESOLVED_CBMS_TAX_CLASSIFICATION', $transmission->response_body_redacted['reason_codes']);
        Queue::assertNothingPushed();
    }

    public static function unsupportedClassifications(): array
    {
        return [['zero_rated'], ['out_of_scope'], ['legacy_unclassified']];
    }

    public function test_hard_disabled_transport_never_attempts_http(): void
    {
        Http::fake();
        $this->expectException(LogicException::class);
        try { app(CbmsHttpTransport::class)->send(CbmsTransmission::ENDPOINT_BILL, ['password' => 'never']); }
        finally { Http::assertNothingSent(); }
    }

    public function test_attempt_evidence_is_bounded_recursive_redacted_and_append_only(): void
    {
        Queue::fake();
        config(['cbms.max_response_excerpt_bytes' => 80]);
        $transmission = app(CbmsQueueService::class)->queue($this->invoice('SI-EVIDENCE'));
        $body = 'password=hidden token=hidden '.str_repeat('x', 500);
        $fake = (new FakeCbmsTransport)->push(CbmsTransportResult::response(now(), 200, '102', $body));
        $this->app->instance(CbmsHttpTransport::class, $fake);
        app(CbmsTransmissionProcessor::class)->process($transmission->id);
        $attempt = $transmission->attempts()->firstOrFail();
        $this->assertLessThanOrEqual(80, strlen($attempt->response_excerpt_redacted));
        $this->assertStringNotContainsString('hidden', $attempt->response_excerpt_redacted);
        $this->assertStringNotContainsString('top-secret', json_encode($attempt->toArray()));
        $this->expectException(LogicException::class);
        $attempt->update(['result_status' => 'submitted']);
    }

    public function test_admin_can_operate_auditor_is_get_only_staff_and_other_company_are_denied(): void
    {
        Queue::fake();
        $transmission = app(CbmsQueueService::class)->queue($this->invoice('SI-AUTH'));
        $this->actingAs($this->admin)->get(route('company.settings.cbms-transmissions.show', $transmission))->assertOk()->assertDontSee('top-secret');
        $this->actingAs($this->auditor)->get(route('company.settings.cbms-transmissions.show', $transmission))->assertOk()->assertDontSee('top-secret');
        $this->actingAs($this->auditor)->post(route('company.settings.cbms-transmissions.retry', $transmission))->assertForbidden();
        $this->actingAs($this->staff)->get(route('company.settings.cbms-transmissions.show', $transmission))->assertForbidden();
        $other = Company::create(['company_name' => 'Other', 'mobile' => '1', 'email' => uniqid().'@test.local', 'status' => 'active', 'country_id' => $this->company->country_id]);
        $otherAdmin = User::create(['name' => 'Other', 'email' => uniqid().'@test.local', 'password' => Hash::make('password'), 'role_id' => Role::COMPANY_ADMIN_ID, 'company_id' => $other->id, 'account_status' => 'active']);
        $this->actingAs($otherAdmin)->get(route('company.settings.cbms-transmissions.show', $transmission))->assertNotFound();
    }

    public function test_duplicate_needs_exact_external_verification_and_has_no_manual_success(): void
    {
        $transmission = $this->transmission(CbmsTransmission::STATUS_DUPLICATE_REQUIRES_RECONCILIATION);
        $this->app->instance(CbmsReconciliationVerifier::class, new FakeCbmsReconciliationVerifier(new CbmsReconciliationResult(false)));
        $this->assertSame(CbmsTransmission::STATUS_DUPLICATE_REQUIRES_RECONCILIATION, app(CbmsReconciliationService::class)->reconcile($transmission)->status);
        $invoice = $transmission->transmittable;
        $evidence = ['seller_pan' => $invoice->seller_pan_snapshot, 'fiscal_year' => '2081.082', 'document_number' => $invoice->invoice_no, 'document_type' => 'bill', 'total_amount' => 113, 'payload_hash' => $transmission->payload_hash];
        $this->app->instance(CbmsReconciliationVerifier::class, new FakeCbmsReconciliationVerifier(new CbmsReconciliationResult(true, $evidence, 'fake_exact_match')));
        $this->assertSame(CbmsTransmission::STATUS_SUBMITTED, app(CbmsReconciliationService::class)->reconcile($transmission->fresh())->status);
    }

    #[DataProvider('returnResponseCases')]
    public function test_credit_note_response_codes_remain_endpoint_specific(string $code, string $expected, string $category): void
    {
        Queue::fake();
        $invoice = $this->invoice('SI-RETURN-'.uniqid());
        CbmsTransmission::create(['company_id' => $this->company->id, 'transmittable_type' => $invoice->getMorphClass(), 'transmittable_id' => $invoice->id, 'endpoint_type' => CbmsTransmission::ENDPOINT_BILL, 'status' => CbmsTransmission::STATUS_SUBMITTED, 'attempt_count' => 1, 'payload_hash' => str_repeat('b', 64), 'submitted_at' => now()]);
        $return = $this->salesReturn($invoice);
        $transmission = app(CbmsQueueService::class)->queue($return);
        $fake = (new FakeCbmsTransport)->push(CbmsTransportResult::response(now(), 200, $code, $code));
        $this->app->instance(CbmsHttpTransport::class, $fake);
        $processed = app(CbmsTransmissionProcessor::class)->process($transmission->id)->transmission;
        $this->assertSame($expected, $processed->status);
        $this->assertSame($category, $processed->response_category);
        $this->assertSame(CbmsTransmission::STATUS_SUBMITTED, CbmsTransmission::where('endpoint_type', CbmsTransmission::ENDPOINT_BILL)->firstOrFail()->status);
    }

    public static function returnResponseCases(): array
    {
        return [
            'return 101 is ambiguous' => ['101', 'duplicate_requires_reconciliation', 'ambiguous_bill_return_101'],
            'return 105 original missing remotely' => ['105', 'permanent_failure', 'referenced_bill_not_found'],
            'return success' => ['200', 'submitted', 'submitted'],
        ];
    }

    public function test_credit_note_waits_until_original_bill_is_remotely_confirmed(): void
    {
        Queue::fake();
        $invoice = $this->invoice('SI-UNCONFIRMED');
        $return = $this->salesReturn($invoice);
        $blocked = app(CbmsQueueService::class)->queue($return);
        $this->assertSame(CbmsTransmission::STATUS_NOT_READY, $blocked->status);
        $this->assertContains('ORIGINAL_BILL_NOT_CONFIRMED', $blocked->response_body_redacted['reason_codes']);
        CbmsTransmission::create(['company_id' => $this->company->id, 'transmittable_type' => $invoice->getMorphClass(), 'transmittable_id' => $invoice->id, 'endpoint_type' => CbmsTransmission::ENDPOINT_BILL, 'status' => CbmsTransmission::STATUS_SUBMITTED, 'payload_hash' => str_repeat('c', 64), 'submitted_at' => now()]);
        $this->assertSame(CbmsTransmission::STATUS_QUEUED, app(CbmsQueueService::class)->queue($return)->status);
    }

    public function test_manual_retry_only_accepts_retryable_failure_and_stale_terminal_job_is_no_op(): void
    {
        Queue::fake();
        $retryable = $this->transmission(CbmsTransmission::STATUS_RETRYABLE_FAILURE);
        $this->assertSame(CbmsTransmission::STATUS_QUEUED, app(CbmsQueueService::class)->retry($retryable)->status);
        Queue::assertPushed(TransmitCbmsDocumentJob::class, 1);

        $submitted = $this->transmission(CbmsTransmission::STATUS_SUBMITTED);
        $fake = new FakeCbmsTransport;
        $this->app->instance(CbmsHttpTransport::class, $fake);
        $this->assertSame(CbmsTransmission::STATUS_SUBMITTED, app(CbmsTransmissionProcessor::class)->process($submitted->id)->transmission->status);
        $this->assertCount(0, $fake->calls);
        $this->expectException(LogicException::class);
        app(CbmsQueueService::class)->retry($submitted);
    }

    #[DataProvider('realtimeCases')]
    public function test_realtime_is_decided_at_attempt(int $seconds, bool $expected): void
    {
        Carbon::setTestNow(Carbon::parse('2024-04-13 10:00:00'));
        $invoice = $this->invoice('SI-RT-'.uniqid());
        $invoice->forceFill(['fiscal_issued_at' => now()])->save();
        Carbon::setTestNow(now()->addSeconds($seconds));
        $built = app(\App\Services\Cbms\CbmsSalesBillPayloadBuilder::class)->build($invoice->fresh(), $this->company->id, now());
        $this->assertSame($expected, $built['payload']['isrealtime']);
        Carbon::setTestNow();
    }

    public static function realtimeCases(): array { return [[300, true], [301, false], [-1, false]]; }

    private function invoice(string $number, string $classification = 'vat_taxable', float $vat = 13): SalesInvoice
    {
        $id = DB::table('sales_invoices')->insertGetId(['created_by' => $this->admin->id, 'company_id' => $this->company->id, 'financial_year_id' => $this->financialYear->id, 'customer_id' => $this->customerId, 'invoice_no' => $number, 'sale_date' => '2024-04-13', 'subtotal' => 100, 'discount' => 0, 'total_vat' => $vat, 'grand_total' => 100 + $vat, 'paid_amount' => 0, 'due_amount' => 100 + $vat, 'payment_status' => 'unpaid', 'status' => 1, 'fiscal_snapshot_captured_at' => now(), 'fiscal_issued_at' => '2024-04-13 10:00:00', 'seller_name_snapshot' => 'CBMS Seller', 'seller_pan_snapshot' => '123456789', 'buyer_name_snapshot' => 'Buyer', 'buyer_tax_no_snapshot' => '987654321', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('sales_items')->insert(['created_by' => $this->admin->id, 'company_id' => $this->company->id, 'financial_year_id' => $this->financialYear->id, 'sales_invoice_id' => $id, 'item_type' => 'service', 'quantity' => 1, 'returned_qty' => 0, 'unit_price' => 100, 'vat_rate' => $vat > 0 ? 13 : 0, 'vat_amount' => $vat, 'fiscal_discount_amount' => 0, 'fiscal_net_base' => 100, 'tax_classification' => $classification, 'total_price' => 100 + $vat, 'item_name_snapshot' => 'Service', 'unit_name_snapshot' => 'Service', 'created_at' => now(), 'updated_at' => now()]);
        return SalesInvoice::findOrFail($id);
    }

    private function transmission(string $status): CbmsTransmission
    {
        $invoice = $this->invoice('SI-STATE-'.uniqid());
        return CbmsTransmission::create(['company_id' => $this->company->id, 'transmittable_type' => $invoice->getMorphClass(), 'transmittable_id' => $invoice->id, 'endpoint_type' => CbmsTransmission::ENDPOINT_BILL, 'status' => $status, 'payload_hash' => str_repeat('a', 64)]);
    }

    private function salesReturn(SalesInvoice $invoice): SalesReturn
    {
        $salesItemId = DB::table('sales_items')->where('sales_invoice_id', $invoice->id)->value('id');
        $return = SalesReturn::create(['company_id' => $this->company->id, 'financial_year_id' => $this->financialYear->id, 'sales_invoice_id' => $invoice->id, 'customer_id' => $this->customerId, 'return_no' => 'CN-'.uniqid(), 'return_date' => '2024-04-13', 'subtotal' => 100, 'total_vat' => 13, 'grand_total' => 113, 'note' => 'Returned item', 'fiscal_issued_at' => '2024-04-13 11:00:00', 'status' => 1]);
        DB::table('sales_return_items')->insert(['company_id' => $this->company->id, 'financial_year_id' => $this->financialYear->id, 'sales_return_id' => $return->id, 'sales_item_id' => $salesItemId, 'quantity' => 1, 'unit_price' => 100, 'vat_rate' => 13, 'vat_amount' => 13, 'fiscal_discount_amount' => 0, 'fiscal_net_base' => 100, 'tax_classification' => 'vat_taxable', 'total_price' => 113, 'status' => 1]);
        return $return->fresh();
    }

    private function user(string $name, int $role): User
    {
        return User::create(['name' => $name, 'email' => strtolower($name).uniqid().'@test.local', 'password' => Hash::make('password'), 'role_id' => $role, 'company_id' => $this->company->id, 'account_status' => 'active']);
    }
}
