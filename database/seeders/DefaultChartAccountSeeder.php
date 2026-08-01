<?php

namespace Database\Seeders;

use App\Models\ChartAccount;
use App\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DefaultChartAccountSeeder extends Seeder
{
    public function run(): void
    {
        Company::query()
            ->select('id')
            ->orderBy('id')
            ->chunkById(100, function ($companies): void {
                foreach ($companies as $company) {
                    DB::transaction(function () use ($company): void {
                        $seededAccounts = [];

                        foreach ($this->definitions() as $definition) {
                            $parent = null;

                            if ($definition['parent_system_code'] !== null) {
                                $parent = $seededAccounts[$definition['parent_system_code']]
                                    ?? ChartAccount::query()
                                        ->forCompany($company->id)
                                        ->where('system_code', $definition['parent_system_code'])
                                        ->where('is_system', true)
                                        ->first();

                                if (! $parent) {
                                    continue;
                                }
                            }

                            $account = $this->upsertSystemAccount(
                                $company->id,
                                $definition,
                                $parent?->id
                            );

                            if ($account) {
                                $seededAccounts[$definition['system_code']] = $account;
                            }
                        }
                    });
                }
            });
    }

    private function upsertSystemAccount(int $companyId, array $definition, ?int $parentId): ?ChartAccount
    {
        $attributes = [
            'company_id' => $companyId,
            'system_code' => $definition['system_code'],
            'is_system' => true,
        ];

        $values = [
            'company_id' => $companyId,
            'parent_id' => $parentId,
            'code' => $definition['code'],
            'name' => $definition['name'],
            'account_class' => $definition['account_class'],
            'account_category' => $definition['account_category'],
            'normal_balance' => $definition['normal_balance'],
            'system_code' => $definition['system_code'],
            'level' => $definition['level'],
            'sort_order' => $definition['sort_order'],
            'is_system' => true,
            'is_control' => $definition['is_control'],
            'allow_manual_entry' => $definition['allow_manual_entry'],
            'status' => 'active',
        ];

        $account = ChartAccount::query()->where($attributes)->first();

        if ($account) {
            $account->fill($values);
            $account->save();

            return $account;
        }

        if (ChartAccount::query()
            ->forCompany($companyId)
            ->where('code', $definition['code'])
            ->exists()) {
            return null;
        }

        return ChartAccount::create($values);
    }

    private function definitions(): array
    {
        return [
            $this->account('1000', 'Assets', 'asset', 'asset', 'debit', 'ASSETS', null, 1, true, false),
            $this->account('2000', 'Liabilities', 'liability', 'liability', 'credit', 'LIABILITIES', null, 1, true, false),
            $this->account('3000', 'Equity', 'equity', 'equity', 'credit', 'EQUITY', null, 1, true, false),
            $this->account('4000', 'Income', 'income', 'income', 'credit', 'INCOME', null, 1, true, false),
            $this->account('5000', 'Expenses', 'expense', 'expense', 'debit', 'EXPENSES', null, 1, true, false),

            $this->account('1100', 'Current Assets', 'asset', 'current_asset', 'debit', 'CURRENT_ASSETS', 'ASSETS', 2, true, false),
            $this->account('1200', 'Non-current Assets', 'asset', 'non_current_asset', 'debit', 'NON_CURRENT_ASSETS', 'ASSETS', 2, true, false),
            $this->account('2100', 'Current Liabilities', 'liability', 'current_liability', 'credit', 'CURRENT_LIABILITIES', 'LIABILITIES', 2, true, false),
            $this->account('2200', 'Non-current Liabilities', 'liability', 'non_current_liability', 'credit', 'NON_CURRENT_LIABILITIES', 'LIABILITIES', 2, true, false),
            $this->account('3100', 'Owner’s Equity', 'equity', 'owner_equity', 'credit', 'OWNERS_EQUITY', 'EQUITY', 2, true, false),
            $this->account('4100', 'Operating Income', 'income', 'operating_income', 'credit', 'OPERATING_INCOME', 'INCOME', 2, true, false),
            $this->account('4200', 'Other Income', 'income', 'other_income', 'credit', 'OTHER_INCOME', 'INCOME', 2, true, false),
            $this->account('5100', 'Cost of Sales', 'expense', 'cost_of_sales', 'debit', 'COST_OF_SALES', 'EXPENSES', 2, true, false),
            $this->account('5200', 'Operating Expenses', 'expense', 'operating_expense', 'debit', 'OPERATING_EXPENSES', 'EXPENSES', 2, true, false),
            $this->account('5300', 'Finance Costs', 'expense', 'finance_cost', 'debit', 'FINANCE_COSTS', 'EXPENSES', 2, true, false),

            $this->account('1110', 'Cash in Hand', 'asset', 'cash', 'debit', 'CASH_IN_HAND', 'CURRENT_ASSETS', 3, false, true),
            $this->account('1120', 'Bank Accounts', 'asset', 'bank', 'debit', 'BANK_ACCOUNTS', 'CURRENT_ASSETS', 3, false, true),
            $this->account('1130', 'Accounts Receivable', 'asset', 'receivable', 'debit', 'ACCOUNTS_RECEIVABLE', 'CURRENT_ASSETS', 3, false, false),
            $this->account('1140', 'Inventory', 'asset', 'inventory', 'debit', 'INVENTORY', 'CURRENT_ASSETS', 3, false, false),
            $this->account('1150', 'Input Tax Receivable', 'asset', 'tax_receivable', 'debit', 'INPUT_TAX_RECEIVABLE', 'CURRENT_ASSETS', 3, false, false),
            $this->account('1160', 'Loan Receivable', 'asset', 'loan_receivable', 'debit', 'LOAN_RECEIVABLE', 'CURRENT_ASSETS', 3, false, false),
            $this->account('1165', 'Loan Compulsory Saving', 'asset', 'loan_deposit_asset', 'debit', 'LOAN_COMPULSORY_SAVING_ASSET', 'CURRENT_ASSETS', 3, false, false),
            $this->account('1170', 'Other Current Assets', 'asset', 'other_current_asset', 'debit', 'OTHER_CURRENT_ASSETS', 'CURRENT_ASSETS', 3, false, true),
            $this->account('1210', 'Property, Plant and Equipment', 'asset', 'fixed_asset', 'debit', 'PROPERTY_PLANT_EQUIPMENT', 'NON_CURRENT_ASSETS', 3, false, true),
            $this->account('1220', 'Accumulated Depreciation', 'asset', 'accumulated_depreciation', 'credit', 'ACCUMULATED_DEPRECIATION', 'NON_CURRENT_ASSETS', 3, false, true),
            $this->account('2110', 'Accounts Payable', 'liability', 'payable', 'credit', 'ACCOUNTS_PAYABLE', 'CURRENT_LIABILITIES', 3, false, false),
            $this->account('2120', 'Output Tax Payable', 'liability', 'tax_payable', 'credit', 'OUTPUT_TAX_PAYABLE', 'CURRENT_LIABILITIES', 3, false, false),
            $this->account('2130', 'Salary Payable', 'liability', 'salary_payable', 'credit', 'SALARY_PAYABLE', 'CURRENT_LIABILITIES', 3, false, false),
            $this->account('2140', 'Personal Loan Payable', 'liability', 'personal_loan_payable', 'credit', 'PERSONAL_LOAN_PAYABLE', 'CURRENT_LIABILITIES', 3, false, false),
            $this->account('2150', 'Other Current Liabilities', 'liability', 'other_current_liability', 'credit', 'OTHER_CURRENT_LIABILITIES', 'CURRENT_LIABILITIES', 3, false, true),
            $this->account('2160', 'Loan Payable', 'liability', 'loan_payable', 'credit', 'LOAN_PAYABLE', 'CURRENT_LIABILITIES', 3, false, false),
            $this->account('2210', 'Bank Loan Payable', 'liability', 'bank_loan_payable', 'credit', 'BANK_LOAN_PAYABLE', 'NON_CURRENT_LIABILITIES', 3, false, false),
            $this->account('3110', 'Owner’s Capital', 'equity', 'capital', 'credit', 'OWNERS_CAPITAL', 'OWNERS_EQUITY', 3, false, true),
            $this->account('3120', 'Owner’s Drawings', 'equity', 'drawings', 'debit', 'OWNERS_DRAWINGS', 'OWNERS_EQUITY', 3, false, true),
            $this->account('3130', 'Retained Earnings', 'equity', 'retained_earnings', 'credit', 'RETAINED_EARNINGS', 'OWNERS_EQUITY', 3, false, false),
            $this->account('3140', 'Opening Balance Equity', 'equity', 'opening_balance_equity', 'credit', 'OPENING_BALANCE_EQUITY', 'OWNERS_EQUITY', 3, false, false),
            $this->account('4110', 'Sales Revenue', 'income', 'sales_income', 'credit', 'SALES_REVENUE', 'OPERATING_INCOME', 3, false, false),
            $this->account('4120', 'Service Revenue', 'income', 'service_income', 'credit', 'SERVICE_REVENUE', 'OPERATING_INCOME', 3, false, false),
            $this->account('4130', 'Sales Returns and Allowances', 'income', 'sales_return', 'debit', 'SALES_RETURNS', 'OPERATING_INCOME', 3, false, false),
            $this->account('4210', 'Other Income', 'income', 'other_income', 'credit', 'OTHER_INCOME_REVENUE', 'OTHER_INCOME', 3, false, true),
            $this->account('4220', 'Loan Interest Income', 'income', 'loan_interest_income', 'credit', 'LOAN_INTEREST_INCOME', 'OTHER_INCOME', 3, false, false),
            $this->account('4230', 'Loan Fine Income', 'income', 'loan_fine_income', 'credit', 'LOAN_FINE_INCOME', 'OTHER_INCOME', 3, false, false),
            $this->account('5110', 'Cost of Goods Sold', 'expense', 'cost_of_goods_sold', 'debit', 'COST_OF_GOODS_SOLD', 'COST_OF_SALES', 3, false, false),
            $this->account('5120', 'Purchase Returns', 'expense', 'purchase_return', 'credit', 'PURCHASE_RETURNS', 'COST_OF_SALES', 3, false, false),
            $this->account('5210', 'Salary Expense', 'expense', 'salary_expense', 'debit', 'SALARY_EXPENSE', 'OPERATING_EXPENSES', 3, false, false),
            $this->account('5220', 'Rent Expense', 'expense', 'rent_expense', 'debit', 'RENT_EXPENSE', 'OPERATING_EXPENSES', 3, false, true),
            $this->account('5230', 'Utilities Expense', 'expense', 'utilities_expense', 'debit', 'UTILITIES_EXPENSE', 'OPERATING_EXPENSES', 3, false, true),
            $this->account('5240', 'Delivery Expense', 'expense', 'delivery_expense', 'debit', 'DELIVERY_EXPENSE', 'OPERATING_EXPENSES', 3, false, true),
            $this->account('5250', 'General Expense', 'expense', 'general_expense', 'debit', 'GENERAL_EXPENSE', 'OPERATING_EXPENSES', 3, false, true),
            $this->account('5260', 'Depreciation Expense', 'expense', 'depreciation_expense', 'debit', 'DEPRECIATION_EXPENSE', 'OPERATING_EXPENSES', 3, false, true),
            $this->account('5270', 'Service Purchase Expense', 'expense', 'service_purchase_expense', 'debit', 'SERVICE_PURCHASE_EXPENSE', 'OPERATING_EXPENSES', 3, false, false),
            $this->account('5310', 'Interest Expense', 'expense', 'interest_expense', 'debit', 'INTEREST_EXPENSE', 'FINANCE_COSTS', 3, false, true),
            $this->account('5320', 'Loan Interest Expense', 'expense', 'loan_interest_expense', 'debit', 'LOAN_INTEREST_EXPENSE', 'FINANCE_COSTS', 3, false, false),
            $this->account('5330', 'Loan Fine Expense', 'expense', 'loan_fine_expense', 'debit', 'LOAN_FINE_EXPENSE', 'FINANCE_COSTS', 3, false, false),
        ];
    }

    private function account(
        string $code,
        string $name,
        string $accountClass,
        string $accountCategory,
        string $normalBalance,
        string $systemCode,
        ?string $parentSystemCode,
        int $level,
        bool $isControl,
        bool $allowManualEntry
    ): array {
        return [
            'code' => $code,
            'name' => $name,
            'account_class' => $accountClass,
            'account_category' => $accountCategory,
            'normal_balance' => $normalBalance,
            'system_code' => $systemCode,
            'parent_system_code' => $parentSystemCode,
            'level' => $level,
            'sort_order' => (int) $code,
            'is_control' => $isControl,
            'allow_manual_entry' => $allowManualEntry,
        ];
    }
}
