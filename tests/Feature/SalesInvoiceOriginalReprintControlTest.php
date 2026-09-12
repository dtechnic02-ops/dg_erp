<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckSubscription;
use App\Http\Middleware\EnsureCompanyUser;
use App\Http\Middleware\UpdateLastSeen;
use App\Models\Company;
use App\Models\CompanyIrdCbmsSetting;
use App\Models\Country;
use App\Models\SalesInvoice;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SalesInvoiceOriginalReprintControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_nepal_cbms_print_sequence_is_server_authoritative_durable_and_non_financial(): void
    {
        [$company, $user, $invoice] = $this->context('NP', true, 'A');
        $this->actingAs($user);
        $protectedFields = ['invoice_no', 'sale_date', 'subtotal', 'discount', 'total_vat', 'grand_total', 'paid_amount', 'due_amount', 'status'];
        $before = array_intersect_key($invoice->getRawOriginal(), array_flip($protectedFields));
        $accountingCount = DB::table('accounting_entries')->count();
        $stockCount = DB::table('stock_movements')->count();

        $this->get(route('company.sales.print', ['id' => $invoice->id, 'copy_number' => 99]))
            ->assertOk()->assertSee('Original')->assertDontSee('Copy of Original');
        $this->get(route('company.sales.print', ['id' => $invoice->id, 'reprint_number' => 99]))
            ->assertOk()->assertSee('Copy of Original (1)');
        $this->get(route('company.sales.print', ['id' => $invoice->id, 'is_original' => 1]))
            ->assertOk()->assertSee('Copy of Original (2)');

        $events = DB::table('fiscal_document_audit_events')
            ->where('company_id', $company->id)->where('document_type', 'sales_invoice')
            ->where('document_id', $invoice->id)->whereIn('event_type', ['original_printed', 'reprinted'])
            ->orderBy('id')->get();
        $this->assertSame(['original_printed', 'reprinted', 'reprinted'], $events->pluck('event_type')->all());
        $this->assertSame([0, 1, 2], $events->map(fn ($event) => json_decode($event->metadata, true)['print_number'])->all());
        $this->assertSame([$user->id, $user->id, $user->id], $events->pluck('actor_id')->map(fn ($id) => (int) $id)->all());
        $this->assertTrue($events->every(fn ($event) => $event->event_at !== null));
        $this->assertSame($before, array_intersect_key($invoice->fresh()->getRawOriginal(), array_flip($protectedFields)));
        $this->assertSame($accountingCount, DB::table('accounting_entries')->count());
        $this->assertSame($stockCount, DB::table('stock_movements')->count());
    }

    public function test_print_sequence_is_company_scoped_and_legacy_modes_remain_unlabelled(): void
    {
        [$companyA, $userA, $invoiceA] = $this->context('NP', true, 'A');
        [$companyB, $userB, $invoiceB] = $this->context('NP', true, 'B');
        [, $offUser, $offInvoice] = $this->context('NP', false, 'OFF');
        [, $foreignUser, $foreignInvoice] = $this->context('AE', true, 'FOREIGN');

        $this->actingAs($userA)->get(route('company.sales.print', $invoiceA->id))->assertOk()->assertSee('Original');
        $this->actingAs($userA)->get(route('company.sales.print', $invoiceA->id))->assertOk()->assertSee('Copy of Original (1)');
        $this->actingAs($userB)->get(route('company.sales.print', $invoiceB->id))->assertOk()->assertSee('Original')->assertDontSee('Copy of Original');

        $this->assertSame(2, DB::table('fiscal_document_audit_events')->where('company_id', $companyA->id)->where('document_id', $invoiceA->id)->whereIn('event_type', ['original_printed', 'reprinted'])->count());
        $this->assertSame(1, DB::table('fiscal_document_audit_events')->where('company_id', $companyB->id)->where('document_id', $invoiceB->id)->whereIn('event_type', ['original_printed', 'reprinted'])->count());

        $this->actingAs($offUser)->get(route('company.sales.print', $offInvoice->id))->assertOk()->assertDontSee('Original')->assertDontSee('Copy of Original');
        $this->actingAs($foreignUser)->get(route('company.sales.print', $foreignInvoice->id))->assertOk()->assertDontSee('Original')->assertDontSee('Copy of Original');
        $this->assertSame(3, DB::table('fiscal_document_audit_events')->count());
    }

    private function context(string $iso, bool $cbms, string $suffix): array
    {
        $country = Country::firstOrCreate(['iso_code' => $iso], ['name' => $iso === 'NP' ? 'Nepal' : $iso, 'is_active' => true]);
        $company = Company::create(['company_name' => 'Print '.$suffix, 'mobile' => '98'.str_pad((string) random_int(1, 99999999), 8, '0', STR_PAD_LEFT), 'email' => 'print-'.strtolower($suffix).'@example.test', 'country_id' => $country->id, 'status' => 'active']);
        DB::table('roles')->insertOrIgnore(['id' => 2, 'name' => 'company_admin', 'created_at' => now(), 'updated_at' => now()]);
        $user = User::create(['name' => 'Print User '.$suffix, 'email' => 'print-user-'.strtolower($suffix).'@example.test', 'password' => Hash::make('password'), 'role_id' => 2, 'company_id' => $company->id, 'account_status' => 'active']);
        $fy = DB::table('financial_years')->insertGetId(['company_id' => $company->id, 'name' => '2026 '.$suffix, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => 1, 'is_closed' => 0, 'is_locked' => 0, 'created_by' => $user->id, 'created_at' => now(), 'updated_at' => now()]);
        $customer = DB::table('customers')->insertGetId(['company_id' => $company->id, 'created_by' => $user->id, 'name' => 'Customer '.$suffix, 'opening_balance' => 0, 'current_balance' => 0, 'credit_days' => 0, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        $invoice = SalesInvoice::create(['created_by' => $user->id, 'company_id' => $company->id, 'financial_year_id' => $fy, 'customer_id' => $customer, 'invoice_no' => 'SI-'.$suffix, 'sale_date' => '2026-06-15', 'subtotal' => 500, 'discount' => 0, 'total_vat' => 0, 'grand_total' => 500, 'paid_amount' => 0, 'due_amount' => 500, 'payment_status' => 'unpaid', 'status' => 1]);
        CompanyIrdCbmsSetting::create(['company_id' => $company->id, 'is_enabled' => $cbms, 'updated_by' => $user->id]);
        if ($iso === 'NP' && $cbms) {
            $invoice->forceFill([
                'fiscal_snapshot_captured_at' => now(),
                'seller_name_snapshot' => 'Print '.$suffix,
                'seller_address_snapshot' => 'Seller Address '.$suffix,
                'seller_pan_snapshot' => '123456789',
                'seller_vat_snapshot' => '987654321',
                'buyer_name_snapshot' => 'Customer '.$suffix,
                'buyer_address_snapshot' => 'Buyer Address '.$suffix,
                'buyer_tax_no_snapshot' => null,
                'fiscal_payment_mode' => 'credit',
                'fiscal_payment_mode_captured_at' => now(),
                'fiscal_issued_at' => now(),
            ])->save();
            DB::table('sales_items')->insert([
                'created_by' => $user->id, 'company_id' => $company->id,
                'financial_year_id' => $fy, 'sales_invoice_id' => $invoice->id,
                'item_type' => 'service', 'quantity' => 1, 'returned_qty' => 0,
                'unit_price' => 500, 'vat_rate' => 0, 'vat_amount' => 0,
                'fiscal_discount_amount' => 0, 'fiscal_net_base' => 500,
                'tax_classification' => 'vat_exempt', 'total_price' => 500,
                'item_name_snapshot' => 'Print Service '.$suffix,
                'unit_name_snapshot' => 'Service',
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $this->withoutMiddleware([EnsureCompanyUser::class, CheckSubscription::class, UpdateLastSeen::class, VerifyCsrfToken::class]);

        return [$company->fresh(), $user, $invoice];
    }
}
