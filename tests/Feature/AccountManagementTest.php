<?php

namespace Tests\Feature;

use App\Http\Controllers\Company\AccountController;
use App\Http\Middleware\CheckSubscription;
use App\Http\Middleware\EnsureCompanyUser;
use App\Http\Middleware\UpdateLastSeen;
use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AccountManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'accounting_entry_lines', 'opening_balance_lines', 'journal_items', 'account_transactions',
            'accounts', 'permissions', 'users', 'roles', 'companies', 'company_subscriptions', 'financial_years',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('companies', fn (Blueprint $t) => [$t->id(), $t->string('company_name'), $t->string('status')->default('active'), $t->timestamps()]);
        Schema::create('roles', fn (Blueprint $t) => [$t->id(), $t->string('name'), $t->timestamps()]);
        Schema::create('users', fn (Blueprint $t) => [$t->id(), $t->string('name'), $t->string('email'), $t->string('password'), $t->unsignedBigInteger('company_id'), $t->unsignedBigInteger('role_id'), $t->string('account_status')->default('active'), $t->rememberToken(), $t->timestamps()]);
        Schema::create('permissions', fn (Blueprint $t) => [$t->id(), $t->string('name'), $t->string('scope')->default('company'), $t->timestamps()]);
        Schema::create('company_subscriptions', fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('company_id'), $t->string('status'), $t->boolean('is_all_modules_enabled')->default(true), $t->timestamps()]);
        Schema::create('financial_years', fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('company_id'), $t->string('name'), $t->date('start_date'), $t->date('end_date'), $t->boolean('is_active'), $t->timestamps()]);
        Schema::create('accounts', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('company_id');
            $t->string('account_group')->nullable();
            $t->string('account_type');
            $t->string('sub_ledger_type')->nullable();
            $t->string('bank_name')->default('');
            $t->string('account_name');
            $t->string('branch')->nullable();
            $t->string('account_no')->nullable();
            $t->string('iban')->nullable();
            $t->string('swift_code')->nullable();
            $t->string('currency')->default('AED');
            $t->decimal('opening_balance', 15, 2)->default(0);
            $t->decimal('current_balance', 15, 2)->default(0);
            $t->string('image_path')->nullable();
            $t->text('note')->nullable();
            $t->string('status')->default('active');
            $t->timestamps();
        });
        Schema::create('account_transactions', fn (Blueprint $t) => [
            $t->id(), $t->unsignedBigInteger('company_id'), $t->unsignedBigInteger('financial_year_id')->nullable(),
            $t->unsignedBigInteger('account_id'), $t->date('transaction_date'), $t->string('voucher_no'),
            $t->string('reference_type'), $t->unsignedBigInteger('reference_id'), $t->decimal('debit', 20, 4)->default(0),
            $t->decimal('credit', 20, 4)->default(0), $t->decimal('balance', 20, 4)->default(0), $t->integer('status')->default(1), $t->timestamps(),
        ]);
        Schema::create('journal_items', fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('account_id')->nullable(), $t->timestamps()]);
        Schema::create('opening_balance_lines', fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('operational_account_id')->nullable(), $t->timestamps()]);
        Schema::create('accounting_entry_lines', fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('operational_account_id')->nullable(), $t->timestamps()]);

        DB::table('companies')->insert([
            ['id' => 1, 'company_name' => 'One'],
            ['id' => 2, 'company_name' => 'Two'],
        ]);
        DB::table('roles')->insert([['id' => 2, 'name' => 'Admin']]);
        DB::table('users')->insert([
            'id' => 1, 'name' => 'Admin', 'email' => 'admin@test', 'password' => Hash::make('x'),
            'company_id' => 1, 'role_id' => 2,
        ]);
        DB::table('company_subscriptions')->insert(['company_id' => 1, 'status' => 'active']);
        DB::table('financial_years')->insert(['id' => 1, 'company_id' => 1, 'name' => 'FY26', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => 1]);

        foreach (['module_accounts', 'view_accounts', 'create_accounts', 'edit_accounts', 'delete_accounts', 'print_accounts'] as $permission) {
            Permission::create(['name' => $permission, 'scope' => Permission::SCOPE_COMPANY]);
        }

        $this->withoutMiddleware([EnsureCompanyUser::class, CheckSubscription::class, UpdateLastSeen::class, VerifyCsrfToken::class]);
    }

    public function test_cash_create_stores_zero_balances_and_empty_bank_fields(): void
    {
        $this->actingAs(User::find(1))->post(route('company.accounts.store'), [
            '_account_form' => 'create',
            'account_group' => 'Asset',
            'account_type' => 'Cash',
            'account_name' => 'Petty Cash Drawer',
            'opening_balance' => '999',
            'current_balance' => '888',
        ])->assertRedirect();

        $account = Account::where('account_name', 'Petty Cash Drawer')->first();
        $this->assertSame('Cash', $account->account_type);
        $this->assertSame('', $account->bank_name);
        $this->assertSame('', $account->branch);
        $this->assertSame('0.00', number_format((float) $account->opening_balance, 2, '.', ''));
        $this->assertSame('0.00', number_format((float) $account->current_balance, 2, '.', ''));
    }

    public function test_bank_create_and_edit_preserve_bank_details(): void
    {
        $this->actingAs(User::find(1))->post(route('company.accounts.store'), [
            '_account_form' => 'create',
            'account_group' => 'Asset',
            'account_type' => 'Bank',
            'account_name' => 'Main Bank',
            'bank_name' => 'Global IME Bank',
            'branch' => 'Kathmandu',
            'account_no' => '12345',
        ])->assertRedirect();

        $account = Account::where('account_name', 'Main Bank')->firstOrFail();

        $this->actingAs(User::find(1))->post(route('company.accounts.update', $account->id), [
            'account_group' => 'Asset',
            'account_type' => 'Bank',
            'account_name' => 'Main Bank Updated',
            'bank_name' => 'Global IME Bank',
            'branch' => 'Pokhara',
            'account_no' => '12345',
            'current_balance' => '5000',
            'opening_balance' => '4000',
        ])->assertRedirect();

        $account->refresh();
        $this->assertSame('Main Bank Updated', $account->account_name);
        $this->assertSame('Pokhara', $account->branch);
        $this->assertSame('0.00', number_format((float) $account->current_balance, 2, '.', ''));
    }

    public function test_duplicate_account_name_is_rejected_per_company(): void
    {
        Account::create([
            'company_id' => 1,
            'account_group' => 'Asset',
            'account_type' => 'Cash',
            'account_name' => 'Duplicate Me',
            'bank_name' => '',
            'opening_balance' => 0,
            'current_balance' => 0,
            'status' => 'active',
        ]);

        $this->actingAs(User::find(1))->post(route('company.accounts.store'), [
            '_account_form' => 'create',
            'account_group' => 'Asset',
            'account_type' => 'Cash',
            'account_name' => 'Duplicate Me',
        ])->assertSessionHasErrors('account_name');
    }

    public function test_cross_company_show_update_and_delete_are_blocked(): void
    {
        $foreign = Account::create([
            'company_id' => 2,
            'account_group' => 'Asset',
            'account_type' => 'Bank',
            'account_name' => 'Foreign Bank',
            'bank_name' => 'Foreign',
            'opening_balance' => 0,
            'current_balance' => 0,
            'status' => 'active',
        ]);

        $this->actingAs(User::find(1))->get(route('company.accounts.show', $foreign->id))->assertNotFound();
        $this->actingAs(User::find(1))->post(route('company.accounts.update', $foreign->id), [
            'account_group' => 'Asset',
            'account_type' => 'Bank',
            'account_name' => 'Hacked',
            'bank_name' => 'Hacked',
        ])->assertNotFound();
        $this->actingAs(User::find(1))->post(route('company.accounts.delete', $foreign->id))->assertNotFound();
    }

    public function test_delete_archives_account_with_financial_history(): void
    {
        $account = Account::create([
            'company_id' => 1,
            'account_group' => 'Asset',
            'account_type' => 'Cash',
            'account_name' => 'Used Cash',
            'bank_name' => '',
            'opening_balance' => 0,
            'current_balance' => 100,
            'status' => 'active',
        ]);

        AccountTransaction::create([
            'company_id' => 1,
            'financial_year_id' => 1,
            'account_id' => $account->id,
            'transaction_date' => '2026-06-01',
            'voucher_no' => 'TX-1',
            'reference_type' => 'test',
            'reference_id' => 1,
            'debit' => 100,
            'credit' => 0,
            'balance' => 100,
            'status' => 1,
        ]);

        $this->actingAs(User::find(1))->post(route('company.accounts.delete', $account->id))->assertRedirect();

        $account->refresh();
        $this->assertSame('inactive', $account->status);
        $this->assertSame(1, Account::whereKey($account->id)->count());
    }

    public function test_normalized_bank_fields_helper_for_cash_and_bank(): void
    {
        $controller = new AccountController();
        $method = new \ReflectionMethod($controller, 'normalizedBankFields');
        $method->setAccessible(true);

        $cashRequest = Request::create('/', 'POST', [
            'bank_name' => null,
            'branch' => null,
            'account_no' => null,
            'iban' => null,
            'swift_code' => null,
        ]);

        $this->assertSame([
            'bank_name' => '',
            'branch' => '',
            'account_no' => '',
            'iban' => '',
            'swift_code' => '',
        ], $method->invoke($controller, $cashRequest, 'Cash'));

        $bankRequest = Request::create('/', 'POST', [
            'bank_name' => 'Test Bank',
            'branch' => 'Main',
            'account_no' => '001',
            'iban' => 'AE1',
            'swift_code' => 'SWFT',
        ]);

        $this->assertSame([
            'bank_name' => 'Test Bank',
            'branch' => 'Main',
            'account_no' => '001',
            'iban' => 'AE1',
            'swift_code' => 'SWFT',
        ], $method->invoke($controller, $bankRequest, 'Bank'));
    }

    public function test_invalid_account_group_and_type_are_rejected(): void
    {
        $this->actingAs(User::find(1))->post(route('company.accounts.store'), [
            '_account_form' => 'create',
            'account_group' => 'InvalidGroup',
            'account_type' => 'InvalidType',
            'account_name' => 'Bad Account',
        ])->assertSessionHasErrors(['account_group', 'account_type']);
    }
}
