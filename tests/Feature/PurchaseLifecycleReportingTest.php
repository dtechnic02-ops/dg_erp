<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\Role;
use App\Models\User;
use App\Services\PurchaseInvoicePaymentStateService;
use App\Services\PurchaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Support\Str;
use Tests\TestCase;

class PurchaseLifecycleReportingTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $user;
    private int $financialYearId;
    private int $supplierId;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        View::share('errors', new ViewErrorBag());

        DB::table('roles')->insertOrIgnore(['id' => Role::COMPANY_ADMIN_ID, 'name' => 'Company Admin']);
        foreach (['module_stock', 'view_stock', 'print_stock'] as $permission) {
            DB::table('permissions')->insertOrIgnore(['name' => $permission, 'scope' => 'company']);
        }
        $this->company = Company::create([
            'company_name' => 'Purchase Reporting Company',
            'mobile' => '9800000010',
            'email' => 'purchase-reporting@example.test',
            'status' => 'active',
        ]);
        $this->user = User::create([
            'name' => 'Reporting Admin',
            'email' => 'purchase-reporting-admin@example.test',
            'password' => 'password',
            'role_id' => Role::COMPANY_ADMIN_ID,
            'company_id' => $this->company->id,
            'account_status' => 'active',
        ]);
        $this->financialYearId = DB::table('financial_years')->insertGetId([
            'company_id' => $this->company->id,
            'name' => 'FY 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_active' => 1,
            'is_closed' => 0,
            'is_locked' => 0,
            'created_by' => $this->user->id,
        ]);
        $unitId = DB::table('units')->insertGetId([
            'company_id' => $this->company->id,
            'name' => 'Pieces',
            'short_name' => 'pcs',
        ]);
        $this->supplierId = DB::table('suppliers')->insertGetId([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'name' => 'Reporting Supplier',
            'opening_balance' => 0,
            'current_balance' => 0,
            'credit_days' => 0,
            'status' => 'active',
        ]);
        $this->product = Product::create([
            'company_id' => $this->company->id,
            'unit_id' => $unitId,
            'name' => 'Reporting Product',
            'current_stock' => 0,
            'stock_alert' => 0,
            'status' => 'active',
        ]);

        $this->actingAs($this->user);
    }

    public function test_dashboard_excludes_full_reverted_purchases_from_chart_and_supplier_due(): void
    {
        $this->invoice('PU-ACTIVE', 100, 60, 1);
        $this->invoice('PU-REVERTED', 500, 500, 0);

        $response = $this->get(route('company.dashboard'));

        $response->assertOk();
        $response->assertViewHas('purchaseChart', function ($rows): bool {
            return $rows->count() === 1 && number_format((float) $rows->first()->total, 2, '.', '') === '100.00';
        });
        $response->assertViewHas('data', fn (array $data): bool =>
            number_format((float) $data['supplier_due'], 2, '.', '') === '60.00'
        );
    }

    public function test_stock_ledger_groups_purchase_lifecycle_movements_by_signed_direction(): void
    {
        foreach ([
            ['purchase', 10, 'PURCHASE-IN'],
            ['purchase_cancel', -10, 'PURCHASE-REVERT-OUT'],
            ['purchase_return', -3, 'PURCHASE-RETURN-OUT'],
            ['purchase_return_cancel', 3, 'RETURN-REVERSE-IN'],
        ] as [$type, $quantity, $reference]) {
            DB::table('stock_movements')->insert([
                'company_id' => $this->company->id,
                'financial_year_id' => $this->financialYearId,
                'transaction_date' => '2026-06-15',
                'product_id' => $this->product->id,
                'type' => $type,
                'quantity' => $quantity,
                'before_stock' => 0,
                'after_stock' => $quantity,
                'reference_no' => $reference,
            ]);
        }

        $out = $this->get(route('company.stock-ledger.index', ['type' => 'out']));
        $out->assertOk()->assertSee('PURCHASE-REVERT-OUT')->assertSee('PURCHASE-RETURN-OUT')
            ->assertDontSee('PURCHASE-IN')->assertDontSee('RETURN-REVERSE-IN');
        $out->assertViewHas('movements', fn ($rows): bool =>
            collect($rows->items())->pluck('type')->sort()->values()->all() === ['purchase_cancel', 'purchase_return']
        );

        $in = $this->get(route('company.stock-ledger.index', ['type' => 'in']));
        $in->assertOk()->assertSee('PURCHASE-IN')->assertSee('RETURN-REVERSE-IN')
            ->assertDontSee('PURCHASE-REVERT-OUT')->assertDontSee('PURCHASE-RETURN-OUT');

        $returns = $this->get(route('company.stock-ledger.index', ['type' => 'return']));
        $returns->assertOk()->assertSee('PURCHASE-RETURN-OUT')->assertSee('RETURN-REVERSE-IN');
        $returns->assertSee('Purchase Full Revert')->assertSee('Reverse Purchase Return');
    }

    public function test_maintenance_recalculation_reuses_canonical_payment_state_with_return_adjustments(): void
    {
        $invoice = $this->invoice('PU-MAINTENANCE', 100, 100, 1);
        DB::table('purchase_items')->insert([
            'created_by' => $this->user->id,
            'status' => 1,
            'company_id' => $this->company->id,
            'financial_year_id' => $this->financialYearId,
            'purchase_invoice_id' => $invoice->id,
            'item_type' => 'product',
            'product_id' => $this->product->id,
            'quantity' => 1,
            'price' => 100,
            'unit_price' => 100,
            'total_price' => 100,
            'total' => 100,
            'vat_rate' => 0,
            'vat_amount' => 0,
        ]);
        DB::table('purchase_payments')->insert([
            'company_id' => $this->company->id,
            'financial_year_id' => $this->financialYearId,
            'purchase_invoice_id' => $invoice->id,
            'supplier_id' => $this->supplierId,
            'payment_no' => 'PP-MAINTENANCE',
            'payment_date' => '2026-06-15',
            'amount' => 20,
            'status' => 1,
            'created_by' => $this->user->id,
        ]);
        $returnId = DB::table('purchase_returns')->insertGetId([
            'company_id' => $this->company->id,
            'financial_year_id' => $this->financialYearId,
            'purchase_invoice_id' => $invoice->id,
            'supplier_id' => $this->supplierId,
            'return_no' => 'PR-MAINTENANCE',
            'request_key' => (string) Str::uuid(),
            'return_date' => '2026-06-15',
            'grand_total' => 30,
            'refund_amount' => 0,
            'adjust_amount' => 30,
            'created_by' => $this->user->id,
            'status' => 1,
        ]);
        $refundId = DB::table('purchase_return_refunds')->insertGetId([
            'company_id' => $this->company->id,
            'financial_year_id' => $this->financialYearId,
            'purchase_return_id' => $returnId,
            'supplier_id' => $this->supplierId,
            'idempotency_key' => (string) Str::uuid(),
            'refund_date' => '2026-06-15',
            'refund_amount' => 30,
            'adjust_amount' => 30,
            'cash_amount' => 0,
            'created_by' => $this->user->id,
            'status' => 1,
        ]);
        DB::table('purchase_return_refund_adjustments')->insert([
            'company_id' => $this->company->id,
            'purchase_return_refund_id' => $refundId,
            'purchase_invoice_id' => $invoice->id,
            'adjust_amount' => 30,
            'status' => 1,
            'created_by' => $this->user->id,
        ]);

        PurchaseInvoicePaymentStateService::syncInvoicePaymentState($invoice->fresh());
        $canonical = $invoice->fresh()->only(['paid_amount', 'due_amount', 'payment_status']);
        $invoice->update(['paid_amount' => 0, 'due_amount' => 100, 'payment_status' => 'unpaid']);

        PurchaseService::recalculateInvoice($invoice->id);

        $this->assertSame($canonical, $invoice->fresh()->only(['paid_amount', 'due_amount', 'payment_status']));
        $this->assertSame('50.00', number_format((float) $invoice->fresh()->paid_amount, 2, '.', ''));
        $this->assertSame('50.00', number_format((float) $invoice->fresh()->due_amount, 2, '.', ''));
        $this->assertSame('partial', $invoice->fresh()->payment_status);
    }

    private function invoice(string $number, float $total, float $due, int $status): PurchaseInvoice
    {
        return PurchaseInvoice::create([
            'created_by' => $this->user->id,
            'company_id' => $this->company->id,
            'financial_year_id' => $this->financialYearId,
            'supplier_id' => $this->supplierId,
            'invoice_no' => $number,
            'purchase_date' => '2026-06-15',
            'subtotal' => $total,
            'discount' => 0,
            'total_vat' => 0,
            'grand_total' => $total,
            'paid_amount' => $total - $due,
            'due_amount' => $due,
            'payment_status' => $due <= 0 ? 'paid' : (($total - $due) > 0 ? 'partial' : 'unpaid'),
            'status' => $status,
        ]);
    }
}
