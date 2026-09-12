<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\FinancialYear;
use App\Models\Product;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CompanyDashboardLowStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_low_stock_row_displays_product_name_and_current_stock(): void
    {
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckSubscription::class,
            \App\Http\Middleware\UpdateLastSeen::class,
        ]);
        DB::table('roles')->insertOrIgnore([
            'id' => Role::COMPANY_ADMIN_ID,
            'name' => 'company_admin',
        ]);
        $company = Company::create([
            'company_name' => 'Dashboard Company',
            'mobile' => '9800000000',
            'email' => 'dashboard@example.test',
            'status' => 'active',
        ]);
        $admin = User::create([
            'name' => 'Dashboard Admin',
            'email' => 'dashboard-admin@example.test',
            'password' => 'password',
            'role_id' => Role::COMPANY_ADMIN_ID,
            'company_id' => $company->id,
            'account_status' => 'active',
        ]);
        FinancialYear::create([
            'company_id' => $company->id,
            'name' => 'FY 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
            'created_by' => $admin->id,
        ]);
        $unit = Unit::create([
            'company_id' => $company->id,
            'name' => 'Pieces',
            'short_name' => 'pcs',
        ]);
        $product = Product::create([
            'company_id' => $company->id,
            'unit_id' => $unit->id,
            'name' => 'Low Stock Widget Product',
            'current_stock' => 3,
            'stock_alert' => 5,
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get(route('company.dashboard'));

        $response->assertOk()
            ->assertSee($product->name)
            ->assertSee('3.00');
        $response->assertViewHas('lowStock', fn ($items): bool =>
            $items->contains(fn (Product $item): bool =>
                $item->is($product) && (float) $item->current_stock === 3.0
            )
        );
    }
}
