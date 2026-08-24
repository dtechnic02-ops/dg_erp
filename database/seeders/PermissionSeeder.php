<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        foreach ([
            'module_users', 'module_income', 'module_expense', 'module_journal',
            'module_opening_balance', 'module_loan', 'module_hr', 'module_payroll',
            'module_delivery', 'module_crm', 'module_company_profile', 'module_maintenance',
            'module_sales', 'module_sales_payment', 'module_customer', 'module_purchase',
            'module_supplier', 'module_stock', 'module_accounts', 'module_account_transaction',
            'module_contra', 'module_vat', 'module_reports', 'module_quotation',
        ] as $modulePermission) {
            Permission::firstOrCreate(['name' => $modulePermission], ['scope' => Permission::SCOPE_COMPANY]);
        }

        Permission::firstOrCreate(['name' => 'system_maintenance'], ['scope' => Permission::SCOPE_COMPANY]);

        $permissions = [
            ...collect([
                'view_sales', 'create_sales', 'edit_sales', 'cancel_sales', 'print_sales',
                'view_quotation', 'create_quotation', 'edit_quotation', 'delete_quotation',
                'approve_quotation', 'generate_quotation_invoice', 'print_quotation',
                'view_sales_payment', 'create_sales_payment', 'edit_sales_payment',
                'cancel_sales_payment', 'print_sales_payment',
                'view_customer', 'create_customer', 'edit_customer', 'delete_customer', 'print_customer',
                'view_purchase', 'create_purchase', 'edit_purchase', 'cancel_purchase', 'print_purchase',
                'view_supplier', 'create_supplier', 'edit_supplier', 'delete_supplier', 'print_supplier',
                'view_stock', 'print_stock',
                'view_accounts', 'create_accounts', 'edit_accounts', 'delete_accounts', 'print_accounts',
                'view_account_transaction',
                'view_contra', 'create_contra', 'edit_contra', 'delete_contra', 'print_contra',
                'view_vat', 'create_vat', 'edit_vat', 'delete_vat', 'print_vat',
                'view_reports', 'print_reports',
            ])->map(fn (string $name) => Permission::firstOrCreate(
                ['name' => $name],
                ['scope' => Permission::SCOPE_COMPANY]
            ))->all(),
            ...collect([
                'journal.view', 'journal.create', 'journal.edit-draft', 'journal.submit',
                'journal.approve', 'journal.reject', 'journal.post', 'journal.cancel',
                'journal.reverse', 'journal.lock', 'journal.unlock', 'journal.audit-view',
                'journal.print', 'journal.export',
            ])->map(fn (string $name) => Permission::firstOrCreate(['name' => $name]))->all(),
            ...collect([
                'opening-balance.view', 'opening-balance.create', 'opening-balance.edit-draft',
                'opening-balance.submit', 'opening-balance.approve', 'opening-balance.post',
                'opening-balance.cancel', 'opening-balance.reverse', 'opening-balance.lock',
                'opening-balance.unlock', 'opening-balance.audit-view', 'opening-balance.print',
                'opening-balance.export',
            ])->map(fn (string $name) => Permission::firstOrCreate(['name' => $name]))->all(),
            Permission::firstOrCreate(['name' => 'view_users']),
            Permission::firstOrCreate(['name' => 'edit_users']),
            Permission::firstOrCreate(['name' => 'delete_users']),
            Permission::firstOrCreate(['name' => 'manage_users']),
            Permission::firstOrCreate(['name' => 'block_user']),
            Permission::firstOrCreate(['name' => 'reset_password']),
            Permission::firstOrCreate(['name' => 'delete_user']),
            Permission::firstOrCreate(['name' => 'view_income']),
            Permission::firstOrCreate(['name' => 'create_income']),
            Permission::firstOrCreate(['name' => 'edit_income']),
            Permission::firstOrCreate(['name' => 'cancel_income']),
            Permission::firstOrCreate(['name' => 'print_income']),
            Permission::firstOrCreate(['name' => 'view_income_categories']),
            Permission::firstOrCreate(['name' => 'manage_income_categories']),
            Permission::firstOrCreate(['name' => 'view_expense']),
            Permission::firstOrCreate(['name' => 'create_expense']),
            Permission::firstOrCreate(['name' => 'edit_expense']),
            Permission::firstOrCreate(['name' => 'cancel_expense']),
            Permission::firstOrCreate(['name' => 'print_expense']),
            Permission::firstOrCreate(['name' => 'view_expense_categories']),
            Permission::firstOrCreate(['name' => 'manage_expense_categories']),
            Permission::firstOrCreate(['name' => 'view_journal']),
            Permission::firstOrCreate(['name' => 'create_journal']),
            Permission::firstOrCreate(['name' => 'edit_journal']),
            Permission::firstOrCreate(['name' => 'cancel_journal']),
            Permission::firstOrCreate(['name' => 'print_journal']),
            Permission::firstOrCreate(['name' => 'view_loan_account']),
            Permission::firstOrCreate(['name' => 'create_loan_account']),
            Permission::firstOrCreate(['name' => 'edit_loan_account']),
            Permission::firstOrCreate(['name' => 'cancel_loan_account']),
            Permission::firstOrCreate(['name' => 'print_loan_account']),
            Permission::firstOrCreate(['name' => 'view_loan_payment']),
            Permission::firstOrCreate(['name' => 'create_loan_payment']),
            Permission::firstOrCreate(['name' => 'edit_loan_payment']),
            Permission::firstOrCreate(['name' => 'cancel_loan_payment']),
            Permission::firstOrCreate(['name' => 'print_loan_payment']),
            Permission::firstOrCreate(['name' => 'view_loan_saving_ledger']),
            Permission::firstOrCreate(['name' => 'print_loan_saving_ledger']),
            Permission::firstOrCreate(['name' => 'create_loan_saving_withdraw']),
            Permission::firstOrCreate(['name' => 'cancel_loan_saving_withdraw']),
            Permission::firstOrCreate(['name' => 'employee.view']),
            Permission::firstOrCreate(['name' => 'employee.create']),
            Permission::firstOrCreate(['name' => 'employee.edit']),
            Permission::firstOrCreate(['name' => 'employee.delete']),
            Permission::firstOrCreate(['name' => 'employee.status']),
            Permission::firstOrCreate(['name' => 'salary.view']),
            Permission::firstOrCreate(['name' => 'salary.create']),
            Permission::firstOrCreate(['name' => 'salary.edit']),
            Permission::firstOrCreate(['name' => 'salary.cancel']),
            Permission::firstOrCreate(['name' => 'salary.payment.view']),
            Permission::firstOrCreate(['name' => 'salary.payment.create']),
            Permission::firstOrCreate(['name' => 'salary.payment.edit']),
            Permission::firstOrCreate(['name' => 'salary.payment.cancel']),
            Permission::firstOrCreate(['name' => 'view_delivery']),
            Permission::firstOrCreate(['name' => 'create_delivery']),
            Permission::firstOrCreate(['name' => 'edit_delivery']),
            Permission::firstOrCreate(['name' => 'cancel_delivery']),
            Permission::firstOrCreate(['name' => 'print_delivery']),
            Permission::firstOrCreate(['name' => 'process_delivery']),
            Permission::firstOrCreate(['name' => 'view_crm_dashboard']),
            Permission::firstOrCreate(['name' => 'view_crm_lead']),
            Permission::firstOrCreate(['name' => 'create_crm_lead']),
            Permission::firstOrCreate(['name' => 'edit_crm_lead']),
            Permission::firstOrCreate(['name' => 'close_crm_lead']),
            Permission::firstOrCreate(['name' => 'archive_crm_lead']),
            Permission::firstOrCreate(['name' => 'cancel_crm_lead']),
            Permission::firstOrCreate(['name' => 'view_crm_contact']),
            Permission::firstOrCreate(['name' => 'create_crm_contact']),
            Permission::firstOrCreate(['name' => 'edit_crm_contact']),
            Permission::firstOrCreate(['name' => 'archive_crm_contact']),
            Permission::firstOrCreate(['name' => 'cancel_crm_contact']),
            Permission::firstOrCreate(['name' => 'view_crm_opportunity']),
            Permission::firstOrCreate(['name' => 'create_crm_opportunity']),
            Permission::firstOrCreate(['name' => 'edit_crm_opportunity']),
            Permission::firstOrCreate(['name' => 'close_crm_opportunity']),
            Permission::firstOrCreate(['name' => 'archive_crm_opportunity']),
            Permission::firstOrCreate(['name' => 'cancel_crm_opportunity']),
            Permission::firstOrCreate(['name' => 'view_crm_follow_up']),
            Permission::firstOrCreate(['name' => 'create_crm_follow_up']),
            Permission::firstOrCreate(['name' => 'edit_crm_follow_up']),
            Permission::firstOrCreate(['name' => 'archive_crm_follow_up']),
            Permission::firstOrCreate(['name' => 'cancel_crm_follow_up']),
            Permission::firstOrCreate(['name' => 'view_crm_meeting']),
            Permission::firstOrCreate(['name' => 'create_crm_meeting']),
            Permission::firstOrCreate(['name' => 'edit_crm_meeting']),
            Permission::firstOrCreate(['name' => 'archive_crm_meeting']),
            Permission::firstOrCreate(['name' => 'cancel_crm_meeting']),
            Permission::firstOrCreate(['name' => 'view_crm_task']),
            Permission::firstOrCreate(['name' => 'create_crm_task']),
            Permission::firstOrCreate(['name' => 'edit_crm_task']),
            Permission::firstOrCreate(['name' => 'archive_crm_task']),
            Permission::firstOrCreate(['name' => 'cancel_crm_task']),
            Permission::firstOrCreate(['name' => 'create_crm_note']),
            Permission::firstOrCreate(['name' => 'edit_crm_note']),
            Permission::firstOrCreate(['name' => 'archive_crm_note']),
            Permission::firstOrCreate(['name' => 'view_crm_attachment']),
            Permission::firstOrCreate(['name' => 'create_crm_attachment']),
            Permission::firstOrCreate(['name' => 'archive_crm_attachment']),
            Permission::firstOrCreate(['name' => 'report_crm']),
            Permission::firstOrCreate(['name' => 'export_crm']),
            Permission::firstOrCreate(['name' => 'manage_crm_settings']),
            Permission::firstOrCreate(['name' => 'view_subscription_module']),
            Permission::firstOrCreate(['name' => 'manage_subscription_module']),
            Permission::firstOrCreate(['name' => 'view_company']),
            Permission::firstOrCreate(['name' => 'create_company']),
            Permission::firstOrCreate(['name' => 'edit_company']),
            Permission::firstOrCreate(['name' => 'approve_company']),
            Permission::firstOrCreate(['name' => 'block_company']),
            Permission::firstOrCreate(['name' => 'unblock_company']),
            Permission::firstOrCreate(['name' => 'delete_company']),
            Permission::firstOrCreate(['name' => 'reset_company_password']),
            Permission::firstOrCreate(['name' => 'view_company_profile']),
            Permission::firstOrCreate(['name' => 'edit_company_profile']),
        ];

        $permissionIds = collect($permissions)->pluck('id')->all();

        $platformPermissionNames = [
            'platform_module_dashboard',
            'platform_module_companies',
            'platform_module_registrations',
            'platform_module_subscriptions',
            'platform_module_subscription_payments',
            'platform_module_subscription_reports',
            'platform_module_settings',
            'platform_module_plans',
            'platform_module_super_staff',
            'platform_module_users',
            'platform_dashboard_view',
            'platform_companies_view',
            'platform_companies_block',
            'platform_companies_unblock',
            'platform_companies_edit',
            'platform_companies_delete',
            'platform_companies_reset_password',
            'platform_registrations_view',
            'platform_registrations_approve',
            'platform_registrations_reject',
            'platform_subscriptions_view',
            'platform_subscriptions_manage',
            'platform_subscription_payments_view',
            'platform_subscription_payments_invoice_view',
            'platform_subscription_reports_view',
            'platform_settings_manage',
            'platform_plans_manage',
            'platform_super_staff_manage',
            'platform_users_manage',
        ];

        foreach ($platformPermissionNames as $platformPermissionName) {
            Permission::firstOrCreate(
                ['name' => $platformPermissionName],
                ['scope' => Permission::SCOPE_PLATFORM]
            );
        }

        $companyPermissionIds = Permission::company()->pluck('id')->all();

        $superAdmin = Role::where('name', 'super_admin')->first();
        $admin = Role::where('name', 'company_admin')->first();
        $staff = Role::where('name', 'staff')->first();

        if ($superAdmin) {
            $superAdmin->permissions()->syncWithoutDetaching($permissionIds);
        }

        if ($admin) {
            $admin->permissions()->syncWithoutDetaching($companyPermissionIds);
        }

        if ($staff) {
            $staff->permissions()->syncWithoutDetaching(
                Permission::company()
                    ->whereIn('name', [
                        'view_income',
                        'view_income_categories',
                        'print_income',
                        'view_expense',
                        'view_expense_categories',
                        'print_expense',
                        'view_journal',
                        'print_journal',
                        'view_loan_account',
                        'print_loan_account',
                        'view_loan_payment',
                        'print_loan_payment',
                        'view_loan_saving_ledger',
                        'print_loan_saving_ledger',
                        'employee.view',
                        'salary.view',
                        'salary.payment.view',
                        'view_delivery',
                        'print_delivery',
                        'process_delivery',
                        'view_crm_dashboard',
                        'view_crm_lead',
                        'view_crm_contact',
                        'view_crm_opportunity',
                        'view_crm_follow_up',
                        'view_crm_meeting',
                        'view_crm_task',
                        'report_crm',
                    ])
                    ->pluck('id')
                    ->all()
            );
        }
    }
}
