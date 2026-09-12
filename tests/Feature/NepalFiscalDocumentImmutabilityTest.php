<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckSubscription;
use App\Http\Middleware\EnsureCompanyUser;
use App\Http\Middleware\UpdateLastSeen;
use App\Models\Company;
use App\Models\CompanyIrdCbmsSetting;
use App\Models\CompanyTaxSetting;
use App\Models\Country;
use App\Models\Permission;
use App\Models\SalesInvoice;
use App\Models\SalesReturn;
use App\Models\User;
use App\Services\Accounting\SalesAccountingIntegrationService;
use App\Services\Accounting\Integrations\SalesCogsAccountingIntegrationService;
use App\Services\Accounting\Integrations\SalesReturnCogsAccountingIntegrationService;
use App\Services\FiscalDocumentAuditService;
use App\Services\FiscalDocumentPolicyService;
use App\Services\InvoiceNumberService;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class NepalFiscalDocumentImmutabilityTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $admin;
    private int $financialYearId;
    private int $customerId;
    private int $accountId;

    protected function setUp(): void
    {
        parent::setUp();
        $np = Country::create(['name' => 'Nepal', 'iso_code' => 'NP', 'is_active' => true]);
        $this->company = Company::create(['company_name' => 'Nepal Fiscal Co', 'mobile' => '9800000001', 'email' => 'fiscal@example.test', 'status' => 'active']);
        DB::table('companies')->where('id', $this->company->id)->update(['country_id' => $np->id, 'pan_number' => '123456789', 'vat_number' => '987654321']);
        $this->company = $this->company->fresh();
        DB::table('roles')->insert(['id' => 2, 'name' => 'company_admin', 'created_at' => now(), 'updated_at' => now()]);
        foreach (['module_sales', 'view_sales', 'create_sales', 'edit_sales', 'cancel_sales'] as $name) {
            Permission::create(['name' => $name, 'scope' => Permission::SCOPE_COMPANY]);
        }
        $this->admin = User::create(['name' => 'Admin', 'email' => 'admin-fiscal@example.test', 'password' => Hash::make('password'), 'role_id' => 2, 'company_id' => $this->company->id, 'account_status' => 'active']);
        $this->financialYearId = DB::table('financial_years')->insertGetId(['company_id' => $this->company->id, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => 1, 'is_closed' => 0, 'is_locked' => 0, 'created_by' => $this->admin->id, 'created_at' => now(), 'updated_at' => now()]);
        $this->customerId = DB::table('customers')->insertGetId(['company_id' => $this->company->id, 'created_by' => $this->admin->id, 'name' => 'Fiscal Customer', 'opening_balance' => 0, 'current_balance' => 0, 'credit_days' => 0, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        $this->accountId = DB::table('accounts')->insertGetId(['company_id' => $this->company->id, 'account_type' => 'Cash', 'bank_name' => 'Cash', 'account_name' => 'Cash', 'currency' => 'NPR', 'opening_balance' => 0, 'current_balance' => 0, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        CompanyTaxSetting::create(['company_id' => $this->company->id, 'is_vat_registered' => true, 'updated_by' => $this->admin->id]);
        $this->withoutMiddleware([EnsureCompanyUser::class, CheckSubscription::class, UpdateLastSeen::class, VerifyCsrfToken::class]);
        $this->actingAs($this->admin);
    }

    public function test_cbms_invoice_edit_and_direct_update_are_blocked_without_mutation(): void
    {
        $invoice = $this->invoice('Original invoice note');
        $itemId = DB::table('sales_items')->insertGetId(['created_by' => $this->admin->id, 'company_id' => $this->company->id, 'financial_year_id' => $this->financialYearId, 'sales_invoice_id' => $invoice->id, 'item_type' => 'service', 'quantity' => 2, 'returned_qty' => 0, 'unit_price' => 250, 'vat_rate' => 0, 'vat_amount' => 0, 'total_price' => 500, 'created_at' => now(), 'updated_at' => now()]);
        $this->enableCbms();
        $this->markFiscallyIssued($invoice);

        $this->get(route('company.sales.edit', $invoice->id))
            ->assertRedirect(route('company.sales.show', $invoice->id))
            ->assertSessionHas('error');

        $accountingCount = DB::table('accounting_entries')->count();
        $stockCount = DB::table('stock_movements')->count();
        $this->from(route('company.sales.show', $invoice->id))->put(route('company.sales.update', $invoice->id), [
            'sale_date' => '2026-07-01', 'note' => 'Tampered note', 'customer_id' => 999,
            'grand_total' => 1, 'quantity' => [999], 'cbms' => false,
        ])->assertRedirect(route('company.sales.show', $invoice->id))->assertSessionHas('error');

        $invoice->refresh();
        $this->assertSame('2026-06-15', $invoice->sale_date->format('Y-m-d'));
        $this->assertSame('Original invoice note', $invoice->note);
        $this->assertSame('500.00', $invoice->grand_total);
        $this->assertSame($this->customerId, (int) $invoice->customer_id);
        $this->assertSame(2.0, (float) DB::table('sales_items')->where('id', $itemId)->value('quantity'));
        $this->assertSame($accountingCount, DB::table('accounting_entries')->count());
        $this->assertSame($stockCount, DB::table('stock_movements')->count());
        $blocked = DB::table('fiscal_document_audit_events')->where('company_id', $this->company->id)
            ->where('document_id', $invoice->id)->where('event_type', 'protected_update_blocked')->get();
        $this->assertCount(2, $blocked);
        $this->assertSame(['edit', 'update'], $blocked->map(fn ($event) => json_decode($event->metadata, true)['attempted_action'])->all());
    }

    public function test_cbms_invoice_show_hides_edit_and_displays_lock_state(): void
    {
        $invoice = $this->invoice();
        $this->enableCbms();

        $this->get(route('company.sales.show', $invoice->id))
            ->assertOk()
            ->assertDontSee(route('company.sales.edit', $invoice->id), false)
            ->assertSee('editing is locked');
        $this->assertDatabaseCount('fiscal_document_audit_events', 0);
        $this->get(route('company.sales.index'))
            ->assertOk()
            ->assertDontSee(route('company.sales.cancel', $invoice->id), false);
    }

    public function test_nepal_cbms_off_and_non_nepal_invoice_updates_remain_allowed_and_company_scoped(): void
    {
        $invoice = $this->invoice('Before');
        $this->put(route('company.sales.update', $invoice->id), ['sale_date' => '2026-06-16', 'note' => 'Nepal OFF'])
            ->assertRedirect(route('company.sales.index'))->assertSessionHas('success');
        $this->assertSame('Nepal OFF', $invoice->fresh()->note);

        [$foreignCompany, $foreignAdmin, $foreignFy, $foreignCustomer] = $this->foreignContext();
        $foreignInvoice = $this->invoice('Foreign before', $foreignCompany->id, $foreignFy, $foreignCustomer, $foreignAdmin->id, 'SI-FOREIGN');
        $this->actingAs($foreignAdmin)->put(route('company.sales.update', $foreignInvoice->id), ['sale_date' => '2026-06-17', 'note' => 'Foreign updated'])
            ->assertRedirect(route('company.sales.index'))->assertSessionHas('success');
        $this->assertSame('Foreign updated', $foreignInvoice->fresh()->note);
        $this->assertSame('Nepal OFF', $invoice->fresh()->note);

        CompanyIrdCbmsSetting::create(['company_id' => $foreignCompany->id, 'is_enabled' => true, 'updated_by' => $foreignAdmin->id]);
        app(FiscalDocumentAuditService::class)->recordIssued($foreignCompany, 'sales_invoice', $foreignInvoice, $foreignInvoice->invoice_no, $foreignAdmin->id);
        $this->assertDatabaseCount('fiscal_document_audit_events', 0);
    }

    public function test_cbms_sales_return_edit_and_update_are_blocked_without_mutation(): void
    {
        $invoice = $this->invoice();
        $return = $this->salesReturn($invoice, 'Original return note');
        $this->enableCbms();
        $this->markFiscallyIssued($invoice);
        $return->forceFill(['fiscal_issued_at' => now()])->save();

        $this->get(route('company.sales-return.edit', $return->id))
            ->assertRedirect(route('company.sales-return.show', $return->id))->assertSessionHas('error');
        $this->from(route('company.sales-return.show', $return->id))->post(route('company.sales-return.update', $return->id), [
            'return_date' => '2026-07-01', 'note' => 'Tampered return', 'grand_total' => 1,
        ])->assertRedirect(route('company.sales-return.show', $return->id))->assertSessionHas('error');

        $return->refresh();
        $this->assertSame('2026-06-16', $return->return_date->format('Y-m-d'));
        $this->assertSame('Original return note', $return->note);
        $this->assertSame('100.00', $return->grand_total);
    }

    public function test_cbms_off_sales_return_update_remains_allowed(): void
    {
        $return = $this->salesReturn($this->invoice(), 'Before');
        $this->post(route('company.sales-return.update', $return->id), ['return_date' => '2026-06-17', 'note' => 'After'])
            ->assertRedirect(route('company.sales-return.show', $return->id))->assertSessionHas('success');
        $this->assertSame('2026-06-17', $return->fresh()->return_date->format('Y-m-d'));
        $this->assertSame('After', $return->fresh()->note);
    }

    public function test_cbms_direct_cancellation_is_blocked_before_dependency_or_financial_mutation(): void
    {
        $invoice = $this->invoice();
        DB::table('sales_payments')->insert(['company_id' => $this->company->id, 'financial_year_id' => $this->financialYearId, 'sales_invoice_id' => $invoice->id, 'customer_id' => $this->customerId, 'account_id' => $this->accountId, 'payment_no' => 'SP-LOCK', 'payment_date' => '2026-06-15', 'paid_amount' => 10, 'status' => 1, 'created_by' => $this->admin->id, 'created_at' => now(), 'updated_at' => now()]);
        $this->enableCbms();
        $this->markFiscallyIssued($invoice);

        $this->post(route('company.sales.cancel', $invoice->id), ['cancel_date' => '2026-06-17', 'cancel_reason' => 'Attempt'])
            ->assertSessionHas('error', FiscalDocumentPolicyService::ISSUED_INVOICE_MUTATION_MESSAGE);
        $this->assertSame(1, (int) $invoice->fresh()->status);
        $this->assertDatabaseMissing('fiscal_document_audit_events', ['document_type' => 'sales_invoice', 'document_id' => $invoice->id, 'event_type' => 'cancelled']);
        $this->assertDatabaseHas('fiscal_document_audit_events', ['document_type' => 'sales_invoice', 'document_id' => $invoice->id, 'event_type' => 'protected_cancel_blocked']);
    }

    public function test_cbms_issued_invoice_cannot_be_cancelled_or_deleted_and_remains_readable_and_printable(): void
    {
        $invoice = $this->invoice('Cancellable');
        $originalValues = $invoice->only(['invoice_no', 'sale_date', 'customer_id', 'subtotal', 'grand_total']);
        $this->enableCbms();
        $this->markFiscallyIssued($invoice);
        $this->mock(SalesAccountingIntegrationService::class)->shouldNotReceive('reverseSale');
        $this->mock(SalesCogsAccountingIntegrationService::class)->shouldNotReceive('reverseSaleCogs');

        $this->post(route('company.sales.cancel', $invoice->id), [
            'cancel_date' => '2026-06-17', 'cancel_reason' => '   ',
        ])->assertSessionHasErrors('cancel_reason');
        $this->assertSame(1, (int) $invoice->fresh()->status);
        $this->assertDatabaseMissing('fiscal_document_audit_events', ['document_type' => 'sales_invoice', 'document_id' => $invoice->id, 'event_type' => 'cancelled']);

        $this->post(route('company.sales.cancel', $invoice->id), [
            'cancel_date' => '2026-06-17', 'cancel_reason' => 'Controlled correction',
        ])->assertSessionHas('error', FiscalDocumentPolicyService::ISSUED_INVOICE_MUTATION_MESSAGE);

        $invoice->refresh();
        $this->assertSame(1, (int) $invoice->status);
        $this->assertNull($invoice->cancelled_at);
        $this->assertNull($invoice->cancellation_reason);
        foreach ($originalValues as $field => $value) {
            $this->assertEquals($value, $invoice->{$field});
        }
        $this->assertDatabaseCount('fiscal_document_audit_events', 1);
        $this->assertDatabaseHas('fiscal_document_audit_events', ['document_type' => 'sales_invoice', 'document_id' => $invoice->id, 'event_type' => 'protected_cancel_blocked']);

        try {
            $invoice->delete();
            $this->fail('Issued CBMS invoice deletion was not blocked.');
        } catch (\RuntimeException $e) {
            $this->assertSame(FiscalDocumentPolicyService::ISSUED_INVOICE_MUTATION_MESSAGE, $e->getMessage());
        }
        $this->assertDatabaseHas('sales_invoices', ['id' => $invoice->id, 'status' => 1]);
        $this->assertDatabaseCount('fiscal_document_audit_events', 2);
        $this->assertDatabaseHas('fiscal_document_audit_events', ['document_type' => 'sales_invoice', 'document_id' => $invoice->id, 'event_type' => 'protected_delete_blocked']);

        $this->get(route('company.sales.print', $invoice->id))
            ->assertRedirect()->assertSessionHas('error');
        $this->get(route('company.sales.show', $invoice->id))->assertOk();
    }

    public function test_cbms_off_and_non_nepal_legacy_cancellation_remain_available(): void
    {
        $invoice = $this->invoice('Nepal OFF');
        $this->mock(SalesAccountingIntegrationService::class)->shouldReceive('reverseSale')->twice();
        $this->mock(SalesCogsAccountingIntegrationService::class)->shouldReceive('reverseSaleCogs')->twice();

        $this->post(route('company.sales.cancel', $invoice->id), [
            'cancel_date' => '2026-06-17', 'cancel_reason' => 'Legacy correction',
        ])->assertSessionHas('success');
        $this->assertSame(0, (int) $invoice->fresh()->status);
        $invoice->delete();
        $this->assertDatabaseMissing('sales_invoices', ['id' => $invoice->id]);

        [$foreignCompany, $foreignAdmin, $foreignFy, $foreignCustomer] = $this->foreignContext();
        CompanyIrdCbmsSetting::create(['company_id' => $foreignCompany->id, 'is_enabled' => true, 'updated_by' => $foreignAdmin->id]);
        $foreignInvoice = $this->invoice('Foreign', $foreignCompany->id, $foreignFy, $foreignCustomer, $foreignAdmin->id, 'SI-FOREIGN-CANCEL');
        $this->actingAs($foreignAdmin)->post(route('company.sales.cancel', $foreignInvoice->id), [
            'cancel_date' => '2026-06-17', 'cancel_reason' => 'Foreign legacy correction', 'cbms' => true,
        ])->assertSessionHas('success');
        $this->assertSame(0, (int) $foreignInvoice->fresh()->status);
        $foreignInvoice->delete();
        $this->assertDatabaseMissing('sales_invoices', ['id' => $foreignInvoice->id]);
    }

    public function test_cbms_invoice_issue_and_server_authoritative_original_reprint_history(): void
    {
        $this->enableCbms();
        $this->company->update(['company_name' => 'Original Seller', 'address' => 'Seller Address', 'pan_number' => '123456789', 'vat_number' => '987654321']);
        DB::table('customers')->where('id', $this->customerId)->update(['name' => 'Original Buyer', 'address' => 'Buyer Address', 'tax_no' => 'BUYER-TAX']);
        $category = DB::table('service_categories')->insertGetId(['company_id' => $this->company->id, 'name' => 'Fiscal Services', 'status' => 'active', 'created_by' => $this->admin->id, 'created_at' => now(), 'updated_at' => now()]);
        $service = DB::table('services')->insertGetId(['company_id' => $this->company->id, 'service_category_id' => $category, 'name' => 'Original Service', 'price' => 500, 'status' => 'active', 'created_by' => $this->admin->id, 'created_at' => now(), 'updated_at' => now()]);
        $this->mock(SalesAccountingIntegrationService::class)->shouldReceive('postSale')->once();
        $this->mock(SalesCogsAccountingIntegrationService::class)->shouldReceive('postSaleCogs')->once();

        $this->post(route('company.sales.store'), ['customer_id' => $this->customerId, 'sale_date' => '2026-06-15', 'item_type' => ['service'], 'product_id' => [null], 'service_id' => [null], 'quantity' => [1], 'unit_price' => [500], 'vat_rate' => [0], 'tax_classification' => ['vat_exempt'], 'paid_amount' => 0])
            ->assertSessionHasErrors('service_id.0');
        $this->assertDatabaseCount('sales_invoices', 0);
        $this->assertDatabaseCount('fiscal_document_audit_events', 0);

        Carbon::setTestNow('2026-09-08 00:15:30 UTC');
        $this->get(route('company.sales.create'))->assertOk();
        $number = session('pending_sales_invoice.invoice_no');
        $this->assertNotEmpty($number);

        $this->post(route('company.sales.store'), ['customer_id' => $this->customerId, 'sale_date' => '2026-06-15', 'item_type' => ['service'], 'product_id' => [null], 'service_id' => [$service], 'quantity' => [1], 'unit_price' => [500], 'vat_rate' => [0], 'tax_classification' => ['vat_exempt'], 'discount_amount' => 0, 'paid_amount' => 0, 'seller_name_snapshot' => 'Forged Seller', 'buyer_name_snapshot' => 'Forged Buyer', 'item_name_snapshot' => ['Forged Item'], 'fiscal_payment_mode' => 'forged', 'fiscal_issued_at' => '1999-01-01 00:00:00', 'issue_date' => '1999-01-01', 'issue_time' => '00:00:00', 'cbms' => false, 'company_id' => 999])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');
        $invoice = SalesInvoice::where('invoice_no', $number)->sole();
        $this->assertNotNull($invoice->fiscal_snapshot_captured_at);
        $this->assertSame('Original Seller', $invoice->seller_name_snapshot);
        $this->assertSame('Seller Address', $invoice->seller_address_snapshot);
        $this->assertSame('123456789', $invoice->seller_pan_snapshot);
        $this->assertSame('987654321', $invoice->seller_vat_snapshot);
        $this->assertSame('Original Buyer', $invoice->buyer_name_snapshot);
        $this->assertSame('Buyer Address', $invoice->buyer_address_snapshot);
        $this->assertSame('BUYER-TAX', $invoice->buyer_tax_no_snapshot);
        $this->assertSame('Original Service', $invoice->items()->sole()->item_name_snapshot);
        $this->assertSame('Service', $invoice->items()->sole()->unit_name_snapshot);
        $this->assertNull($invoice->items()->sole()->fiscal_origin_type);
        $this->assertNull($invoice->items()->sole()->fiscal_hs_code);
        $this->assertSame('credit', $invoice->fiscal_payment_mode);
        $this->assertNotNull($invoice->fiscal_payment_mode_captured_at);
        $this->assertSame('2026-06-15', $invoice->sale_date->format('Y-m-d'));
        $this->assertSame('2026-09-08 00:15:30', $invoice->fiscal_issued_at?->format('Y-m-d H:i:s'));
        $this->assertDatabaseCount('fiscal_document_audit_events', 1);
        $this->assertDatabaseHas('fiscal_document_audit_events', ['document_type' => 'sales_invoice', 'document_id' => $invoice->id, 'event_type' => 'invoice_issued']);
        $issued = \App\Models\FiscalDocumentAuditEvent::where('document_type', 'sales_invoice')->where('document_id', $invoice->id)->where('event_type', 'invoice_issued')->sole();
        $this->assertSame($this->financialYearId, (int) $issued->metadata['financial_year_id']);
        $this->assertSame('2026-06-15', $issued->metadata['invoice_date']);
        $this->assertSame('500.00', $issued->metadata['grand_total']);
        $this->assertSame('succeeded', $issued->metadata['result']);
        $this->assertSame('credit', $issued->metadata['fiscal_payment_mode']);
        $this->assertSame(
            ['category' => 'Credit', 'detail' => null, 'display' => 'Credit'],
            $issued->metadata['fiscal_payment_presentation']
        );
        $this->assertSame('2026-09-08T00:15:30.000000Z', $issued->metadata['fiscal_issued_at']);

        $this->company->update(['company_name' => 'Changed Seller', 'address' => 'Changed Seller Address', 'pan_number' => '111111111', 'vat_number' => '222222222']);
        DB::table('customers')->where('id', $this->customerId)->update(['name' => 'Changed Buyer', 'address' => 'Changed Buyer Address', 'tax_no' => 'CHANGED-TAX']);
        DB::table('services')->where('id', $service)->update(['name' => 'Changed Service']);

        $this->get(route('company.sales.print', ['id' => $invoice->id, 'original' => 1]))->assertOk()->assertSee('Original')->assertSee('TAX INVOICE')->assertSee('Original Seller')->assertSee('Original Buyer')->assertSee('Original Service')->assertSee('Mode of Payment')->assertSee('Credit')->assertDontSee('Cheque')->assertSee('Transaction Date')->assertSee('15-06-2026')->assertSee('Issue Date/Time')->assertSee('08-09-2026 06:00:30')->assertSee('Gross')->assertSee('Discount')->assertSee('Net')->assertSee('Tax Class')->assertSee('Exempt Amount')->assertSee('Seller Signature')->assertDontSee('Changed Seller')->assertDontSee('Changed Buyer')->assertDontSee('Changed Service');
        $this->get(route('company.sales.print', ['id' => $invoice->id, 'original' => 1]))->assertOk()->assertSee('Copy of Original (1)')->assertSee('Mode of Payment')->assertSee('Credit')->assertSee('08-09-2026 06:00:30')->assertSee('Original Seller')->assertSee('Original Buyer')->assertSee('Original Service');
        $this->get(route('company.sales.print', $invoice->id))->assertOk()->assertSee('Copy of Original (2)')->assertSee('Mode of Payment')->assertSee('Credit')->assertSee('08-09-2026 06:00:30')->assertSee('Original Seller')->assertSee('Original Buyer')->assertSee('Original Service');
        CompanyIrdCbmsSetting::where('company_id', $this->company->id)->update(['is_enabled' => false]);
        $this->get(route('company.sales.print', $invoice->id))->assertOk()->assertSee('Copy of Original (3)')->assertSee('TAX INVOICE');
        $this->assertSame('2026-09-08 00:15:30', $invoice->fresh()->fiscal_issued_at?->format('Y-m-d H:i:s'));
        $this->assertSame(1, DB::table('fiscal_document_audit_events')->where('document_id', $invoice->id)->where('event_type', 'original_printed')->count());
        $this->assertSame(3, DB::table('fiscal_document_audit_events')->where('document_id', $invoice->id)->where('event_type', 'reprinted')->count());
        $this->assertSame(
            ['invoice_issued', 'original_printed', 'reprinted', 'reprinted', 'reprinted'],
            \App\Models\FiscalDocumentAuditEvent::where('company_id', $this->company->id)
                ->where('document_type', 'sales_invoice')->where('document_id', $invoice->id)
                ->orderBy('event_at')->orderBy('id')->pluck('event_type')->all()
        );

        $before = DB::table('fiscal_document_audit_events')->count();
        $this->actingAs($this->admin)->get(route('company.sales.show', $invoice->id))->assertSee('Fiscal History')->assertDontSee('Delete Fiscal Event');

        [, $foreignAdmin] = $this->foreignContext();
        $this->actingAs($foreignAdmin)->get(route('company.sales.show', $invoice->id))->assertNotFound();
        $this->get(route('company.sales.print', $invoice->id))->assertNotFound();
        $this->assertSame($before, DB::table('fiscal_document_audit_events')->count());

        auth()->logout();
        $this->get(route('company.sales.print', $invoice->id))->assertRedirect();
        $this->assertSame($before, DB::table('fiscal_document_audit_events')->count());
        Carbon::setTestNow();
    }

    public function test_fiscal_payment_mode_derives_every_supported_issuance_mode_from_authoritative_state(): void
    {
        $service = app(\App\Services\SalesFiscalPaymentModeService::class);
        $this->assertSame([], $service->issuanceAttributes($this->company, 0, 100, null));

        $this->enableCbms();
        $this->assertSame('credit', $service->issuanceAttributes($this->company->fresh(), 0, 100, null)['fiscal_payment_mode']);
        $this->assertSame('mixed', $service->issuanceAttributes($this->company->fresh(), 25, 100, $this->accountId)['fiscal_payment_mode']);

        foreach (['Cash' => 'cash', 'Bank' => 'bank', 'ATM' => 'bank', 'Wallet' => 'digital', 'Other' => 'other'] as $type => $expected) {
            $accountId = DB::table('accounts')->insertGetId([
                'company_id' => $this->company->id, 'account_type' => $type, 'bank_name' => $type,
                'account_name' => $type.' Receipt', 'currency' => 'NPR', 'opening_balance' => 0,
                'current_balance' => 0, 'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
            ]);
            $this->assertSame($expected, $service->issuanceAttributes($this->company->fresh(), 100, 100, $accountId)['fiscal_payment_mode']);
        }

        [$foreignCompany] = $this->foreignContext();
        CompanyIrdCbmsSetting::create([
            'company_id' => $foreignCompany->id, 'is_enabled' => true, 'updated_by' => $this->admin->id,
        ]);
        $this->assertSame([], $service->issuanceAttributes($foreignCompany->fresh(), 100, 100, null));
        $foreignAccount = DB::table('accounts')->insertGetId([
            'company_id' => $foreignCompany->id, 'account_type' => 'Cash', 'bank_name' => 'Cash',
            'account_name' => 'Foreign Cash', 'currency' => 'NPR', 'opening_balance' => 0,
            'current_balance' => 0, 'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $service->issuanceAttributes($this->company->fresh(), 100, 100, $foreignAccount);
    }

    public function test_fiscal_issue_timestamp_is_future_only_and_uses_authoritative_company_scope(): void
    {
        $service = app(\App\Services\SalesFiscalIssueDateTimeService::class);
        $this->assertSame([], $service->issuanceAttributes($this->company->fresh()));

        Carbon::setTestNow('2026-09-08 00:15:30 UTC');
        $this->enableCbms();
        $attributes = $service->issuanceAttributes($this->company->fresh());
        $this->assertSame('2026-09-08 00:15:30', $attributes['fiscal_issued_at']->format('Y-m-d H:i:s'));

        [$foreignCompany, $foreignAdmin] = $this->foreignContext();
        CompanyIrdCbmsSetting::create([
            'company_id' => $foreignCompany->id, 'is_enabled' => true, 'updated_by' => $foreignAdmin->id,
        ]);
        $this->assertSame([], $service->issuanceAttributes($foreignCompany->fresh()));
        Carbon::setTestNow();
    }

    public function test_later_payment_changes_and_cancellation_never_rewrite_fiscal_payment_mode(): void
    {
        $this->enableCbms();
        $invoice = $this->invoice();
        $invoice->forceFill([
            'fiscal_payment_mode' => 'credit',
            'fiscal_payment_mode_captured_at' => now(),
            'fiscal_issued_at' => now()->subHour(),
        ])->save();
        $capturedAt = $invoice->fresh()->fiscal_payment_mode_captured_at?->format('Y-m-d H:i:s');
        $issuedAt = $invoice->fresh()->fiscal_issued_at?->format('Y-m-d H:i:s');
        $presentation = app(\App\Services\SalesFiscalPaymentModeService::class)
            ->irdPresentation($invoice->fiscal_payment_mode);

        $paymentId = DB::table('sales_payments')->insertGetId([
            'company_id' => $this->company->id, 'financial_year_id' => $this->financialYearId,
            'sales_invoice_id' => $invoice->id, 'customer_id' => $this->customerId,
            'account_id' => $this->accountId, 'payment_no' => 'SP-LATER', 'payment_date' => '2026-06-16',
            'paid_amount' => 500, 'payment_method' => 'bank', 'status' => 1,
            'created_by' => $this->admin->id, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $invoice->update(['paid_amount' => 500, 'due_amount' => 0, 'payment_status' => 'paid']);
        DB::table('sales_payments')->where('id', $paymentId)->update(['payment_method' => 'wallet', 'status' => 0]);
        $invoice->update(['paid_amount' => 0, 'due_amount' => 500, 'payment_status' => 'unpaid']);

        $invoice->refresh();
        $this->assertSame('credit', $invoice->fiscal_payment_mode);
        $this->assertSame($presentation, app(\App\Services\SalesFiscalPaymentModeService::class)
            ->irdPresentation($invoice->fiscal_payment_mode));
        $this->assertSame($capturedAt, $invoice->fiscal_payment_mode_captured_at?->format('Y-m-d H:i:s'));
        $this->assertSame($issuedAt, $invoice->fiscal_issued_at?->format('Y-m-d H:i:s'));
    }

    public function test_fiscal_payment_mode_is_captured_during_real_fully_paid_issuance(): void
    {
        $this->enableCbms();
        $category = DB::table('service_categories')->insertGetId([
            'company_id' => $this->company->id, 'name' => 'Paid Fiscal Services', 'status' => 'active',
            'created_by' => $this->admin->id, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $service = DB::table('services')->insertGetId([
            'company_id' => $this->company->id, 'service_category_id' => $category,
            'name' => 'Paid Fiscal Service', 'price' => 500, 'status' => 'active',
            'created_by' => $this->admin->id, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->mock(SalesAccountingIntegrationService::class)->shouldReceive('postSale')->once();
        $this->mock(SalesCogsAccountingIntegrationService::class)->shouldReceive('postSaleCogs')->once();

        $this->get(route('company.sales.create'))->assertOk();
        $number = session('pending_sales_invoice.invoice_no');
        $this->post(route('company.sales.store'), [
            'customer_id' => $this->customerId, 'sale_date' => '2026-06-15',
            'item_type' => ['service'], 'product_id' => [null], 'service_id' => [$service],
            'quantity' => [1], 'unit_price' => [500], 'vat_rate' => [0],
            'tax_classification' => ['vat_exempt'], 'discount_amount' => 0,
            'paid_amount' => 500, 'account_id' => $this->accountId,
            'fiscal_payment_mode' => 'bank',
        ])->assertSessionHasNoErrors()->assertSessionHas('success');

        $invoice = SalesInvoice::where('invoice_no', $number)->sole();
        $this->assertSame('cash', $invoice->fiscal_payment_mode);
        $this->assertNotNull($invoice->fiscal_payment_mode_captured_at);
        $this->assertDatabaseHas('sales_payments', [
            'sales_invoice_id' => $invoice->id, 'payment_method' => 'invoice', 'paid_amount' => 500,
        ]);
        $event = \App\Models\FiscalDocumentAuditEvent::where('document_type', 'sales_invoice')
            ->where('document_id', $invoice->id)->where('event_type', 'invoice_issued')->sole();
        $this->assertSame('cash', $event->metadata['fiscal_payment_mode']);
    }

    public function test_fiscal_audit_is_cbms_only_append_only_and_sales_return_print_is_tracked(): void
    {
        $invoice = $this->invoice();
        $return = $this->salesReturn($invoice, 'Return');
        $audit = app(FiscalDocumentAuditService::class);

        $audit->recordIssued($this->company, 'sales_invoice', $invoice, $invoice->invoice_no, $this->admin->id);
        $this->assertDatabaseCount('fiscal_document_audit_events', 0);

        $this->enableCbms();
        $this->markFiscallyIssued($invoice);
        $return->forceFill(['fiscal_issued_at' => now()])->save();
        $audit->recordIssued($this->company, 'sales_return', $return, $return->return_no, $this->admin->id);
        $this->get(route('company.sales-return.print', $return->id))->assertOk()->assertSee('Original');
        $this->get(route('company.sales-return.print', $return->id))->assertOk()->assertSee('Copy of Original (1)');
        $this->assertDatabaseHas('fiscal_document_audit_events', ['document_type' => 'sales_return', 'document_id' => $return->id, 'event_type' => 'issued']);
        $event = \App\Models\FiscalDocumentAuditEvent::where('document_type', 'sales_return')->where('event_type', 'issued')->sole();
        $this->expectException(\LogicException::class);
        $event->update(['event_type' => 'tampered']);
    }

    public function test_failed_issued_transaction_rolls_back_without_false_fiscal_event(): void
    {
        $this->enableCbms();
        $category = DB::table('service_categories')->insertGetId(['company_id' => $this->company->id, 'name' => 'Rollback Services', 'status' => 'active', 'created_by' => $this->admin->id, 'created_at' => now(), 'updated_at' => now()]);
        $service = DB::table('services')->insertGetId(['company_id' => $this->company->id, 'service_category_id' => $category, 'name' => 'Rollback Service', 'price' => 500, 'status' => 'active', 'created_by' => $this->admin->id, 'created_at' => now(), 'updated_at' => now()]);
        $this->get(route('company.sales.create'))->assertOk();
        $this->mock(SalesAccountingIntegrationService::class)->shouldReceive('postSale')->once();
        $this->mock(SalesCogsAccountingIntegrationService::class)->shouldReceive('postSaleCogs')->once()->andThrow(new \RuntimeException('Forced rollback'));

        $this->post(route('company.sales.store'), ['customer_id' => $this->customerId, 'sale_date' => '2026-06-15', 'item_type' => ['service'], 'product_id' => [null], 'service_id' => [$service], 'quantity' => [1], 'unit_price' => [500], 'vat_rate' => [0], 'tax_classification' => ['vat_exempt'], 'paid_amount' => 0])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('sales_invoices', 0);
        $this->assertDatabaseCount('sales_items', 0);
        $this->assertDatabaseCount('fiscal_document_audit_events', 0);
    }

    public function test_cbms_sales_return_real_issue_and_cancellation_are_audited_once(): void
    {
        $invoice = $this->invoice();
        $category = DB::table('service_categories')->insertGetId(['company_id' => $this->company->id, 'name' => 'Return Services', 'status' => 'active', 'created_by' => $this->admin->id, 'created_at' => now(), 'updated_at' => now()]);
        $service = DB::table('services')->insertGetId(['company_id' => $this->company->id, 'service_category_id' => $category, 'name' => 'Return Service', 'price' => 500, 'status' => 'active', 'created_by' => $this->admin->id, 'created_at' => now(), 'updated_at' => now()]);
        $salesItem = DB::table('sales_items')->insertGetId(['created_by' => $this->admin->id, 'company_id' => $this->company->id, 'financial_year_id' => $this->financialYearId, 'sales_invoice_id' => $invoice->id, 'item_type' => 'service', 'service_id' => $service, 'quantity' => 2, 'returned_qty' => 0, 'unit_price' => 250, 'vat_rate' => 0, 'vat_amount' => 0, 'fiscal_discount_amount' => 0, 'fiscal_net_base' => 500, 'tax_classification' => 'vat_exempt', 'total_price' => 500, 'created_at' => now(), 'updated_at' => now()]);
        $this->enableCbms();
        $invoice->forceFill([
            'fiscal_issued_at' => now()->subDay(),
            'fiscal_snapshot_captured_at' => now()->subDay(),
            'seller_name_snapshot' => 'Frozen Seller',
            'seller_address_snapshot' => 'Frozen Seller Address',
            'seller_pan_snapshot' => '123456789',
            'buyer_name_snapshot' => 'Frozen Buyer',
            'buyer_address_snapshot' => 'Frozen Buyer Address',
            'buyer_tax_no_snapshot' => '987654321',
        ])->save();
        $originalIssuedAt = $invoice->fresh()->fiscal_issued_at?->format('Y-m-d H:i:s');
        $returnAccounting = $this->mock(SalesReturnCogsAccountingIntegrationService::class);
        $returnAccounting->shouldReceive('postReturn')->once();

        $this->post(route('company.sales-return.store'), [
            'sales_invoice_id' => $invoice->id,
            'customer_id' => $this->customerId,
            'return_date' => '2026-06-16',
            'sales_item_id' => [$salesItem],
            'quantity' => [1],
            'note' => 'Fiscal credit note',
        ])->assertSessionHas('success', 'Sales return saved successfully.');

        $return = SalesReturn::sole();
        $this->assertNotNull($return->fiscal_issued_at);
        $this->assertDatabaseHas('fiscal_document_audit_events', ['company_id' => $this->company->id, 'document_type' => 'sales_return', 'document_id' => $return->id, 'event_type' => 'issued', 'actor_id' => $this->admin->id]);
        $this->assertDatabaseHas('fiscal_document_audit_events', ['company_id' => $this->company->id, 'document_type' => 'sales_invoice', 'document_id' => $invoice->id, 'event_type' => 'sales_return_created', 'actor_id' => $this->admin->id]);
        $link = \App\Models\FiscalDocumentAuditEvent::where('document_type', 'sales_invoice')->where('document_id', $invoice->id)->where('event_type', 'sales_return_created')->sole();
        $this->assertSame($return->id, (int) $link->metadata['sales_return_id']);
        $this->assertTrue($link->metadata['original_preserved']);
        $this->assertSame('Fiscal credit note', $link->metadata['reason']);
        $this->assertTrue($link->metadata['fiscal_reconciliation']['is_reconciled']);
        $this->assertSame(1, (int) $invoice->fresh()->status);
        $this->assertSame($originalIssuedAt, $invoice->fresh()->fiscal_issued_at?->format('Y-m-d H:i:s'));

        CompanyIrdCbmsSetting::where('company_id', $this->company->id)->update(['is_enabled' => false]);
        DB::table('companies')->where('id', $this->company->id)->update(['company_name' => 'Changed Seller']);
        DB::table('customers')->where('id', $this->customerId)->update(['name' => 'Changed Buyer']);
        $this->assertTrue(app(FiscalDocumentPolicyService::class)->isSalesReturnImmutable($return->fresh('invoice')));
        $this->get(route('company.sales-return.print', $return->id))->assertOk()
            ->assertSee('CREDIT NOTE')->assertSee('Frozen Seller')->assertSee('Frozen Buyer')
            ->assertSee('Fiscal credit note')->assertSee($invoice->invoice_no)->assertSee('Original');

        $this->post(route('company.sales-return.cancel', $return->id), [
            'cancel_date' => '2026-06-17',
            'cancel_reason' => 'Credit note correction',
        ])->assertSessionHas('error', FiscalDocumentPolicyService::ISSUED_CREDIT_NOTE_MUTATION_MESSAGE);

        $return->refresh();
        $this->assertSame(1, (int) $return->status);
        $this->assertNull($return->cancellation_reason);
        $this->assertSame(0, DB::table('fiscal_document_audit_events')->where('document_type', 'sales_return')->where('document_id', $return->id)->where('event_type', 'cancelled')->count());
        $this->assertSame($originalIssuedAt, $invoice->fresh()->fiscal_issued_at?->format('Y-m-d H:i:s'));

        try {
            $return->delete();
            $this->fail('Permanently issued Credit Note deletion was not blocked.');
        } catch (\RuntimeException $exception) {
            $this->assertSame(FiscalDocumentPolicyService::ISSUED_CREDIT_NOTE_MUTATION_MESSAGE, $exception->getMessage());
        }
    }

    public function test_fiscal_credit_note_requires_meaningful_reason_and_rolls_back_all_evidence(): void
    {
        $invoice = $this->invoice();
        $category = DB::table('service_categories')->insertGetId(['company_id' => $this->company->id, 'name' => 'Reason Services', 'status' => 'active', 'created_by' => $this->admin->id, 'created_at' => now(), 'updated_at' => now()]);
        $service = DB::table('services')->insertGetId(['company_id' => $this->company->id, 'service_category_id' => $category, 'name' => 'Reason Service', 'price' => 100, 'status' => 'active', 'created_by' => $this->admin->id, 'created_at' => now(), 'updated_at' => now()]);
        $salesItem = DB::table('sales_items')->insertGetId(['created_by' => $this->admin->id, 'company_id' => $this->company->id, 'financial_year_id' => $this->financialYearId, 'sales_invoice_id' => $invoice->id, 'item_type' => 'service', 'service_id' => $service, 'quantity' => 1, 'returned_qty' => 0, 'unit_price' => 100, 'vat_rate' => 0, 'vat_amount' => 0, 'fiscal_discount_amount' => 0, 'fiscal_net_base' => 100, 'tax_classification' => 'vat_exempt', 'total_price' => 100, 'created_at' => now(), 'updated_at' => now()]);
        $this->markFiscallyIssued($invoice);

        foreach ([null, '', '   '] as $reason) {
            $this->post(route('company.sales-return.store'), [
                'sales_invoice_id' => $invoice->id, 'customer_id' => $this->customerId,
                'return_date' => '2026-06-16', 'sales_item_id' => [$salesItem],
                'quantity' => [1], 'note' => $reason,
            ])->assertSessionHas('error', 'A meaningful Credit Note reason is required for a fiscal Sales Return.');
        }

        $this->assertDatabaseCount('sales_returns', 0);
        $this->assertDatabaseCount('sales_return_items', 0);
        $this->assertEquals(0, DB::table('sales_items')->where('id', $salesItem)->value('returned_qty'));
        $this->assertDatabaseCount('fiscal_document_audit_events', 0);
    }

    public function test_historical_return_is_not_upgraded_from_original_invoice_or_current_setting(): void
    {
        $invoice = $this->invoice();
        $return = $this->salesReturn($invoice, 'Historical reason');
        $this->markFiscallyIssued($invoice);
        $this->enableCbms();

        $this->assertNull($return->fresh()->fiscal_issued_at);
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumn('sales_returns', 'fiscal_issued_at'));
        $this->assertFalse(app(FiscalDocumentPolicyService::class)->isSalesReturnImmutable($return->fresh('invoice')));
        $this->get(route('company.sales-return.print', $return->id))->assertOk()
            ->assertSee('SALES RETURN')->assertDontSee('CREDIT NOTE');
    }

    public function test_failed_fiscal_credit_note_posting_rolls_back_timestamp_lines_and_audit(): void
    {
        $invoice = $this->invoice();
        $category = DB::table('service_categories')->insertGetId(['company_id' => $this->company->id, 'name' => 'Failed Return Services', 'status' => 'active', 'created_by' => $this->admin->id, 'created_at' => now(), 'updated_at' => now()]);
        $service = DB::table('services')->insertGetId(['company_id' => $this->company->id, 'service_category_id' => $category, 'name' => 'Failed Return Service', 'price' => 100, 'status' => 'active', 'created_by' => $this->admin->id, 'created_at' => now(), 'updated_at' => now()]);
        $salesItem = DB::table('sales_items')->insertGetId(['created_by' => $this->admin->id, 'company_id' => $this->company->id, 'financial_year_id' => $this->financialYearId, 'sales_invoice_id' => $invoice->id, 'item_type' => 'service', 'service_id' => $service, 'quantity' => 1, 'returned_qty' => 0, 'unit_price' => 100, 'vat_rate' => 0, 'vat_amount' => 0, 'fiscal_discount_amount' => 0, 'fiscal_net_base' => 100, 'tax_classification' => 'vat_exempt', 'total_price' => 100, 'created_at' => now(), 'updated_at' => now()]);
        $this->markFiscallyIssued($invoice);
        $this->mock(SalesReturnCogsAccountingIntegrationService::class)
            ->shouldReceive('postReturn')->once()->andThrow(new \RuntimeException('Forced return posting rollback'));

        $this->post(route('company.sales-return.store'), [
            'sales_invoice_id' => $invoice->id, 'customer_id' => $this->customerId,
            'return_date' => '2026-06-16', 'sales_item_id' => [$salesItem],
            'quantity' => [1], 'note' => 'Valid fiscal reason',
        ])->assertSessionHas('error');

        $this->assertDatabaseCount('sales_returns', 0);
        $this->assertDatabaseCount('sales_return_items', 0);
        $this->assertEquals(0, DB::table('sales_items')->where('id', $salesItem)->value('returned_qty'));
        $this->assertDatabaseCount('fiscal_document_audit_events', 0);
    }

    public function test_product_and_unit_snapshot_survive_master_edits_and_reject_cross_company_sources(): void
    {
        $this->enableCbms();
        $unit = \App\Models\Unit::create(['company_id' => $this->company->id, 'name' => 'Pieces', 'short_name' => 'pcs']);
        $product = \App\Models\Product::create(['company_id' => $this->company->id, 'unit_id' => $unit->id, 'name' => 'Original Product', 'origin_type' => 'domestic', 'cost_price' => 10, 'retail_price' => 20, 'wholesale_price' => 15, 'current_stock' => 10, 'status' => 'active']);
        $invoice = $this->invoice();
        $snapshots = app(\App\Services\SalesFiscalSnapshotService::class);
        $invoice->forceFill($snapshots->invoiceAttributes($this->company->fresh(), \App\Models\Customer::findOrFail($this->customerId)))->save();
        $item = \App\Models\SalesItem::create(['created_by' => $this->admin->id, 'company_id' => $this->company->id, 'financial_year_id' => $this->financialYearId, 'sales_invoice_id' => $invoice->id, 'item_type' => 'product', 'product_id' => $product->id, 'quantity' => 1, 'returned_qty' => 0, 'unit_price' => 20, 'vat_rate' => 0, 'vat_amount' => 0, 'tax_classification' => 'vat_exempt', 'total_price' => 20]);
        $item->forceFill($snapshots->itemAttributes('product', $product->id, null, $this->company->id, true) + ['fiscal_discount_amount' => 0, 'fiscal_net_base' => 20])->save();
        $invoice->forceFill(['subtotal' => 20, 'discount' => 0, 'total_vat' => 0, 'grand_total' => 20, 'due_amount' => 20, 'fiscal_payment_mode' => 'credit', 'fiscal_payment_mode_captured_at' => now(), 'fiscal_issued_at' => now()])->save();

        $product->update(['name' => 'Changed Product']);
        $unit->update(['name' => 'Changed Unit', 'short_name' => 'changed']);
        $this->get(route('company.sales.print', $invoice->id))->assertOk()
            ->assertSee('TAX INVOICE')->assertSee('Original Product')->assertSee('pcs')->assertDontSee('Changed Product')->assertDontSee('changed');

        [$foreignCompany] = $this->foreignContext();
        $foreignUnit = \App\Models\Unit::create(['company_id' => $foreignCompany->id, 'name' => 'Foreign Unit', 'short_name' => 'foreign']);
        $foreignProduct = \App\Models\Product::create(['company_id' => $foreignCompany->id, 'unit_id' => $foreignUnit->id, 'name' => 'Foreign Product', 'cost_price' => 10, 'retail_price' => 20, 'wholesale_price' => 15, 'current_stock' => 10, 'status' => 'active']);
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $snapshots->itemAttributes('product', $foreignProduct->id, null, $this->company->id, true);
    }

    public function test_imported_product_fiscal_details_are_enforced_snapshotted_and_reused_by_returns(): void
    {
        $this->enableCbms();
        $unit = \App\Models\Unit::create(['company_id' => $this->company->id, 'name' => 'Pieces', 'short_name' => 'pcs']);
        $brand = \App\Models\Brand::create(['company_id' => $this->company->id, 'created_by' => $this->admin->id, 'name' => 'Original Brand', 'status' => 'active']);
        $product = \App\Models\Product::create([
            'company_id' => $this->company->id, 'unit_id' => $unit->id, 'brand_id' => $brand->id,
            'name' => 'Imported Exempt Device', 'origin_type' => 'imported', 'hs_code' => '084710',
            'product_type' => 'Computer', 'model' => 'X100', 'size' => '13 inch',
            'cost_price' => 10, 'retail_price' => 20, 'wholesale_price' => 15,
            'current_stock' => 10, 'status' => 'active',
        ]);
        $this->mock(SalesAccountingIntegrationService::class)->shouldReceive('postSale')->once();
        $this->mock(SalesCogsAccountingIntegrationService::class)->shouldReceive('postSaleCogs')->once();
        $this->mock(\App\Services\SalesInventoryCostService::class)->shouldReceive('snapshot')->once();

        $this->get(route('company.sales.create'))->assertOk();
        $number = session('pending_sales_invoice.invoice_no');
        $this->post(route('company.sales.store'), [
            'customer_id' => $this->customerId, 'sale_date' => '2026-06-15',
            'item_type' => ['product'], 'product_id' => [$product->id], 'service_id' => [null],
            'quantity' => [1], 'unit_price' => [20], 'vat_rate' => [0],
            'tax_classification' => ['vat_exempt'], 'discount_amount' => 0, 'paid_amount' => 0,
            'fiscal_hs_code' => ['9999'], 'fiscal_origin_type' => ['domestic'],
            'fiscal_brand_name' => ['Forged'], 'fiscal_model' => ['Forged'],
            'fiscal_size' => ['Forged'], 'company_id' => 999,
        ])->assertSessionHasNoErrors()->assertSessionHas('success');

        $invoice = SalesInvoice::where('invoice_no', $number)->sole();
        $line = $invoice->items()->sole();
        $this->assertSame('imported', $line->fiscal_origin_type);
        $this->assertSame('084710', $line->fiscal_hs_code);
        $this->assertSame('Original Brand', $line->fiscal_brand_name);
        $this->assertSame('Computer', $line->fiscal_product_type);
        $this->assertSame('X100', $line->fiscal_model);
        $this->assertSame('13 inch', $line->fiscal_size);
        $this->assertSame([], app(\App\Services\SalesFiscalSnapshotService::class)->itemDetailComplianceErrors($invoice));
        $this->assertNotContains('One or more imported sales lines have no valid immutable H.S. Code snapshot.', app(\App\Services\SalesFiscalReadinessService::class)->errors($invoice));
        $issuedEvent = \App\Models\FiscalDocumentAuditEvent::where('document_type', 'sales_invoice')
            ->where('document_id', $invoice->id)->where('event_type', 'invoice_issued')->sole();
        $this->assertTrue($issuedEvent->metadata['hs_compliance_complete']);

        $product->update(['origin_type' => 'domestic', 'hs_code' => '8473', 'product_type' => 'Accessory', 'model' => 'X200', 'size' => '15 inch']);
        $brand->update(['name' => 'Changed Brand']);
        foreach (['Original', 'Copy of Original (1)', 'Copy of Original (2)'] as $label) {
            $this->get(route('company.sales.print', $invoice->id))->assertOk()
                ->assertSee($label)->assertSee('084710')->assertSee('Original Brand')
                ->assertSee('Computer')->assertSee('X100')->assertSee('13 inch')
                ->assertDontSee('Changed Brand')->assertDontSee('X200');
        }

        $return = $this->salesReturn($invoice, 'Imported return');
        \App\Models\SalesReturnItem::create([
            'company_id' => $this->company->id, 'financial_year_id' => $this->financialYearId,
            'sales_return_id' => $return->id, 'sales_item_id' => $line->id,
            'product_id' => $product->id, 'quantity' => 1, 'unit_price' => 20,
            'vat_rate' => 0, 'vat_amount' => 0, 'tax_classification' => 'vat_exempt',
            'total_price' => 20, 'created_by' => $this->admin->id, 'status' => 1,
        ]);
        $this->get(route('company.sales-return.print', $return->id))->assertOk()
            ->assertSee('084710')->assertSee('Original Brand')->assertSee('X100')
            ->assertDontSee('Changed Brand')->assertDontSee('X200');
    }

    public function test_unknown_and_invalid_imported_products_block_cbms_issuance_but_domestic_product_does_not(): void
    {
        $this->enableCbms();
        $unit = \App\Models\Unit::create(['company_id' => $this->company->id, 'name' => 'Pieces', 'short_name' => 'pcs']);
        $snapshots = app(\App\Services\SalesFiscalSnapshotService::class);

        foreach ([
            ['Unknown Product', 'unknown', null, 'origin is not classified'],
            ['Imported Product', 'imported', null, 'requires a valid H.S. Code'],
            ['Short HS Product', 'imported', '123', 'requires a valid H.S. Code'],
        ] as [$name, $origin, $hsCode, $message]) {
            $product = \App\Models\Product::create([
                'company_id' => $this->company->id, 'unit_id' => $unit->id, 'name' => $name,
                'origin_type' => $origin, 'hs_code' => $hsCode, 'current_stock' => 10,
                'cost_price' => 10, 'retail_price' => 20, 'status' => 'active',
            ]);
            $this->assertSame([], $snapshots->itemAttributes('product', $product->id, null, $this->company->id, false));
            try {
                $snapshots->itemAttributes('product', $product->id, null, $this->company->id, true);
                $this->fail('Invalid fiscal product evidence was accepted.');
            } catch (\RuntimeException $exception) {
                $this->assertStringContainsString($message, $exception->getMessage());
            }
        }

        $domestic = \App\Models\Product::create([
            'company_id' => $this->company->id, 'unit_id' => $unit->id, 'name' => 'Domestic Product',
            'origin_type' => 'domestic', 'hs_code' => null, 'current_stock' => 10,
            'cost_price' => 10, 'retail_price' => 20, 'status' => 'active',
        ]);
        $attributes = $snapshots->itemAttributes('product', $domestic->id, null, $this->company->id, true);
        $this->assertSame('domestic', $attributes['fiscal_origin_type']);
        $this->assertNull($attributes['fiscal_hs_code']);

        $invalidForIssuance = \App\Models\Product::where('name', 'Imported Product')->sole();
        $this->get(route('company.sales.create'))->assertOk();
        $this->post(route('company.sales.store'), [
            'customer_id' => $this->customerId, 'sale_date' => '2026-06-15',
            'item_type' => ['product'], 'product_id' => [$invalidForIssuance->id], 'service_id' => [null],
            'quantity' => [1], 'unit_price' => [20], 'vat_rate' => [0],
            'tax_classification' => ['vat_exempt'], 'discount_amount' => 0, 'paid_amount' => 0,
        ])->assertSessionHas('error', 'This imported product requires a valid H.S. Code before issuing a Nepal CBMS invoice.');
        $this->assertDatabaseCount('sales_invoices', 0);
        $this->assertDatabaseCount('sales_items', 0);
        $this->assertDatabaseCount('fiscal_document_audit_events', 0);

        [$foreignCompany] = $this->foreignContext();
        $foreignUnit = \App\Models\Unit::create(['company_id' => $foreignCompany->id, 'name' => 'Foreign Unit', 'short_name' => 'f']);
        $foreignProduct = \App\Models\Product::create(['company_id' => $foreignCompany->id, 'unit_id' => $foreignUnit->id, 'name' => 'Foreign', 'origin_type' => 'imported', 'hs_code' => '8471', 'current_stock' => 1, 'cost_price' => 1, 'retail_price' => 2, 'status' => 'active']);
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $snapshots->itemAttributes('product', $foreignProduct->id, null, $this->company->id, true);
    }

    public function test_historical_invoice_without_snapshot_remains_detectable_and_is_not_backfilled(): void
    {
        $invoice = $this->invoice();
        $unit = \App\Models\Unit::create(['company_id' => $this->company->id, 'name' => 'Pieces', 'short_name' => 'pcs']);
        $product = \App\Models\Product::create(['company_id' => $this->company->id, 'unit_id' => $unit->id, 'name' => 'Historical Imported Product', 'origin_type' => 'imported', 'hs_code' => '8471', 'current_stock' => 1, 'cost_price' => 1, 'retail_price' => 500, 'status' => 'active']);
        $line = \App\Models\SalesItem::create(['created_by' => $this->admin->id, 'company_id' => $this->company->id, 'financial_year_id' => $this->financialYearId, 'sales_invoice_id' => $invoice->id, 'item_type' => 'product', 'product_id' => $product->id, 'quantity' => 1, 'returned_qty' => 0, 'unit_price' => 500, 'vat_rate' => 0, 'vat_amount' => 0, 'tax_classification' => 'vat_exempt', 'total_price' => 500]);
        $before = $invoice->getRawOriginal();
        $this->enableCbms();

        $this->assertFalse(app(\App\Services\SalesFiscalSnapshotService::class)->isComplete($invoice->fresh()));
        $this->assertContains('The sales invoice has no complete immutable fiscal identity snapshot.', app(\App\Services\SalesFiscalReadinessService::class)->errors($invoice->fresh()));
        $this->get(route('company.sales.show', $invoice->id))->assertOk();
        $this->assertNull($invoice->fresh()->fiscal_snapshot_captured_at);
        $this->assertNull($invoice->fresh()->fiscal_payment_mode);
        $this->assertNull($invoice->fresh()->fiscal_issued_at);
        $this->assertNull($line->fresh()->fiscal_origin_type);
        $this->assertNull($line->fresh()->fiscal_hs_code);
        $this->assertContains('One or more physical sales lines have no authoritative fiscal origin snapshot.', app(\App\Services\SalesFiscalReadinessService::class)->errors($invoice->fresh()));
        $this->assertContains('The sales invoice has no authoritative fiscal payment mode snapshot.', app(\App\Services\SalesFiscalReadinessService::class)->errors($invoice->fresh()));
        $this->assertContains('The sales invoice has no immutable fiscal issue timestamp.', app(\App\Services\SalesFiscalReadinessService::class)->errors($invoice->fresh()));
        $this->assertSame($before['grand_total'], $invoice->fresh()->getRawOriginal('grand_total'));
    }

    public function test_fiscal_invoice_stays_immutable_and_audited_after_setting_is_forced_off(): void
    {
        $invoice = $this->invoice('Permanent evidence');
        $this->enableCbms();
        $invoice->forceFill(['fiscal_issued_at' => now()])->save();
        app(FiscalDocumentAuditService::class)->recordIssued($this->company, 'sales_invoice', $invoice, $invoice->invoice_no, $this->admin->id);

        $this->get(route('company.sales.print', $invoice->id))->assertRedirect()->assertSessionHas('error');
        CompanyIrdCbmsSetting::where('company_id', $this->company->id)->update(['is_enabled' => false]);
        $invoice->refresh();

        $this->assertFalse(app(\App\Services\NepalIrdCbmsModeService::class)->isActiveForCompany($this->company->fresh()));
        $this->assertTrue(app(FiscalDocumentPolicyService::class)->wasFiscallyIssued($invoice));
        $this->get(route('company.sales.edit', $invoice->id))->assertSessionHas('error');
        $this->put(route('company.sales.update', $invoice->id), ['sale_date' => '2026-06-20', 'note' => 'Tampered'])
            ->assertSessionHas('error');
        $this->post(route('company.sales.cancel', $invoice->id), ['cancel_date' => '2026-06-17', 'cancel_reason' => 'Tampered'])
            ->assertSessionHas('error', FiscalDocumentPolicyService::ISSUED_INVOICE_MUTATION_MESSAGE);
        try {
            $invoice->delete();
            $this->fail('Permanently issued invoice deletion was not blocked.');
        } catch (\RuntimeException $exception) {
            $this->assertSame(FiscalDocumentPolicyService::ISSUED_INVOICE_MUTATION_MESSAGE, $exception->getMessage());
        }
        $this->get(route('company.sales.print', $invoice->id))->assertRedirect()->assertSessionHas('error');
        $this->assertDatabaseHas('fiscal_document_audit_events', ['document_id' => $invoice->id, 'event_type' => 'protected_update_blocked']);
        $this->assertDatabaseHas('fiscal_document_audit_events', ['document_id' => $invoice->id, 'event_type' => 'protected_cancel_blocked']);
        $this->assertDatabaseHas('fiscal_document_audit_events', ['document_id' => $invoice->id, 'event_type' => 'protected_delete_blocked']);
        $this->assertDatabaseMissing('fiscal_document_audit_events', ['document_id' => $invoice->id, 'event_type' => 'reprinted']);
    }

    private function enableCbms(): void
    {
        CompanyIrdCbmsSetting::updateOrCreate(['company_id' => $this->company->id], ['is_enabled' => true, 'updated_by' => $this->admin->id]);
    }

    private function markFiscallyIssued(SalesInvoice $invoice): void
    {
        $invoice->forceFill(['fiscal_issued_at' => now()])->save();
    }

    private function invoice(?string $note = null, ?int $companyId = null, ?int $fy = null, ?int $customer = null, ?int $creator = null, string $number = 'SI-CBMS-1'): SalesInvoice
    {
        return SalesInvoice::create(['created_by' => $creator ?? $this->admin->id, 'company_id' => $companyId ?? $this->company->id, 'financial_year_id' => $fy ?? $this->financialYearId, 'customer_id' => $customer ?? $this->customerId, 'invoice_no' => $number, 'sale_date' => '2026-06-15', 'subtotal' => 500, 'grand_total' => 500, 'paid_amount' => 0, 'due_amount' => 500, 'payment_status' => 'unpaid', 'note' => $note, 'status' => 1]);
    }

    private function salesReturn(SalesInvoice $invoice, string $note): SalesReturn
    {
        return SalesReturn::create(['company_id' => $this->company->id, 'financial_year_id' => $this->financialYearId, 'sales_invoice_id' => $invoice->id, 'customer_id' => $this->customerId, 'return_no' => 'SR-CBMS-'.$invoice->id, 'return_date' => '2026-06-16', 'subtotal' => 100, 'grand_total' => 100, 'adjust_amount' => 0, 'refund_amount' => 100, 'note' => $note, 'created_by' => $this->admin->id, 'status' => 1]);
    }

    private function foreignContext(): array
    {
        $country = Country::create(['name' => 'UAE', 'iso_code' => 'AE', 'is_active' => true]);
        $company = Company::create(['company_name' => 'Foreign Co', 'mobile' => '971500000001', 'email' => 'foreign-fiscal@example.test', 'status' => 'active']);
        DB::table('companies')->where('id', $company->id)->update(['country_id' => $country->id]);
        $admin = User::create(['name' => 'Foreign Admin', 'email' => 'foreign-fiscal-admin@example.test', 'password' => Hash::make('password'), 'role_id' => 2, 'company_id' => $company->id, 'account_status' => 'active']);
        $fy = DB::table('financial_years')->insertGetId(['company_id' => $company->id, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => 1, 'is_closed' => 0, 'is_locked' => 0, 'created_by' => $admin->id, 'created_at' => now(), 'updated_at' => now()]);
        $customer = DB::table('customers')->insertGetId(['company_id' => $company->id, 'created_by' => $admin->id, 'name' => 'Foreign Customer', 'opening_balance' => 0, 'current_balance' => 0, 'credit_days' => 0, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        return [$company->fresh(), $admin, $fy, $customer];
    }
}
