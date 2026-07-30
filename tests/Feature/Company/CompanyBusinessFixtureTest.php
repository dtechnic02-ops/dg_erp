<?php

namespace Tests\Feature\Company;

use App\Models\InventoryValuation;
use App\Models\SalesCostSnapshot;
use App\Models\StockMovement;
use Tests\Concerns\CreatesCompanyRouteTestFoundation;
use Tests\TestCase;

class CompanyBusinessFixtureTest extends TestCase
{
    use CreatesCompanyRouteTestFoundation;

    public function test_core_company_business_fixtures_are_valid(): void
    {
        $this->createCompanyRouteTestSchema();
        $role = $this->createCompanyDashboardRole();
        $company = $this->createActiveCompany();
        $user = $this->createCompanyAdmin($company, $role);
        $this->createOperationalCompanySubscription($company, $this->createActiveSubscriptionPlan());
        $this->authenticateCompanyAdmin($user);
        $year = $this->createActiveFinancialYear($company, $user);
        $warehouse = $this->createActiveWarehouse($company);
        $customer = $this->createCustomer($company, $user);
        $product = $this->createProduct($company);

        $this->assertSame($company->id, $year->company_id);
        $this->assertTrue((bool) $year->is_active);
        $this->assertTrue('2026-06-01' >= $year->start_date && '2026-06-01' <= $year->end_date);
        $this->assertSame($company->id, $warehouse->company_id);
        $this->assertSame('active', $warehouse->status);
        $this->assertSame($company->id, $customer->company_id);
        $this->assertNotSame('', $customer->name);
        $this->assertSame('active', $customer->status);
        $this->assertSame($company->id, $product->company_id);
        $this->assertSame('active', $product->status);
        $this->assertSame('99.00000000', number_format((float) $product->cost_price, 8, '.', ''));
        $this->assertSame(0, StockMovement::count());
        $this->assertSame(0, InventoryValuation::count());
        $this->assertSame(0, SalesCostSnapshot::count());
        $this->assertTrue(auth()->check());
    }
}
