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

    public function test_company_customer_store_does_not_post_embedded_opening_balance(): void
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
        $this->assertSame(5, $company->id);
        $this->assertSame(5, $customer->company_id);
        $this->assertSame('0.00', number_format((float) $customer->opening_balance, 2, '.', ''));
        $this->assertSame(0, CustomerTransaction::query()->where('customer_id', $customer->id)->count());
        $this->assertSame(0, AccountingEntry::query()->where('company_id', $company->id)->count());
        $this->assertSame(0, Customer::query()->where('company_id', $otherCompany->id)->count());
        $this->assertSame(0, CustomerTransaction::query()->where('company_id', $otherCompany->id)->count());
        $this->assertSame(0, AccountingEntry::query()->where('company_id', $otherCompany->id)->count());
    }

    public function test_customer_store_no_longer_requires_opening_balance_chart_mapping(): void
    {
        [$company, $user] = $this->createAuthenticatedCompanyFiveContext(false);

        $this->actingAs($user)->post(route('company.customers.store'), $this->customerPayload('1250.50'))->assertRedirect();
        $this->assertSame(1, Customer::query()->where('company_id', $company->id)->count());
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
