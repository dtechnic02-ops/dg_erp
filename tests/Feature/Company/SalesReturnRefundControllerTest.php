<?php

namespace Tests\Feature\Company;

use App\Models\Account;
use App\Models\AccountingEntry;
use App\Models\AccountTransaction;
use App\Models\ChartAccount;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\CustomerTransaction;
use App\Models\FinancialYear;
use App\Models\SalesReturn;
use App\Models\SalesReturnRefund;
use App\Models\SalesReturnItem;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Database\UniqueConstraintViolationException;
use Tests\Concerns\CreatesCompanyRouteTestFoundation;
use Tests\TestCase;

class SalesReturnRefundControllerTest extends TestCase
{
    use CreatesCompanyRouteTestFoundation;

    public function test_rejects_duplicate_invoice_ids_in_one_refund_request(): void
    {
        $context = $this->createRefundContext();
        $invoiceId = $context['sale']['salesInvoice']->id;

        $response = $this->post(route('company.sales-return-refund.store'), $this->payload($context, [
            'sales_invoice_id' => [$invoiceId, $invoiceId],
            'adjust_amount' => ['40.00', '40.00'],
        ]));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Duplicate invoice selected for adjustment.');
        $this->assertDatabaseCount('sales_return_refunds', 0);
        $this->assertSame('0.0000', number_format((float) $context['return']->fresh()->adjust_amount, 4, '.', ''));
        $this->assertSame('500.0000', number_format((float) $context['sale']['salesInvoice']->fresh()->due_amount, 4, '.', ''));
    }

    public function test_rejects_duplicate_browser_submission_without_persisting_a_second_refund(): void
    {
        $context = $this->createRefundContext();
        $token = (string) Str::uuid();
        $payload = $this->payload($context, [
            'idempotency_key' => $token,
            'sales_invoice_id' => [$context['sale']['salesInvoice']->id],
            'adjust_amount' => ['50.00'],
        ]);

        $this->post(route('company.sales-return-refund.store'), $payload)
            ->assertRedirect();

        $response = $this->post(route('company.sales-return-refund.store'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'This refund request has already been submitted.');
        $this->assertDatabaseCount('sales_return_refunds', 1);
        $this->assertDatabaseCount('sales_return_refund_adjustments', 1);
        $this->assertDatabaseCount('accounting_entries', 3);
    }

    public function test_cancellation_rolls_back_when_expected_customer_transaction_is_missing(): void
    {
        $context = $this->createRefundContext();
        $refund = $this->postCashRefund($context);

        CustomerTransaction::where('reference_id', $refund->id)
            ->where('reference_type', 'sales_return_refund')
            ->delete();
        $counts = $this->refundCounts();

        $response = $this->post(route('company.sales-return-refund.cancel', $refund->id), [
            'cancel_date' => '2026-06-21',
            'cancel_reason' => 'Verification failure',
        ]);

        $response->assertRedirect();
        $this->assertSame(SalesReturnRefund::STATUS_ACTIVE, $refund->fresh()->status);
        $this->assertSame($counts['refunds'], SalesReturnRefund::count());
        $this->assertSame($counts['account_transactions'], AccountTransaction::count());
        $this->assertSame($counts['accounting_entries'], AccountingEntry::count());
        $this->assertSame('0.0000', number_format((float) $context['return']->fresh()->refund_amount, 4, '.', ''));
        $this->assertSame('100.0000', number_format((float) $context['return']->fresh()->adjust_amount, 4, '.', ''));
    }

    public function test_cancellation_rolls_back_when_expected_account_transaction_is_missing(): void
    {
        $context = $this->createRefundContext();
        $refund = $this->postCashRefund($context);

        AccountTransaction::where('reference_id', $refund->id)
            ->where('reference_type', 'sales_return_refund')
            ->delete();
        $counts = $this->refundCounts();

        $response = $this->post(route('company.sales-return-refund.cancel', $refund->id), [
            'cancel_date' => '2026-06-21',
            'cancel_reason' => 'Verification failure',
        ]);

        $response->assertRedirect();
        $this->assertSame(SalesReturnRefund::STATUS_ACTIVE, $refund->fresh()->status);
        $this->assertSame($counts['refunds'], SalesReturnRefund::count());
        $this->assertSame($counts['customer_transactions'], CustomerTransaction::count());
        $this->assertSame($counts['accounting_entries'], AccountingEntry::count());
        $this->assertSame('0.0000', number_format((float) $context['return']->fresh()->refund_amount, 4, '.', ''));
        $this->assertSame('100.0000', number_format((float) $context['return']->fresh()->adjust_amount, 4, '.', ''));
    }

    public function test_refund_date_edit_synchronizes_linked_ledgers_and_accounting_entry(): void
    {
        $context = $this->createRefundContext();
        $refund = $this->postCashRefund($context);

        $response = $this->post(route('company.sales-return-refund.update', $refund->id), [
            'refund_date' => '2026-06-22',
            'note' => 'Corrected business date',
        ]);

        $response->assertRedirect(route('company.sales-return-refund.show', $refund->id));
        $this->assertSame('2026-06-22', $refund->fresh()->refund_date->toDateString());
        $this->assertSame('2026-06-22', AccountTransaction::where('reference_id', $refund->id)->sole()->transaction_date);
        $this->assertSame('2026-06-22', CustomerTransaction::where('reference_id', $refund->id)
            ->where('reference_type', 'sales_return_refund')
            ->sole()
            ->transaction_date);
        $this->assertSame('2026-06-22', AccountingEntry::where('source_key', 'sales_return_refund:' . $refund->id . ':created')->sole()->entry_date->toDateString());
    }

    public function test_rejects_invoice_adjustment_from_an_inactive_financial_year(): void
    {
        $context = $this->createRefundContext();
        $oldFinancialYear = FinancialYear::create([
            'company_id' => $context['company']->id,
            'name' => 'FY 2025',
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'is_active' => false,
            'created_by' => $context['user']->id,
        ]);
        $context['sale']['salesInvoice']->update(['financial_year_id' => $oldFinancialYear->id]);

        $response = $this->post(route('company.sales-return-refund.store'), $this->payload($context, [
            'sales_invoice_id' => [$context['sale']['salesInvoice']->id],
            'adjust_amount' => ['50.00'],
        ]));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Invoice adjustment must belong to the active financial year.');
        $this->assertDatabaseCount('sales_return_refunds', 0);
        $this->assertSame('500.0000', number_format((float) $context['sale']['salesInvoice']->fresh()->due_amount, 4, '.', ''));
    }

    public function test_all_supported_settlement_modes_post_exact_balanced_accounting_lines(): void
    {
        foreach ([
            'adjustment_only' => ['adjust' => '100.00', 'cash' => '0.00', 'account_type' => null, 'operational_code' => 'ACCOUNTS_RECEIVABLE'],
            'cash_only' => ['adjust' => '0.00', 'cash' => '100.00', 'account_type' => 'Cash', 'operational_code' => 'CASH_IN_HAND'],
            'bank_only' => ['adjust' => '0.00', 'cash' => '100.00', 'account_type' => 'Bank', 'operational_code' => 'BANK_ACCOUNTS'],
            'mixed_adjustment_cash' => ['adjust' => '50.00', 'cash' => '50.00', 'account_type' => 'Cash', 'operational_code' => 'CASH_IN_HAND'],
            'mixed_adjustment_bank' => ['adjust' => '50.00', 'cash' => '50.00', 'account_type' => 'Bank', 'operational_code' => 'BANK_ACCOUNTS'],
        ] as $mode => $expectation) {
            $context = $this->createRefundContext();
            $account = null;

            if ($expectation['account_type']) {
                $account = $expectation['account_type'] === 'Cash'
                    ? $context['cashAccount']
                    : Account::create([
                        'company_id' => $context['company']->id,
                        'account_type' => 'Bank',
                        'account_name' => 'Refund Bank',
                        'current_balance' => '1000.0000',
                        'status' => 'active',
                    ]);
            }

            $payload = $this->payload($context, [
                'cash_amount' => $expectation['cash'],
                'account_id' => $account?->id,
                'sales_invoice_id' => $expectation['adjust'] === '0.00' ? [] : [$context['sale']['salesInvoice']->id],
                'adjust_amount' => $expectation['adjust'] === '0.00' ? [] : [$expectation['adjust']],
            ]);

            $response = $this->post(route('company.sales-return-refund.store'), $payload);
            $response->assertRedirect();
            $this->assertSame(
                'Sales return refund created successfully.',
                $response->getSession()->get('success'),
                $mode . ' failed: ' . (string) $response->getSession()->get('error')
            );

            $refund = SalesReturnRefund::query()->sole();
            $entry = AccountingEntry::query()
                ->with('lines.chartAccount')
                ->where('company_id', $context['company']->id)
                ->where('source_key', 'sales_return_refund:' . $refund->id . ':created')
                ->sole();
            $lines = $entry->lines->keyBy(fn ($line) => $line->chartAccount->system_code);

            $this->assertSame($mode === 'adjustment_only' ? 1 : 1, SalesReturnRefund::count());
            $this->assertSame('100.0000', number_format((float) $lines['SALES_RETURNS']->debit, 4, '.', ''));
            $this->assertSame('0.0000', number_format((float) $lines['SALES_RETURNS']->credit, 4, '.', ''));
            $this->assertSame('100.0000', number_format((float) $entry->lines->sum('debit'), 4, '.', ''));
            $this->assertSame('100.0000', number_format((float) $entry->lines->sum('credit'), 4, '.', ''));
            $this->assertSame('sales_return_refund', $entry->source_module);
            $this->assertSame('sales_return_refund', $entry->source_type);
            $this->assertSame($refund->id, $entry->source_id);
            $this->assertSame('created', $entry->source_event);
            $this->assertSame($expectation['adjust'] === '0.00' ? '0.0000' : number_format((float) $expectation['adjust'], 4, '.', ''), number_format((float) ($lines['ACCOUNTS_RECEIVABLE']->credit ?? 0), 4, '.', ''));
            if ($expectation['operational_code'] !== 'ACCOUNTS_RECEIVABLE') {
                $this->assertSame($expectation['cash'] === '0.00' ? '0.0000' : number_format((float) $expectation['cash'], 4, '.', ''), number_format((float) ($lines[$expectation['operational_code']]->credit ?? 0), 4, '.', ''));
            }
            $this->assertSame($expectation['cash'] === '0.00' ? 0 : 1, AccountTransaction::query()->where('reference_id', $refund->id)->count());
            $this->assertSame($expectation['adjust'] === '0.00' ? 0 : 1, CustomerTransaction::query()->where('reference_type', 'sales_return_refund_adjustment')->where('reference_id', $refund->id)->count());
        }
    }

    public function test_tax_refund_posts_output_tax_payable_and_remains_balanced(): void
    {
        $context = $this->createRefundContext();
        $context['return']->update(['subtotal' => '100.0000', 'total_vat' => '10.0000', 'grand_total' => '110.0000', 'refund_amount' => '110.0000']);
        SalesReturnItem::query()->where('sales_return_id', $context['return']->id)->update(['total_price' => '110.0000', 'vat_amount' => '10.0000']);

        $this->post(route('company.sales-return-refund.store'), $this->payload($context, [
            'account_id' => $context['cashAccount']->id,
            'cash_amount' => '110.00',
        ]))->assertRedirect()->assertSessionHas('success', 'Sales return refund created successfully.');

        $refund = SalesReturnRefund::query()->sole();
        $entry = AccountingEntry::query()->with('lines.chartAccount')
            ->where('source_key', 'sales_return_refund:' . $refund->id . ':created')
            ->sole();
        $lines = $entry->lines->keyBy(fn ($line) => $line->chartAccount->system_code);

        $this->assertSame('100.0000', number_format((float) $lines['SALES_RETURNS']->debit, 4, '.', ''));
        $this->assertSame('10.0000', number_format((float) $lines['OUTPUT_TAX_PAYABLE']->debit, 4, '.', ''));
        $this->assertSame('110.0000', number_format((float) $lines['CASH_IN_HAND']->credit, 4, '.', ''));
        $this->assertSame('110.0000', number_format((float) $entry->lines->sum('debit'), 4, '.', ''));
        $this->assertSame('110.0000', number_format((float) $entry->lines->sum('credit'), 4, '.', ''));
    }

    public function test_idempotency_key_is_company_scoped_and_database_unique_constraint_is_enforced(): void
    {
        $context = $this->createRefundContext();
        $key = (string) Str::uuid();
        $base = [
            'financial_year_id' => $context['financialYear']->id,
            'sales_return_id' => $context['return']->id,
            'customer_id' => $context['sale']['customer']->id,
            'refund_no' => 'DIRECT-ONE',
            'refund_date' => '2026-06-20',
            'refund_amount' => '1.00',
            'adjust_amount' => '1.00',
            'cash_amount' => '0.00',
            'idempotency_key' => $key,
            'status' => SalesReturnRefund::STATUS_ACTIVE,
        ];

        SalesReturnRefund::create(array_merge($base, ['company_id' => $context['company']->id]));
        SalesReturnRefund::create(array_merge($base, ['company_id' => 2, 'refund_no' => 'DIRECT-TWO']));
        $this->assertSame(2, SalesReturnRefund::query()->where('idempotency_key', $key)->count());

        $this->expectException(UniqueConstraintViolationException::class);
        SalesReturnRefund::create(array_merge($base, ['company_id' => $context['company']->id, 'refund_no' => 'DIRECT-THREE']));
    }

    public function test_successful_cancellation_reverses_accounting_once_without_inventory_side_effects(): void
    {
        $context = $this->createRefundContext();
        $refund = $this->postCashRefund($context);
        $inventoryBefore = [
            'stock' => DB::table('stock_movements')->count(),
            'valuation' => DB::table('inventory_valuations')->count(),
        ];

        $this->post(route('company.sales-return-refund.cancel', $refund->id), [
            'cancel_date' => '2026-06-21',
            'cancel_reason' => 'Sprint 3 cancellation verification',
        ])->assertRedirect()->assertSessionHas('success', 'Refund cancelled successfully.');

        $original = AccountingEntry::query()->where('source_key', 'sales_return_refund:' . $refund->id . ':created')->sole();
        $reversal = AccountingEntry::query()->with('lines.chartAccount')->where('source_key', 'sales_return_refund_cancel:' . $refund->id . ':cancelled')->sole();
        $this->assertSame(SalesReturnRefund::STATUS_CANCELLED, $refund->fresh()->status);
        $this->assertSame('reversed', $original->status);
        $this->assertSame($original->id, $reversal->reversal_of_id);
        $this->assertSame('cancelled', $reversal->source_event);
        $this->assertSame($inventoryBefore['stock'], DB::table('stock_movements')->count());
        $this->assertSame($inventoryBefore['valuation'], DB::table('inventory_valuations')->count());

        $second = $this->post(route('company.sales-return-refund.cancel', $refund->id), [
            'cancel_date' => '2026-06-21',
            'cancel_reason' => 'Second attempt',
        ]);
        $second->assertRedirect();
        $this->assertSame(1, AccountingEntry::query()->where('source_key', 'sales_return_refund_cancel:' . $refund->id . ':cancelled')->count());
    }

    public function test_invalid_bank_balance_and_invalid_date_update_roll_back_without_partial_records(): void
    {
        $context = $this->createRefundContext();
        $bank = Account::create([
            'company_id' => $context['company']->id,
            'account_type' => 'Bank',
            'account_name' => 'Insufficient Bank',
            'current_balance' => '1.0000',
            'status' => 'active',
        ]);

        $this->post(route('company.sales-return-refund.store'), $this->payload($context, [
            'account_id' => $bank->id,
            'cash_amount' => '100.00',
        ]))->assertRedirect()->assertSessionHas('error', 'Insufficient account balance.');
        $this->assertDatabaseCount('sales_return_refunds', 0);
        $this->assertDatabaseCount('accounting_entries', 2);
        $this->assertDatabaseCount('account_transactions', 0);

        $refund = $this->postCashRefund($context);
        $before = [
            'refund_date' => $refund->refund_date->toDateString(),
            'account_date' => AccountTransaction::where('reference_id', $refund->id)->sole()->transaction_date,
            'customer_date' => CustomerTransaction::where('reference_type', 'sales_return_refund')->where('reference_id', $refund->id)->sole()->transaction_date,
            'entry_date' => AccountingEntry::where('source_key', 'sales_return_refund:' . $refund->id . ':created')->sole()->entry_date->toDateString(),
        ];
        $this->post(route('company.sales-return-refund.update', $refund->id), ['refund_date' => '2025-12-31', 'note' => 'Invalid date'])
            ->assertRedirect()
            ->assertSessionHas('error', 'Selected date must fall within the active financial year.');
        $this->assertSame($before['refund_date'], $refund->fresh()->refund_date->toDateString());
        $this->assertSame($before['account_date'], AccountTransaction::where('reference_id', $refund->id)->sole()->transaction_date);
        $this->assertSame($before['customer_date'], CustomerTransaction::where('reference_type', 'sales_return_refund')->where('reference_id', $refund->id)->sole()->transaction_date);
        $this->assertSame($before['entry_date'], AccountingEntry::where('source_key', 'sales_return_refund:' . $refund->id . ':created')->sole()->entry_date->toDateString());
    }

    public function test_foreign_company_user_cannot_update_or_cancel_another_company_refund(): void
    {
        $context = $this->createRefundContext();
        $refund = $this->postCashRefund($context);
        $otherCompany = Company::create([
            'company_name' => 'Foreign Company',
            'email' => 'foreign-company@example.test',
            'mobile' => '9800000009',
            'status' => 'active',
        ]);
        $otherUser = User::create([
            'name' => 'Foreign Company Admin',
            'email' => 'foreign-admin@example.test',
            'password' => bcrypt('password'),
            'role_id' => 2,
            'company_id' => $otherCompany->id,
            'account_status' => 'active',
        ]);
        $this->createOperationalCompanySubscription($otherCompany, SubscriptionPlan::query()->sole());
        $this->createActiveFinancialYear($otherCompany, $otherUser);
        $entryCount = AccountingEntry::count();

        $this->actingAs($otherUser)->post(route('company.sales-return-refund.update', $refund->id), [
            'refund_date' => '2026-06-22',
            'note' => 'Foreign edit attempt',
        ])->assertRedirect();
        $this->actingAs($otherUser)->post(route('company.sales-return-refund.cancel', $refund->id), [
            'cancel_date' => '2026-06-22',
            'cancel_reason' => 'Foreign cancel attempt',
        ])->assertRedirect();

        $this->assertSame(SalesReturnRefund::STATUS_ACTIVE, $refund->fresh()->status);
        $this->assertSame('2026-06-20', $refund->fresh()->refund_date->toDateString());
        $this->assertSame($entryCount, AccountingEntry::count());
        $this->assertSame(0, SalesReturnRefund::query()->where('company_id', $otherCompany->id)->count());
    }

    private function createRefundContext(): array
    {
        $this->createCompanyRouteTestSchema();
        $role = $this->createCompanyDashboardRole();
        $company = $this->createActiveCompany();
        $user = $this->createCompanyAdmin($company, $role);
        $this->createOperationalCompanySubscription($company, $this->createActiveSubscriptionPlan());
        $this->authenticateCompanyAdmin($user);
        $financialYear = $this->createActiveFinancialYear($company, $user);
        $sale = $this->createPostedProductSale(
            $company,
            $user,
            $financialYear,
            $this->createActiveWarehouse($company),
            $this->createCustomer($company, $user),
            $this->createProduct($company)
        );

        $this->createRefundChartAccounts($company, $user);

        $return = SalesReturn::create([
            'company_id' => $company->id,
            'financial_year_id' => $financialYear->id,
            'sales_invoice_id' => $sale['salesInvoice']->id,
            'customer_id' => $sale['customer']->id,
            'return_no' => 'SR-REFUND-0001',
            'return_date' => '2026-06-20',
            'subtotal' => '100.0000',
            'total_vat' => '0.0000',
            'grand_total' => '100.0000',
            'adjust_amount' => '0.0000',
            'refund_amount' => '100.0000',
            'created_by' => $user->id,
            'status' => 1,
        ]);

        SalesReturnItem::create([
            'company_id' => $company->id,
            'financial_year_id' => $financialYear->id,
            'sales_return_id' => $return->id,
            'sales_item_id' => $sale['salesItem']->id,
            'product_id' => $sale['product']->id,
            'quantity' => '0.400000',
            'unit_price' => '250.0000',
            'vat_amount' => '0.0000',
            'total_price' => '100.0000',
            'created_by' => $user->id,
            'status' => 1,
        ]);

        $cashAccount = Account::create([
            'company_id' => $company->id,
            'account_type' => 'Cash',
            'account_name' => 'Refund Cash',
            'current_balance' => '1000.0000',
            'status' => 'active',
        ]);

        return compact('company', 'user', 'financialYear', 'sale', 'return', 'cashAccount');
    }

    private function createRefundChartAccounts($company, $user): void
    {
        foreach ([
            ['code' => '4090', 'name' => 'Sales Returns', 'account_class' => 'income', 'normal_balance' => 'debit', 'system_code' => 'SALES_RETURNS'],
            ['code' => '2100', 'name' => 'Output Tax Payable', 'account_class' => 'liability', 'normal_balance' => 'credit', 'system_code' => 'OUTPUT_TAX_PAYABLE'],
            ['code' => '1000', 'name' => 'Cash In Hand', 'account_class' => 'asset', 'normal_balance' => 'debit', 'system_code' => 'CASH_IN_HAND'],
            ['code' => '1010', 'name' => 'Bank Accounts', 'account_class' => 'asset', 'normal_balance' => 'debit', 'system_code' => 'BANK_ACCOUNTS'],
        ] as $definition) {
            ChartAccount::create($definition + [
                'company_id' => $company->id,
                'created_by' => $user->id,
                'is_system' => true,
                'allow_manual_entry' => false,
                'status' => 'active',
            ]);
        }
    }

    private function postCashRefund(array $context): SalesReturnRefund
    {
        $this->post(route('company.sales-return-refund.store'), $this->payload($context, [
            'account_id' => $context['cashAccount']->id,
            'cash_amount' => '100.00',
        ]))->assertRedirect()->assertSessionHas('success', 'Sales return refund created successfully.');

        return SalesReturnRefund::query()->sole();
    }

    private function payload(array $context, array $overrides = []): array
    {
        return array_merge([
            'sales_return_id' => $context['return']->id,
            'idempotency_key' => (string) Str::uuid(),
            'refund_date' => '2026-06-20',
            'sales_invoice_id' => [],
            'adjust_amount' => [],
            'cash_amount' => '0.00',
            'note' => 'Focused refund test',
        ], $overrides);
    }

    private function refundCounts(): array
    {
        return [
            'refunds' => SalesReturnRefund::count(),
            'customer_transactions' => CustomerTransaction::count(),
            'account_transactions' => AccountTransaction::count(),
            'accounting_entries' => AccountingEntry::count(),
        ];
    }
}
