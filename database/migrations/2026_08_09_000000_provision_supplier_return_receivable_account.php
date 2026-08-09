<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CODE = '1175';
    private const SYSTEM_CODE = 'SUPPLIER_RETURN_RECEIVABLE';

    public function up(): void
    {
        $this->preflight();

        Schema::table('purchase_returns', function (Blueprint $table): void {
            $table->uuid('request_key')->nullable()->after('return_no');
            $table->unique(['company_id', 'request_key'], 'purchase_returns_company_request_unique');
        });
        Schema::table('purchase_return_refunds', function (Blueprint $table): void {
            $table->uuid('idempotency_key')->nullable()->after('account_id');
            $table->unique(['company_id', 'idempotency_key'], 'purchase_return_refunds_company_idempotency_unique');
        });

        DB::transaction(function (): void {
            foreach (DB::table('companies')->select('id')->orderBy('id')->get() as $company) {
                $matches = DB::table('chart_accounts')
                    ->where('company_id', $company->id)
                    ->where('system_code', self::SYSTEM_CODE)
                    ->lockForUpdate()
                    ->get();

                if ($matches->count() > 1) {
                    throw new RuntimeException("Required Chart Account system code [" . self::SYSTEM_CODE . "] is duplicated for company {$company->id}.");
                }

                if ($matches->count() === 1) {
                    $account = $matches->first();
                    if ((string) $account->code !== self::CODE || $account->status !== 'active'
                        || $account->account_class !== 'asset' || $account->normal_balance !== 'debit') {
                        throw new RuntimeException("Required Chart Account [" . self::SYSTEM_CODE . "] is not an active Asset/Debit account with code [" . self::CODE . "] for company {$company->id}.");
                    }
                    $this->assertCurrentAssetsParent((int) $company->id, (int) $account->parent_id);
                    continue;
                }

                $parent = DB::table('chart_accounts')
                    ->where('company_id', $company->id)
                    ->where('system_code', 'CURRENT_ASSETS')
                    ->where('status', 'active')
                    ->lockForUpdate()
                    ->get();

                if ($parent->count() !== 1) {
                    throw new RuntimeException("Required Chart Account parent [CURRENT_ASSETS] must resolve exactly once for company {$company->id}.");
                }

                if (DB::table('chart_accounts')->where('company_id', $company->id)->where('code', self::CODE)->exists()) {
                    throw new RuntimeException("Cannot provision [" . self::SYSTEM_CODE . "] for company {$company->id}: Chart Account code [" . self::CODE . "] is already assigned.");
                }

                DB::table('chart_accounts')->insert([
                    'company_id' => $company->id,
                    'parent_id' => $parent->first()->id,
                    'code' => self::CODE,
                    'name' => 'Supplier Return Receivable',
                    'account_class' => 'asset',
                    'account_category' => 'supplier_return_receivable',
                    'normal_balance' => 'debit',
                    'system_code' => self::SYSTEM_CODE,
                    'level' => 3,
                    'sort_order' => 1175,
                    'is_system' => true,
                    'is_control' => true,
                    'allow_manual_entry' => false,
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    private function preflight(): void
    {
        foreach (DB::table('companies')->select('id')->orderBy('id')->get() as $company) {
            $matches = DB::table('chart_accounts')->where('company_id', $company->id)->where('system_code', self::SYSTEM_CODE)->get();
            if ($matches->count() > 1) throw new RuntimeException("Required Chart Account system code [" . self::SYSTEM_CODE . "] is duplicated for company {$company->id}.");
            if ($matches->count() === 1) {
                $account = $matches->first();
                if ((string) $account->code !== self::CODE || $account->status !== 'active' || $account->account_class !== 'asset' || $account->normal_balance !== 'debit') {
                    throw new RuntimeException("Required Chart Account [" . self::SYSTEM_CODE . "] is not an active Asset/Debit account with code [" . self::CODE . "] for company {$company->id}.");
                }
                $this->assertCurrentAssetsParent((int) $company->id, (int) $account->parent_id);
                continue;
            }
            if (DB::table('chart_accounts')->where('company_id', $company->id)->where('system_code', 'CURRENT_ASSETS')->where('status', 'active')->count() !== 1) {
                throw new RuntimeException("Required Chart Account parent [CURRENT_ASSETS] must resolve exactly once for company {$company->id}.");
            }
            if (DB::table('chart_accounts')->where('company_id', $company->id)->where('code', self::CODE)->exists()) {
                throw new RuntimeException("Cannot provision [" . self::SYSTEM_CODE . "] for company {$company->id}: Chart Account code [" . self::CODE . "] is already assigned.");
            }
        }
    }

    private function assertCurrentAssetsParent(int $companyId, int $parentId): void
    {
        $validParent = DB::table('chart_accounts')
            ->where('id', $parentId)
            ->where('company_id', $companyId)
            ->where('system_code', 'CURRENT_ASSETS')
            ->where('status', 'active')
            ->exists();

        if (!$validParent) {
            throw new RuntimeException("Required Chart Account [" . self::SYSTEM_CODE . "] must belong to active parent [CURRENT_ASSETS] for company {$companyId}.");
        }
    }

    public function down(): void
    {
        if (DB::table('accounting_entry_lines')->whereIn('chart_account_id', DB::table('chart_accounts')->select('id')->where('system_code', self::SYSTEM_CODE))->exists()) {
            throw new RuntimeException('Cannot remove Supplier Return Receivable because accounting history references it.');
        }

        DB::table('chart_accounts')->where('system_code', self::SYSTEM_CODE)->delete();

        Schema::table('purchase_return_refunds', function (Blueprint $table): void {
            $table->dropUnique('purchase_return_refunds_company_idempotency_unique');
            $table->dropColumn('idempotency_key');
        });
        Schema::table('purchase_returns', function (Blueprint $table): void {
            $table->dropUnique('purchase_returns_company_request_unique');
            $table->dropColumn('request_key');
        });
    }
};
