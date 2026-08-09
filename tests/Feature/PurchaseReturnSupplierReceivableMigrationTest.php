<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\QueryException;
use RuntimeException;
use Tests\TestCase;

class PurchaseReturnSupplierReceivableMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('accounting_entry_lines');
        Schema::dropIfExists('purchase_return_refunds');
        Schema::dropIfExists('purchase_returns');
        Schema::dropIfExists('chart_accounts');
        Schema::dropIfExists('companies');

        Schema::create('companies', function (Blueprint $table): void {
            $table->id();
        });
        Schema::create('chart_accounts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('code');
            $table->string('name');
            $table->string('account_class');
            $table->string('account_category')->nullable();
            $table->string('normal_balance');
            $table->string('system_code')->nullable();
            $table->unsignedInteger('level')->default(1);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_system')->default(false);
            $table->boolean('is_control')->default(false);
            $table->boolean('allow_manual_entry')->default(true);
            $table->string('status')->default('active');
            $table->timestamps();
        });
        Schema::create('purchase_returns', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('return_no');
        });
        Schema::create('purchase_return_refunds', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('account_id')->nullable();
        });
        Schema::create('accounting_entry_lines', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('chart_account_id');
            $table->decimal('debit', 20, 4)->default(0);
        });
    }

    public function test_it_provisions_exactly_one_valid_account_per_company_without_touching_history(): void
    {
        DB::table('companies')->insert([['id' => 1], ['id' => 2]]);
        $parentOne = $this->insertAccount(1, '1100', 'CURRENT_ASSETS');
        $parentTwo = $this->insertAccount(2, '1100', 'CURRENT_ASSETS');
        $historical = $this->insertAccount(1, '1199', 'HISTORICAL_TEST');
        DB::table('accounting_entry_lines')->insert(['id' => 77, 'chart_account_id' => $historical, 'debit' => 42.25]);

        $this->migration()->up();

        foreach ([[1, $parentOne], [2, $parentTwo]] as [$companyId, $parentId]) {
            $account = DB::table('chart_accounts')->where('company_id', $companyId)
                ->where('system_code', 'SUPPLIER_RETURN_RECEIVABLE')->sole();
            $this->assertSame('1175', (string) $account->code);
            $this->assertSame('asset', $account->account_class);
            $this->assertSame('debit', $account->normal_balance);
            $this->assertSame('active', $account->status);
            $this->assertSame($parentId, (int) $account->parent_id);
        }

        $this->assertTrue(Schema::hasColumn('purchase_returns', 'request_key'));
        $this->assertTrue(Schema::hasColumn('purchase_return_refunds', 'idempotency_key'));
        $this->assertSame(1, DB::table('accounting_entry_lines')->count());
        $this->assertSame(42.25, (float) DB::table('accounting_entry_lines')->where('id', 77)->value('debit'));
    }

    public function test_invalid_existing_account_fails_before_schema_changes(): void
    {
        DB::table('companies')->insert(['id' => 1]);
        $parent = $this->insertAccount(1, '1100', 'CURRENT_ASSETS');
        $this->insertAccount(1, '1175', 'SUPPLIER_RETURN_RECEIVABLE', $parent, 'inactive');

        try {
            $this->migration()->up();
            $this->fail('Inactive clearing account was accepted.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('not an active Asset/Debit', $exception->getMessage());
        }

        $this->assertFalse(Schema::hasColumn('purchase_returns', 'request_key'));
        $this->assertFalse(Schema::hasColumn('purchase_return_refunds', 'idempotency_key'));
    }

    public function test_duplicate_account_and_code_conflict_fail_safely(): void
    {
        DB::table('companies')->insert(['id' => 1]);
        $parent = $this->insertAccount(1, '1100', 'CURRENT_ASSETS');
        $this->insertAccount(1, '1175', 'SUPPLIER_RETURN_RECEIVABLE', $parent);
        $this->insertAccount(1, '1175', 'SUPPLIER_RETURN_RECEIVABLE', $parent);

        try {
            $this->migration()->up();
            $this->fail('Duplicate clearing accounts were accepted.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('duplicated', $exception->getMessage());
        }

        DB::table('chart_accounts')->where('system_code', 'SUPPLIER_RETURN_RECEIVABLE')->delete();
        $this->insertAccount(1, '1175', 'UNRELATED_ACCOUNT', $parent);

        try {
            $this->migration()->up();
            $this->fail('Conflicting account code was accepted.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('already assigned', $exception->getMessage());
        }

        $this->assertFalse(Schema::hasColumn('purchase_returns', 'request_key'));
    }

    public function test_request_keys_are_company_scoped_and_database_unique(): void
    {
        DB::table('companies')->insert([['id' => 1], ['id' => 2]]);
        $this->insertAccount(1, '1100', 'CURRENT_ASSETS');
        $this->insertAccount(2, '1100', 'CURRENT_ASSETS');
        $this->migration()->up();

        $key = '11111111-1111-4111-8111-111111111111';
        DB::table('purchase_returns')->insert(['company_id' => 1, 'return_no' => 'PR-1', 'request_key' => $key]);
        DB::table('purchase_returns')->insert(['company_id' => 2, 'return_no' => 'PR-2', 'request_key' => $key]);
        DB::table('purchase_return_refunds')->insert(['company_id' => 1, 'account_id' => null, 'idempotency_key' => $key]);
        DB::table('purchase_return_refunds')->insert(['company_id' => 2, 'account_id' => null, 'idempotency_key' => $key]);

        $this->assertSame(2, DB::table('purchase_returns')->where('request_key', $key)->count());
        $this->assertSame(2, DB::table('purchase_return_refunds')->where('idempotency_key', $key)->count());

        try {
            DB::table('purchase_returns')->insert(['company_id' => 1, 'return_no' => 'PR-3', 'request_key' => $key]);
            $this->fail('Duplicate Purchase Return key was accepted.');
        } catch (QueryException) {
            $this->assertTrue(true);
        }

        try {
            DB::table('purchase_return_refunds')->insert(['company_id' => 1, 'account_id' => null, 'idempotency_key' => $key]);
            $this->fail('Duplicate Purchase Return Refund key was accepted.');
        } catch (QueryException) {
            $this->assertTrue(true);
        }
    }

    private function insertAccount(
        int $companyId,
        string $code,
        string $systemCode,
        ?int $parentId = null,
        string $status = 'active'
    ): int {
        return (int) DB::table('chart_accounts')->insertGetId([
            'company_id' => $companyId,
            'parent_id' => $parentId,
            'code' => $code,
            'name' => $systemCode,
            'account_class' => 'asset',
            'account_category' => 'test',
            'normal_balance' => 'debit',
            'system_code' => $systemCode,
            'level' => $parentId ? 3 : 2,
            'sort_order' => 1,
            'is_system' => true,
            'is_control' => true,
            'allow_manual_entry' => false,
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function migration(): object
    {
        return require database_path('migrations/2026_08_09_000000_provision_supplier_return_receivable_account.php');
    }
}
