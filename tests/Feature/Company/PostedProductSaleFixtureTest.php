<?php

namespace Tests\Feature\Company;

use App\Models\AccountingEntry;
use App\Models\CustomerTransaction;
use App\Models\InventoryValuation;
use App\Models\StockMovement;
use Tests\Concerns\CreatesCompanyRouteTestFoundation;
use Tests\TestCase;

class PostedProductSaleFixtureTest extends TestCase
{
    use CreatesCompanyRouteTestFoundation;

    public function test_posted_product_sale_fixture_is_complete_and_balanced(): void
    {
        $this->createCompanyRouteTestSchema();
        $role = $this->createCompanyDashboardRole();
        $company = $this->createActiveCompany();
        $user = $this->createCompanyAdmin($company, $role);
        $this->createOperationalCompanySubscription($company, $this->createActiveSubscriptionPlan());
        $this->authenticateCompanyAdmin($user);

        $fixture = $this->createPostedProductSale(
            $company,
            $user,
            $this->createActiveFinancialYear($company, $user),
            $this->createActiveWarehouse($company),
            $this->createCustomer($company, $user),
            $this->createProduct($company)
        );

        $sale = $fixture['salesInvoice'];
        $item = $fixture['salesItem'];
        $customerTransaction = $fixture['customerTransaction'];
        $movement = $fixture['stockMovement'];
        $valuation = $fixture['inventoryValuation'];
        $snapshot = $fixture['salesCostSnapshot'];
        $revenueEntry = $fixture['revenueEntry']->load('lines');
        $cogsEntry = $fixture['cogsEntry']->load('lines');
        $accounts = $fixture['accounts'];

        $this->assertTrue($sale->exists);
        $this->assertSame($company->id, $sale->company_id);
        $this->assertSame($fixture['customer']->id, $sale->customer_id);
        $this->assertSame(1, $sale->status);
        $this->assertSame('500.00', $sale->subtotal);
        $this->assertSame('500.00', $sale->grand_total);

        $this->assertTrue($item->exists);
        $this->assertSame($sale->id, $item->sales_invoice_id);
        $this->assertSame($fixture['product']->id, $item->product_id);
        $this->assertSame('2.00', $item->quantity);
        $this->assertSame('250.00', $item->unit_price);
        $this->assertSame('500.00', $item->total_price);

        $this->assertTrue($customerTransaction->exists);
        $this->assertSame($company->id, $customerTransaction->company_id);
        $this->assertSame($fixture['customer']->id, $customerTransaction->customer_id);
        $this->assertSame('sales_invoice', $customerTransaction->reference_type);
        $this->assertSame($sale->id, $customerTransaction->reference_id);
        $this->assertSame('500.0000', number_format((float) $customerTransaction->debit - (float) $customerTransaction->credit, 4, '.', ''));

        $this->assertTrue($movement->exists);
        $this->assertSame($fixture['product']->id, $movement->product_id);
        $this->assertSame($sale->invoice_no, $movement->reference_no);
        $this->assertSame('sale', $movement->type);
        $this->assertSame('-2.000000', number_format((float) $movement->quantity, 6, '.', ''));

        $this->assertTrue($valuation->exists);
        $this->assertSame($movement->id, $valuation->stock_movement_id);
        $this->assertSame('75.00000000', $valuation->movement_unit_cost);
        $this->assertSame('-150.0000', $valuation->inventory_value_change);
        $this->assertSame('sale', $valuation->movement_type);

        $this->assertTrue($snapshot->exists);
        $this->assertSame($sale->id, $snapshot->sales_invoice_id);
        $this->assertSame($item->id, $snapshot->sales_item_id);
        $this->assertSame($fixture['product']->id, $snapshot->product_id);
        $this->assertSame($valuation->id, $snapshot->inventory_valuation_id);
        $this->assertSame('75.00000000', $snapshot->movement_unit_cost);
        $this->assertSame('150.0000', $snapshot->movement_value);

        $this->assertSame('99.00000000', $fixture['product']->cost_price);
        $this->assertNotSame($fixture['product']->cost_price, $snapshot->movement_unit_cost);

        $revenueLines = $revenueEntry->lines->keyBy('chart_account_id');
        $this->assertSame($sale->id, $revenueEntry->source_id);
        $this->assertSame('500.0000', $revenueLines[$accounts['receivableAccount']->id]->debit);
        $this->assertSame('500.0000', $revenueLines[$accounts['revenueAccount']->id]->credit);
        $this->assertSame('500.0000', number_format((float) $revenueEntry->lines->sum('debit'), 4, '.', ''));
        $this->assertSame('500.0000', number_format((float) $revenueEntry->lines->sum('credit'), 4, '.', ''));

        $cogsLines = $cogsEntry->lines->keyBy('chart_account_id');
        $this->assertSame($sale->id, $cogsEntry->source_id);
        $this->assertSame('150.0000', $cogsLines[$accounts['cogsAccount']->id]->debit);
        $this->assertSame('150.0000', $cogsLines[$accounts['inventoryAccount']->id]->credit);
        $this->assertSame('150.0000', number_format((float) $cogsEntry->lines->sum('debit'), 4, '.', ''));
        $this->assertSame('150.0000', number_format((float) $cogsEntry->lines->sum('credit'), 4, '.', ''));

        $this->assertSame(0, StockMovement::query()->where('type', 'sales_restore')->count());
        $this->assertSame(0, InventoryValuation::query()->where('movement_type', 'sales_restore')->count());
        $this->assertSame(0, CustomerTransaction::query()->where('reference_type', 'sales_invoice_cancel')->count());
        $this->assertSame(0, AccountingEntry::query()->where('source_event', 'cancelled')->count());
        $this->assertTrue(auth()->check());
    }
}
