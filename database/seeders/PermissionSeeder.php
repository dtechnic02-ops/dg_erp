<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        $permissions = [
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
            'platform_companies_view',
            'platform_companies_block',
            'platform_companies_unblock',
            'platform_registrations_view',
            'platform_registrations_approve',
            'platform_registrations_reject',
            'platform_subscriptions_view',
            'platform_subscription_payments_view',
            'platform_subscription_payments_invoice_view',
            'platform_subscription_reports_view',
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
