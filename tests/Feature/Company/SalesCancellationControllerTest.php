<?php

namespace Tests\Feature\Company;

use App\Models\AccountingEntry;
use App\Models\CustomerTransaction;
use App\Models\InventoryValuation;
use App\Models\SalesCostSnapshot;
use App\Models\SalesInvoice;
use App\Models\StockMovement;
use Carbon\Carbon;
use Tests\Concerns\CreatesCompanyRouteTestFoundation;
use Tests\TestCase;

class SalesCancellationControllerTest extends TestCase
{
    use CreatesCompanyRouteTestFoundation;

    public function test_authenticated_company_admin_can_cancel_posted_product_sale_with_exact_reversals(): void
    {
        $this->createCompanyRouteTestSchema();
        $role = $this->createCompanyDashboardRole();
        $company = $this->createActiveCompany();
        $user = $this->createCompanyAdmin($company, $role);
        $plan = $this->createActiveSubscriptionPlan();
        $this->createOperationalCompanySubscription($company, $plan);
        $this->authenticateCompanyAdmin($user);
        $oldLastSeen = $user->last_seen->copy();

        $fixture = $this->createPostedProductSale(
            $company,
            $user,
            $this->createActiveFinancialYear($company, $user),
            $this->createActiveWarehouse($company),
            $this->createCustomer($company, $user),
            $this->createProduct($company)
        );

        $countsBefore = [
            'customer_transactions' => CustomerTransaction::count(),
            'stock_movements' => StockMovement::count(),
            'inventory_valuations' => InventoryValuation::count(),
            'sales_cost_snapshots' => SalesCostSnapshot::count(),
            'accounting_entries' => AccountingEntry::count(),
            'accounting_entry_lines' => $fixture['revenueEntry']->lines()->count() + $fixture['cogsEntry']->lines()->count(),
        ];

        $response = $this->post(route('company.sales.cancel', $fixture['salesInvoice']->id), [
            'cancel_date' => '2026-06-20',
            'cancel_reason' => 'Controller integration exact cancellation test',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Sales cancelled successfully.');
        $this->assertSame(302, $response->getStatusCode());

        $sale = SalesInvoice::findOrFail($fixture['salesInvoice']->id);
        $originalTransaction = CustomerTransaction::findOrFail($fixture['customerTransaction']->id);
        $originalMovement = StockMovement::findOrFail($fixture['stockMovement']->id);
        $originalValuation = InventoryValuation::findOrFail($fixture['inventoryValuation']->id);
        $snapshot = SalesCostSnapshot::findOrFail($fixture['salesCostSnapshot']->id);
        $originalRevenue = AccountingEntry::with('lines')->findOrFail($fixture['revenueEntry']->id);
        $originalCogs = AccountingEntry::with('lines')->findOrFail($fixture['cogsEntry']->id);
        $accounts = $fixture['accounts'];

        $customerReversal = CustomerTransaction::query()
            ->where('company_id', $company->id)
            ->where('reference_type', 'sales_invoice_cancel')
            ->where('reference_id', $sale->id)
            ->sole();
        $restorationMovement = StockMovement::query()->where('type', 'sales_restore')->sole();
        $restorationValuation = InventoryValuation::query()->where('movement_type', 'sales_restore')->sole();
        $revenueReversal = AccountingEntry::query()->with('lines')
            ->where('company_id', $company->id)
            ->where('source_key', 'sales:' . $sale->id . ':cancelled')
            ->sole();
        $cogsReversal = AccountingEntry::query()->with('lines')
            ->where('company_id', $company->id)
            ->where('source_key', 'sales-cogs:' . $sale->id . ':cancelled')
            ->sole();

        $this->assertSame($company->id, $sale->company_id);
        $this->assertSame(0, $sale->status);
        $this->assertStringContainsString('Controller integration exact cancellation test', $sale->note);

        $this->assertSame('sales_invoice', $originalTransaction->reference_type);
        $this->assertSame($sale->id, $originalTransaction->reference_id);
        $this->assertSame($company->id, $customerReversal->company_id);
        $this->assertSame($fixture['customer']->id, $customerReversal->customer_id);
        $this->assertSame($sale->id, $customerReversal->reference_id);
        $this->assertSame('sales_invoice_cancel', $customerReversal->reference_type);
        $this->assertSame('0.0000', number_format((float) $customerReversal->debit, 4, '.', ''));
        $this->assertSame('500.0000', number_format((float) $customerReversal->credit, 4, '.', ''));
        $this->assertSame('0.0000', number_format((float) $originalTransaction->debit - (float) $customerReversal->credit, 4, '.', ''));

        $this->assertSame($company->id, $originalMovement->company_id);
        $this->assertSame('-2.000000', number_format((float) $originalMovement->quantity, 6, '.', ''));
        $this->assertSame($company->id, $restorationMovement->company_id);
        $this->assertSame($fixture['product']->id, $restorationMovement->product_id);
        $this->assertSame($sale->invoice_no, $restorationMovement->reference_no);
        $this->assertSame('sales_restore', $restorationMovement->type);
        $this->assertSame('2.000000', number_format((float) $restorationMovement->quantity, 6, '.', ''));
        $this->assertSame('0.000000', number_format((float) $originalMovement->quantity + (float) $restorationMovement->quantity, 6, '.', ''));
        $this->assertSame('10.000000', number_format((float) $fixture['product']->fresh()->current_stock, 6, '.', ''));

        $this->assertSame($fixture['stockMovement']->id, $originalValuation->stock_movement_id);
        $this->assertSame($restorationMovement->id, $restorationValuation->stock_movement_id);
        $this->assertSame($fixture['product']->id, $restorationValuation->product_id);
        $this->assertSame('75.00000000', $restorationValuation->movement_unit_cost);
        $this->assertSame('150.0000', $restorationValuation->inventory_value_change);
        $this->assertSame('0.0000', number_format((float) $originalValuation->inventory_value_change + (float) $restorationValuation->inventory_value_change, 4, '.', ''));
        $this->assertSame($originalValuation->id, $restorationValuation->reversal_of_id);

        $this->assertSame('75.00000000', $snapshot->movement_unit_cost);
        $this->assertSame('150.0000', $snapshot->movement_value);
        $productCost = number_format((float) $fixture['product']->fresh()->cost_price, 8, '.', '');
        $this->assertSame('99.00000000', $productCost);
        $this->assertNotSame($productCost, $snapshot->movement_unit_cost);
        $this->assertSame(1, SalesCostSnapshot::count());

        $revenueLines = $revenueReversal->lines->keyBy('chart_account_id');
        $this->assertSame($company->id, $revenueReversal->company_id);
        $this->assertSame($sale->id, $revenueReversal->source_id);
        $this->assertSame('sales', $revenueReversal->source_type);
        $this->assertSame('cancelled', $revenueReversal->source_event);
        $this->assertSame($originalRevenue->id, $revenueReversal->reversal_of_id);
        $this->assertSame('500.0000', $revenueLines[$accounts['revenueAccount']->id]->debit);
        $this->assertSame('500.0000', $revenueLines[$accounts['receivableAccount']->id]->credit);
        $this->assertBalancedEntry($revenueReversal, '500.0000');
        $this->assertSame('reversed', $originalRevenue->status);

        $cogsLines = $cogsReversal->lines->keyBy('chart_account_id');
        $this->assertSame($company->id, $cogsReversal->company_id);
        $this->assertSame($sale->id, $cogsReversal->source_id);
        $this->assertSame('sales_cogs', $cogsReversal->source_type);
        $this->assertSame('cancelled', $cogsReversal->source_event);
        $this->assertSame($originalCogs->id, $cogsReversal->reversal_of_id);
        $this->assertSame('150.0000', $cogsLines[$accounts['inventoryAccount']->id]->debit);
        $this->assertSame('150.0000', $cogsLines[$accounts['cogsAccount']->id]->credit);
        $this->assertBalancedEntry($cogsReversal, '150.0000');
        $this->assertSame('reversed', $originalCogs->status);

        $this->assertSame($countsBefore['customer_transactions'] + 1, CustomerTransaction::count());
        $this->assertSame($countsBefore['stock_movements'] + 1, StockMovement::count());
        $this->assertSame($countsBefore['inventory_valuations'] + 1, InventoryValuation::count());
        $this->assertSame($countsBefore['sales_cost_snapshots'], SalesCostSnapshot::count());
        $this->assertSame($countsBefore['accounting_entries'] + 2, AccountingEntry::count());
        $this->assertSame($countsBefore['accounting_entry_lines'] + 4, AccountingEntry::query()->withCount('lines')->get()->sum('lines_count'));
        $this->assertSame(1, CustomerTransaction::query()->where('reference_type', 'sales_invoice_cancel')->count());
        $this->assertSame(1, StockMovement::query()->where('type', 'sales_restore')->count());
        $this->assertSame(1, InventoryValuation::query()->where('movement_type', 'sales_restore')->count());
        $this->assertSame(1, AccountingEntry::query()->where('source_key', 'sales:' . $sale->id . ':cancelled')->count());
        $this->assertSame(1, AccountingEntry::query()->where('source_key', 'sales-cogs:' . $sale->id . ':cancelled')->count());

        $this->assertSame($user->id, auth()->id());
        $this->assertGreaterThan($oldLastSeen->getTimestamp(), Carbon::parse($user->fresh()->last_seen)->getTimestamp());
        $this->assertSame($company->id, auth()->user()->company_id);
        $this->assertTrue(app(\App\Services\SubscriptionService::class)->isSubscriptionOperational($company->fresh()));
    }

    private function assertBalancedEntry(AccountingEntry $entry, string $amount): void
    {
        $this->assertSame($amount, number_format((float) $entry->lines->sum('debit'), 4, '.', ''));
        $this->assertSame($amount, number_format((float) $entry->lines->sum('credit'), 4, '.', ''));
    }
}
