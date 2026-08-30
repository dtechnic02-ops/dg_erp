<?php

namespace Tests\Feature;

use App\Http\Controllers\Company\SalesController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SalesValidationPreservationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['accounts', 'customers', 'users'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::create('users', function (Blueprint $table): void {
            $table->id(); $table->string('name'); $table->string('email'); $table->string('password');
            $table->unsignedBigInteger('company_id'); $table->timestamp('last_seen')->nullable(); $table->rememberToken(); $table->timestamps();
        });
        Schema::create('customers', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('company_id'); $table->string('name'); $table->timestamps();
        });
        Schema::create('accounts', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('company_id'); $table->string('account_name'); $table->timestamps();
        });

        DB::table('users')->insert(['id' => 1, 'name' => 'Sales Admin', 'email' => 'sales@test', 'password' => Hash::make('x'), 'company_id' => 1, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('customers')->insert(['id' => 1, 'company_id' => 1, 'name' => 'Customer', 'created_at' => now(), 'updated_at' => now()]);
        Route::post('/__test/sales-validation', [SalesController::class, 'store'])->name('test.sales.validation');
    }

    public function test_fifty_sales_rows_survive_a_late_row_validation_failure(): void
    {
        $rows = 50;
        $types = array_map(fn ($index) => $index % 2 === 0 ? 'product' : 'service', range(0, $rows - 1));
        $payload = [
            'customer_id' => 1, 'sale_date' => '2026-06-15', 'price_type' => 'retail', 'barcode' => 'SCAN-50',
            'item_type' => $types,
            'product_id' => array_map(fn ($type) => $type === 'product' ? '101' : '', $types),
            'service_id' => array_map(fn ($type) => $type === 'service' ? '202' : '', $types),
            'quantity' => array_fill(0, $rows, '2'),
            'unit_price' => array_fill(0, $rows, '10.50'),
            'vat_rate' => array_fill(0, $rows, '13'),
            'vat_amount' => array_fill(0, $rows, '2.73'),
            'total_price' => array_fill(0, $rows, '23.73'),
            'paid_amount' => '0', 'discount_amount' => '5.00', 'subtotal' => '1050.00',
            'total_vat' => '136.50', 'grand_total' => '1181.50', 'note' => 'Keep all sales rows.',
        ];
        $payload['unit_price'][49] = '';

        $response = $this->actingAs(User::findOrFail(1))
            ->from('/sales/create')
            ->post('/__test/sales-validation', $payload);

        $response->assertRedirect('/sales/create')
            ->assertSessionHasErrors(['unit_price.49'])
            ->assertSessionHasInput('item_type', $payload['item_type'])
            ->assertSessionHasInput('product_id', $payload['product_id'])
            ->assertSessionHasInput('service_id', $payload['service_id'])
            ->assertSessionHasInput('quantity', $payload['quantity'])
            ->assertSessionHasInput('unit_price', $payload['unit_price'])
            ->assertSessionHasInput('vat_rate', $payload['vat_rate'])
            ->assertSessionHasInput('vat_amount', $payload['vat_amount'])
            ->assertSessionHasInput('total_price', $payload['total_price'])
            ->assertSessionHasInput('customer_id', 1)
            ->assertSessionHasInput('barcode', 'SCAN-50')
            ->assertSessionHasInput('note', 'Keep all sales rows.');

        $this->assertSame('Row 50: Unit Price is required.', session('errors')->get('unit_price.49')[0]);
        $this->assertCount(50, session()->getOldInput('item_type'));
        $this->assertSame('101', session()->getOldInput('product_id')[0]);
        $this->assertSame('202', session()->getOldInput('service_id')[49]);
        $this->assertNull(session()->getOldInput('unit_price')[49]);
    }
}
