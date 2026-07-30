<?php

namespace Tests\Feature\Company;

use App\Models\AccountingEntry;
use App\Models\ChartAccount;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerTransaction;
use App\Models\Role;
use App\Models\User;
use RuntimeException;
use Tests\Concerns\CreatesCompanyRouteTestFoundation;
use Tests\TestCase;

class CustomerOpeningBalanceAccountingRouteTest extends TestCase
{
    use CreatesCompanyRouteTestFoundation;

    public function test_company_customer_store_posts_opening_balance_to_general_accounting(): void
    {
        [$company, $user] = $this->createAuthenticatedCompanyFiveContext(true);
        $otherCompany = Company::create([
            'company_name' => 'Other Company',
            'email' => 'other-company@example.test',
            'mobile' => '9800000001',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->post(route('company.customers.store'), $this->customerPayload('1250.50'));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Customer Added');

        $customer = Customer::query()
            ->where('company_id', $company->id)
            ->where('name', 'Opening Balance Route Test Customer')
            ->sole();
        $transaction = CustomerTransaction::query()
            ->where('company_id', $company->id)
            ->where('customer_id', $customer->id)
            ->where('reference_type', 'opening_balance')
            ->where('reference_id', $customer->id)
            ->sole();
        $entry = AccountingEntry::query()
            ->with('lines.chartAccount')
            ->where('company_id', $company->id)
            ->where('source_module', 'customer')
            ->where('source_type', 'customer_opening_balance')
            ->where('source_id', $customer->id)
            ->where('source_event', 'created')
            ->where('source_key', 'customer-opening-balance:' . $customer->id)
            ->sole();

        $linesBySystemCode = $entry->lines->keyBy(fn ($line) => $line->chartAccount->system_code);

        $this->assertSame(5, $company->id);
        $this->assertSame(5, $customer->company_id);
        $this->assertSame('1250.50', number_format((float) $customer->opening_balance, 2, '.', ''));
        $this->assertSame('1250.5000', number_format((float) $transaction->debit, 4, '.', ''));
        $this->assertSame('0.0000', number_format((float) $transaction->credit, 4, '.', ''));
        $this->assertSame(5, $transaction->company_id);
        $this->assertSame($customer->id, $transaction->customer_id);
        $this->assertSame('customer', $entry->source_module);
        $this->assertSame('customer_opening_balance', $entry->source_type);
        $this->assertSame($customer->id, $entry->source_id);
        $this->assertSame('created', $entry->source_event);
        $this->assertSame('customer-opening-balance:' . $customer->id, $entry->source_key);
        $this->assertSame(5, $entry->company_id);
        $this->assertSame('1250.5000', $linesBySystemCode['ACCOUNTS_RECEIVABLE']->debit);
        $this->assertSame('0.0000', $linesBySystemCode['ACCOUNTS_RECEIVABLE']->credit);
        $this->assertSame('customer', $linesBySystemCode['ACCOUNTS_RECEIVABLE']->subledger_type);
        $this->assertSame($customer->id, $linesBySystemCode['ACCOUNTS_RECEIVABLE']->subledger_id);
        $this->assertSame('0.0000', $linesBySystemCode['OPENING_BALANCE_EQUITY']->debit);
        $this->assertSame('1250.5000', $linesBySystemCode['OPENING_BALANCE_EQUITY']->credit);
        $this->assertSame('1250.5000', number_format((float) $entry->lines->sum('debit'), 4, '.', ''));
        $this->assertSame('1250.5000', number_format((float) $entry->lines->sum('credit'), 4, '.', ''));
        $this->assertSame(1, AccountingEntry::query()->where('source_key', $entry->source_key)->count());
        $this->assertSame(0, Customer::query()->where('company_id', $otherCompany->id)->count());
        $this->assertSame(0, CustomerTransaction::query()->where('company_id', $otherCompany->id)->count());
        $this->assertSame(0, AccountingEntry::query()->where('company_id', $otherCompany->id)->count());
    }

    public function test_customer_store_rolls_back_when_required_system_account_is_missing(): void
    {
        [$company, $user] = $this->createAuthenticatedCompanyFiveContext(false);

        $this->withoutExceptionHandling();

        try {
            $this->actingAs($user)->post(route('company.customers.store'), $this->customerPayload('1250.50'));
            $this->fail('The missing ACCOUNTS_RECEIVABLE account should reject the request.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Chart account system code [ACCOUNTS_RECEIVABLE] could not be resolved for this company.', $exception->getMessage());
        }

        $this->assertSame(0, Customer::query()->where('company_id', $company->id)->count());
        $this->assertSame(0, CustomerTransaction::query()->where('company_id', $company->id)->count());
        $this->assertSame(0, AccountingEntry::query()->where('company_id', $company->id)->count());
        $this->assertSame(0, \App\Models\AccountingEntryLine::query()->count());
    }

    private function createAuthenticatedCompanyFiveContext(bool $includeReceivable): array
    {
        $this->createCompanyRouteTestSchema();

        $role = $this->createCompanyDashboardRole();
        $company = new Company([
            'company_name' => 'Company Five',
            'email' => 'company-five@example.test',
            'mobile' => '9800000005',
            'status' => 'active',
        ]);
        $company->id = 5;
        $company->save();

        $user = $this->createCompanyAdmin($company, $role);
        $plan = $this->createActiveSubscriptionPlan();
        $this->createOperationalCompanySubscription($company, $plan);
        $this->createActiveFinancialYear($company, $user);
        $this->createOpeningBalanceChartAccounts($company, $user, $includeReceivable);

        return [$company, $user];
    }

    private function createOpeningBalanceChartAccounts(Company $company, User $user, bool $includeReceivable): void
    {
        $definitions = [
            ['code' => '3140', 'name' => 'Opening Balance Equity', 'account_class' => 'equity', 'account_category' => 'opening_balance_equity', 'normal_balance' => 'credit', 'system_code' => 'OPENING_BALANCE_EQUITY', 'is_control' => false],
        ];

        if ($includeReceivable) {
            $definitions[] = ['code' => '1130', 'name' => 'Accounts Receivable', 'account_class' => 'asset', 'account_category' => 'receivable', 'normal_balance' => 'debit', 'system_code' => 'ACCOUNTS_RECEIVABLE', 'is_control' => true];
        }

        foreach ($definitions as $definition) {
            ChartAccount::create($definition + [
                'company_id' => $company->id,
                'level' => 3,
                'sort_order' => (int) $definition['code'],
                'is_system' => true,
                'allow_manual_entry' => false,
                'status' => 'active',
                'created_by' => $user->id,
            ]);
        }
    }

    private function customerPayload(string $openingBalance): array
    {
        return [
            'name' => 'Opening Balance Route Test Customer',
            'mobile' => '9800000002',
            'opening_balance' => $openingBalance,
            'credit_days' => 0,
            'status' => 'active',
        ];
    }
}
