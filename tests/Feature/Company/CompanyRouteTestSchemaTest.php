<?php

namespace Tests\Feature\Company;

use Illuminate\Support\Facades\Schema;
use Tests\Concerns\CreatesCompanyRouteTestFoundation;
use Tests\TestCase;

class CompanyRouteTestSchemaTest extends TestCase
{
    use CreatesCompanyRouteTestFoundation;

    public function test_company_route_schema_contains_the_real_sales_cancellation_dependencies(): void
    {
        $this->createCompanyRouteTestSchema();

        foreach (['users','roles','companies','subscription_plans','company_subscriptions','financial_years','warehouses','customers','products','accounts','chart_accounts','sales_invoices','sales_items','customer_transactions','stock_movements','inventory_valuations','sales_cost_snapshots','accounting_entries','accounting_entry_lines'] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing {$table}.");
        }

        $this->assertTrue(Schema::hasColumns('users', ['company_id','role_id','last_seen']));
        $this->assertTrue(Schema::hasColumns('companies', ['status']));
        $this->assertTrue(Schema::hasColumns('company_subscriptions', ['company_id','subscription_plan_id','start_date','expiry_date']));
        $this->assertTrue(Schema::hasColumns('sales_invoices', ['company_id','status']));
        $this->assertTrue(Schema::hasColumns('sales_cost_snapshots', ['inventory_valuation_id']));
        $this->assertTrue(Schema::hasColumns('inventory_valuations', ['stock_movement_id']));
    }
}
