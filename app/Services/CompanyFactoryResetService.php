<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyFactoryResetAudit;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

class CompanyFactoryResetService
{
    private const PRESERVED_COMPANY_TABLES = [
        'company_permission', 'company_subscriptions', 'company_ird_cbms_settings', 'company_cbms_api_configurations', 'company_tax_settings', 'company_whatsapp_settings',
        'subscription_histories', 'subscription_payments', 'company_factory_reset_audits',
    ];

    private const RESET_ORDER = [
        'cbms_transmission_attempts',
        'cbms_transmissions',
        'sales_return_refund_adjustments', 'purchase_return_refund_adjustments',
        'sales_cost_snapshots', 'inventory_valuations',
        'delivery_attachments', 'delivery_signatures', 'delivery_status_histories', 'delivery_note_items',
        'crm_attachments', 'crm_notes', 'crm_tasks', 'crm_follow_ups', 'crm_meetings',
        'crm_status_histories', 'crm_opportunities', 'crm_contacts', 'crm_leads',
        'fiscal_document_audit_events', 'opening_balance_audit_events', 'journal_audit_events',
        'sales_return_refunds', 'purchase_return_refunds', 'sales_return_items', 'purchase_return_items',
        'sales_payments', 'purchase_payments', 'invoice_payments',
        'stock_transactions', 'stock_movements', 'sales_items', 'purchase_items', 'quotation_items',
        'loan_saving_ledgers', 'loan_payments', 'employee_payments', 'salary_sheets',
        'customer_transactions', 'supplier_transactions', 'account_transactions',
        'sales_returns', 'purchase_returns', 'delivery_notes', 'quotations',
        'sales_invoices', 'purchase_invoices', 'journal_items', 'journals', 'opening_balances',
        'accounting_entries', 'loan_accounts', 'expenses', 'incomes',
        'crm_configurations', 'employee_accounts', 'party_accounts',
        'customers', 'suppliers', 'products', 'services', 'product_categories',
        'service_categories', 'brands', 'units', 'vats',
        'expense_categories', 'income_categories', 'contras', 'cash_accounts', 'accounts',
        'accounting_period_locks', 'journal_number_sequences', 'opening_balance_legacy_records',
        'loan_integrity_seeded_chart_accounts', 'financial_years', 'chart_accounts',
    ];

    private const FILE_COLUMNS = [
        'accounts' => ['image_path'],
        'brands' => ['image'],
        'contras' => ['attachment'],
        'crm_attachments' => ['file_path'],
        'customers' => ['image_path'],
        'delivery_attachments' => ['file_path'],
        'delivery_notes' => ['pdf_path'],
        'delivery_signatures' => ['signature_path'],
        'employee_accounts' => ['cv_attachment', 'id_document', 'contract_document'],
        'employee_payments' => ['attachment'],
        'expenses' => ['attachment'],
        'incomes' => ['attachment'],
        'journals' => ['attachment'],
        'loan_accounts' => ['attachment'],
        'loan_payments' => ['attachment'],
        'loan_saving_ledgers' => ['attachment'],
        'party_accounts' => ['document'],
        'products' => ['image'],
        'purchase_payments' => ['receipt_file'],
        'purchase_return_refunds' => ['attachment'],
        'sales_payments' => ['receipt_file'],
        'sales_return_refunds' => ['attachment'],
        'service_categories' => ['upload_path'],
        'services' => ['upload_path'],
        'suppliers' => ['image_path'],
    ];

    public function __construct(
        private DefaultChartAccountBootstrapService $chartAccounts,
        private FiscalDocumentPolicyService $fiscalDocumentPolicy,
    ) {
    }

    public function reset(Company $company, int $initiatingAdminId, string $currentSessionId): CompanyFactoryResetAudit
    {
        $this->assertInventoryComplete();
        $this->assertQueueIsSafe();
        $manifest = $this->buildFileManifest($company->id);
        $requestedAt = now();

        $audit = DB::transaction(function () use ($company, $initiatingAdminId, $currentSessionId, $requestedAt, $manifest): CompanyFactoryResetAudit {
            $locked = Company::query()->lockForUpdate()->find($company->id);
            if (! $locked || $locked->status !== 'active') {
                throw new RuntimeException('The Company is not active or no longer exists.');
            }
            if ($this->fiscalDocumentPolicy->companyHasPermanentFiscalHistory($locked)) {
                throw new RuntimeException('Factory Reset is not allowed while Nepal IRD/CBMS mode is active.');
            }

            $users = DB::table('users')->where('company_id', $locked->id)->lockForUpdate()->get(['id', 'email', 'role_id', 'password', 'account_status']);
            $unknownRoles = $users->whereNotIn('role_id', [Role::COMPANY_ADMIN_ID, Role::COMPANY_STAFF_ID]);
            if ($unknownRoles->isNotEmpty()) {
                throw new RuntimeException('An unknown Company-side role blocks Factory Reset safely.');
            }
            $admins = $users->where('role_id', Role::COMPANY_ADMIN_ID);
            if (! $admins->contains('id', $initiatingAdminId)) {
                throw new RuntimeException('The initiating Company Admin no longer belongs to this Company.');
            }

            $preserved = $this->preservationSnapshot($locked->id, $admins->pluck('id')->all());
            $staff = $users->where('role_id', Role::COMPANY_STAFF_ID);
            $staffIds = $staff->pluck('id')->all();
            $staffEmails = $staff->pluck('email')->filter()->values()->all();
            $adminIds = $admins->pluck('id')->all();
            $adminEmails = $admins->pluck('email')->filter()->values()->all();
            $counts = [];

            $this->removeStaffAuthenticationResidue($staffIds, $staffEmails, $counts);
            $this->invalidateAdminPasswordResets($adminIds, $adminEmails, $counts);
            $counts['admin_sessions_revoked'] = DB::table('sessions')->whereIn('user_id', $adminIds)
                ->where(function ($query) use ($initiatingAdminId, $currentSessionId): void {
                    $query->where('user_id', '!=', $initiatingAdminId)->orWhere('id', '!=', $currentSessionId);
                })->delete();

            $this->deleteIndirectChildren($locked->id, $counts);
            foreach (self::RESET_ORDER as $table) {
                $counts[$table] = DB::table($table)->where('company_id', $locked->id)->delete();
            }
            $counts['users'] = $staffIds === [] ? 0 : DB::table('users')->where('company_id', $locked->id)
                ->where('role_id', Role::COMPANY_STAFF_ID)->whereIn('id', $staffIds)->delete();

            $this->chartAccounts->seedForCompany($locked->id);
            $this->assertResetComplete($locked->id);
            $this->assertPreserved($locked->id, $admins->pluck('id')->all(), $preserved);

            return CompanyFactoryResetAudit::create([
                'company_id' => $locked->id,
                'initiated_by' => $initiatingAdminId,
                'company_name' => $locked->company_name,
                'requested_at' => $requestedAt,
                'completed_at' => now(),
                'result' => 'reset',
                'deleted_counts' => $counts,
                'file_manifest' => $manifest,
                'file_cleanup_state' => 'pending',
            ]);
        }, 3);

        $this->cleanupFiles($audit, $manifest);
        return $audit->fresh();
    }

    public function retryFileCleanup(CompanyFactoryResetAudit $audit): CompanyFactoryResetAudit
    {
        if ($audit->file_cleanup_state !== 'failed' || ! is_array($audit->file_manifest)) {
            throw new RuntimeException('No failed Factory Reset file cleanup is available for retry.');
        }
        $this->cleanupFiles($audit, $audit->file_manifest);
        return $audit->fresh();
    }

    private function preservationSnapshot(int $companyId, array $adminIds): array
    {
        return [
            'company' => (array) DB::table('companies')->where('id', $companyId)->first(),
            'admins' => DB::table('users')->whereIn('id', $adminIds)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all(),
            'admin_permissions' => DB::table('user_permissions')->whereIn('user_id', $adminIds)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all(),
            'company_permission' => DB::table('company_permission')->where('company_id', $companyId)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all(),
            'company_subscriptions' => DB::table('company_subscriptions')->where('company_id', $companyId)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all(),
            'subscription_payments' => DB::table('subscription_payments')->where('company_id', $companyId)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all(),
            'subscription_histories' => DB::table('subscription_histories')->where('company_id', $companyId)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all(),
            'ird_cbms' => DB::table('company_ird_cbms_settings')->where('company_id', $companyId)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all(),
            'cbms_api' => DB::table('company_cbms_api_configurations')->where('company_id', $companyId)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all(),
            'tax_settings' => DB::table('company_tax_settings')->where('company_id', $companyId)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all(),
            'whatsapp' => DB::table('company_whatsapp_settings')->where('company_id', $companyId)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all(),
        ];
    }

    private function assertPreserved(int $companyId, array $adminIds, array $before): void
    {
        $after = $this->preservationSnapshot($companyId, $adminIds);
        if ($after !== $before) {
            throw new RuntimeException('Preserved Company identity, administrators, subscription, permissions, or settings changed; Factory Reset was rolled back.');
        }
    }

    private function removeStaffAuthenticationResidue(array $staffIds, array $emails, array &$counts): void
    {
        if ($staffIds === []) return;
        $counts['staff_sessions'] = DB::table('sessions')->whereIn('user_id', $staffIds)->delete();
        $counts['staff_permissions'] = DB::table('user_permissions')->whereIn('user_id', $staffIds)->delete();
        $counts['staff_password_tokens'] = $emails === [] ? 0 : DB::table('password_reset_tokens')->whereIn('email', $emails)->delete();
        $counts['staff_password_requests'] = DB::table('password_reset_requests')->where(function ($query) use ($staffIds, $emails): void {
            $query->whereIn('user_id', $staffIds)->orWhereIn('initiated_by', $staffIds);
            if ($emails !== []) $query->orWhereIn('user_email', $emails)->orWhereIn('initiated_by_email', $emails);
        })->delete();
    }

    private function invalidateAdminPasswordResets(array $adminIds, array $emails, array &$counts): void
    {
        $counts['admin_password_requests_invalidated'] = DB::table('password_reset_requests')
            ->whereNull('used_at')->whereNull('invalidated_at')
            ->where(function ($query) use ($adminIds, $emails): void {
                $query->whereIn('user_id', $adminIds);
                if ($emails !== []) $query->orWhereIn('user_email', $emails);
            })->update([
                'invalidated_at' => now(), 'pending_password_hash' => null,
                'otp_hash' => null, 'otp_session_hash' => null,
            ]);
        if ($emails !== []) DB::table('password_reset_tokens')->whereIn('email', $emails)->delete();
    }

    private function deleteIndirectChildren(int $companyId, array &$counts): void
    {
        $entryIds = DB::table('accounting_entries')->where('company_id', $companyId)->pluck('id');
        $counts['accounting_entry_lines'] = $entryIds->isEmpty() ? 0 : DB::table('accounting_entry_lines')->whereIn('accounting_entry_id', $entryIds)->delete();
        $openingIds = DB::table('opening_balances')->where('company_id', $companyId)->pluck('id');
        $counts['opening_balance_lines'] = $openingIds->isEmpty() ? 0 : DB::table('opening_balance_lines')->whereIn('opening_balance_id', $openingIds)->delete();
    }

    private function assertResetComplete(int $companyId): void
    {
        foreach (array_diff(self::RESET_ORDER, ['chart_accounts']) as $table) {
            if (DB::table($table)->where('company_id', $companyId)->exists()) {
                throw new RuntimeException("Company operational rows remain in {$table}; Factory Reset was rolled back.");
            }
        }
        if (DB::table('users')->where('company_id', $companyId)->where('role_id', Role::COMPANY_STAFF_ID)->exists()) {
            throw new RuntimeException('Company Staff remain after Factory Reset.');
        }
        $duplicateSystemCode = DB::table('chart_accounts')->where('company_id', $companyId)->whereNotNull('system_code')
            ->select('system_code')->groupBy('system_code')->havingRaw('COUNT(*) > 1')->exists();
        if ($duplicateSystemCode || ! DB::table('chart_accounts')->where('company_id', $companyId)->where('is_system', true)->exists()) {
            throw new RuntimeException('Default Chart of Accounts was not reseeded safely.');
        }
    }

    private function assertInventoryComplete(): void
    {
        $classified = array_merge(self::RESET_ORDER, self::PRESERVED_COMPANY_TABLES, ['users']);
        $missing = array_values(array_diff(CompanyPermanentDeletionService::COMPANY_TABLES, $classified));
        if ($missing !== []) throw new RuntimeException('Unclassified Company tables block Factory Reset: '.implode(', ', $missing));
    }

    private function assertQueueIsSafe(): void
    {
        if (Schema::hasTable('jobs') && DB::table('jobs')->exists()) {
            throw new RuntimeException('Pending queued-job ownership cannot be proven. Factory Reset is blocked safely.');
        }
    }

    private function buildFileManifest(int $companyId): array
    {
        $manifest = [
            ['type' => 'directory', 'path' => $this->storageCompanyRoot().DIRECTORY_SEPARATOR.$companyId, 'root' => $this->storageCompanyRoot()],
            ['type' => 'directory', 'path' => $this->crmRoot().DIRECTORY_SEPARATOR.$companyId, 'root' => $this->crmRoot()],
            ['type' => 'directory', 'path' => $this->protectedCompanyRoot().DIRECTORY_SEPARATOR.$companyId, 'root' => $this->protectedCompanyRoot()],
        ];
        foreach (self::FILE_COLUMNS as $table => $columns) {
            foreach ($columns as $column) {
                foreach (DB::table($table)->where('company_id', $companyId)->pluck($column)->filter() as $storedPath) {
                    $path = str_replace('\\', '/', ltrim((string) $storedPath, '/\\'));
                    $prefix = 'companies/'.$companyId.'/';
                    if (! str_starts_with($path, $prefix)) {
                        if ($table === 'crm_attachments' && str_contains($path, '/'.$companyId.'/')) continue;
                        throw new RuntimeException("Ambiguous operational file ownership in {$table}.{$column} blocks Factory Reset.");
                    }
                    $manifest[] = [
                        'type' => 'file',
                        'path' => $this->publicCompanyRoot().DIRECTORY_SEPARATOR.$companyId.DIRECTORY_SEPARATOR.substr($path, strlen($prefix)),
                        'root' => $this->publicCompanyRoot().DIRECTORY_SEPARATOR.$companyId,
                    ];
                }
            }
        }
        foreach ($manifest as $item) $this->assertSafePath($item['path'], $item['root']);
        return $manifest;
    }

    private function cleanupFiles(CompanyFactoryResetAudit $audit, array $manifest): void
    {
        try {
            foreach ($manifest as $item) {
                $this->assertSafePath($item['path'], $item['root']);
                if (is_link($item['path'])) throw new RuntimeException('Symbolic-link cleanup is not allowed.');
                if ($item['type'] === 'directory' && File::isDirectory($item['path'])) File::deleteDirectory($item['path']);
                if ($item['type'] === 'file' && File::isFile($item['path'])) File::delete($item['path']);
            }
            $audit->update(['file_cleanup_state' => 'completed']);
        } catch (Throwable) {
            $audit->update(['file_cleanup_state' => 'failed', 'safe_error' => 'Verified Company operational file cleanup requires retry.']);
        }
    }

    private function assertSafePath(string $path, string $root): void
    {
        $path = str_replace('\\', '/', rtrim($path, '/\\'));
        $root = str_replace('\\', '/', rtrim($root, '/\\'));
        if ($path === $root || ! str_starts_with($path.'/', $root.'/') || str_contains($path, '/../')) {
            throw new RuntimeException('An unsafe Company file path blocked Factory Reset.');
        }
        $realPath = file_exists($path) ? realpath($path) : false;
        $realRoot = file_exists($root) ? realpath($root) : false;
        if ($realPath !== false && $realRoot !== false) {
            $realPath = str_replace('\\', '/', $realPath);
            $realRoot = str_replace('\\', '/', rtrim($realRoot, '/\\'));
            if (! str_starts_with($realPath.'/', $realRoot.'/')) {
                throw new RuntimeException('A symbolic-link escape blocked Company operational file cleanup.');
            }
        }
    }

    private function publicCompanyRoot(): string { return (string) config('dg-erp.destructive_files.public_company_root', public_path('companies')); }
    private function storageCompanyRoot(): string { return (string) config('dg-erp.destructive_files.storage_company_root', storage_path('app/public/companies')); }
    private function crmRoot(): string { return (string) config('dg-erp.destructive_files.crm_root', storage_path('app/crm')); }
    private function protectedCompanyRoot(): string { return \Illuminate\Support\Facades\Storage::disk('local')->path('protected/companies'); }
}
