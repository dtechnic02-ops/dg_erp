<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyDeletionAudit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

class CompanyPermanentDeletionService
{
    /** Every pre-feature application table that directly carries company_id. */
    public const COMPANY_TABLES = [
        'account_transactions', 'accounting_entries', 'accounting_period_locks', 'accounts',
        'brands', 'cash_accounts', 'chart_accounts', 'company_permission', 'company_subscriptions',
        'company_whatsapp_settings', 'contras', 'crm_attachments', 'crm_configurations',
        'company_factory_reset_audits',
        'crm_contacts', 'crm_follow_ups', 'crm_leads', 'crm_meetings', 'crm_notes',
        'crm_opportunities', 'crm_status_histories', 'crm_tasks', 'customer_transactions',
        'customers', 'delivery_attachments', 'delivery_note_items', 'delivery_notes',
        'delivery_signatures', 'delivery_status_histories', 'employee_accounts', 'employee_payments',
        'expense_categories', 'expenses', 'financial_years', 'income_categories', 'incomes',
        'inventory_valuations', 'invoice_payments', 'journal_audit_events', 'journal_items',
        'journal_number_sequences', 'journals', 'loan_accounts', 'loan_integrity_seeded_chart_accounts',
        'loan_payments', 'loan_saving_ledgers', 'opening_balance_audit_events',
        'opening_balance_legacy_records', 'opening_balances', 'party_accounts', 'product_categories',
        'products', 'purchase_invoices', 'purchase_items', 'purchase_payments',
        'purchase_return_items', 'purchase_return_refund_adjustments', 'purchase_return_refunds',
        'purchase_returns', 'quotation_items', 'quotations', 'salary_sheets', 'sales_cost_snapshots',
        'sales_invoices', 'sales_items', 'sales_payments', 'sales_return_items',
        'sales_return_refund_adjustments', 'sales_return_refunds', 'sales_returns',
        'service_categories', 'services', 'stock_movements', 'stock_transactions',
        'subscription_histories', 'subscription_payments', 'suppliers', 'supplier_transactions',
        'units', 'users', 'vats',
    ];

    /** Child-first order from the approved dependency audit. */
    private const DELETE_ORDER = [
        'sales_return_refund_adjustments', 'purchase_return_refund_adjustments',
        'sales_cost_snapshots', 'inventory_valuations',
        'delivery_attachments', 'delivery_signatures', 'delivery_status_histories', 'delivery_note_items',
        'crm_attachments', 'crm_notes', 'crm_tasks', 'crm_follow_ups', 'crm_meetings',
        'crm_status_histories', 'crm_opportunities', 'crm_contacts', 'crm_leads',
        'opening_balance_audit_events', 'journal_audit_events',
        'sales_return_refunds', 'purchase_return_refunds', 'sales_return_items', 'purchase_return_items',
        'sales_payments', 'purchase_payments', 'invoice_payments',
        'stock_transactions', 'stock_movements', 'sales_items', 'purchase_items', 'quotation_items',
        'loan_saving_ledgers', 'loan_payments', 'employee_payments', 'salary_sheets',
        'customer_transactions', 'supplier_transactions', 'account_transactions',
        'sales_returns', 'purchase_returns', 'delivery_notes', 'quotations',
        'sales_invoices', 'purchase_invoices', 'journal_items', 'journals', 'opening_balances', 'accounting_entries',
        'loan_accounts', 'expenses', 'incomes',
        'crm_configurations', 'employee_accounts', 'party_accounts',
        'customers', 'suppliers', 'products', 'services', 'product_categories',
        'service_categories', 'brands', 'units', 'vats',
        'expense_categories', 'income_categories', 'contras', 'cash_accounts', 'accounts',
        'accounting_period_locks', 'journal_number_sequences', 'opening_balance_legacy_records',
        'loan_integrity_seeded_chart_accounts', 'financial_years', 'chart_accounts',
        'subscription_histories', 'subscription_payments', 'company_subscriptions',
        'company_factory_reset_audits', 'company_whatsapp_settings', 'company_permission',
    ];

    public function delete(Company $company, int $actorId, ?int $challengeId = null): CompanyDeletionAudit
    {
        $this->assertInventoryComplete();
        $this->assertQueueIsSafe();
        $manifest = $this->buildFileManifest($company);
        $requestedAt = now();

        $audit = DB::transaction(function () use ($company, $actorId, $challengeId, $requestedAt, $manifest): CompanyDeletionAudit {
            $locked = Company::query()->lockForUpdate()->find($company->id);
            if (! $locked) {
                throw new RuntimeException('The target Company no longer exists.');
            }

            $userRows = DB::table('users')->where('company_id', $locked->id)->lockForUpdate()->get(['id', 'email', 'role_id']);
            $userIds = $userRows->pluck('id')->map(fn ($id) => (int) $id)->all();
            $emails = $userRows->pluck('email')->filter()->unique()->values()->all();
            $counts = [];

            $this->deleteAuthenticationResidue($userIds, $emails, $counts);
            $this->deleteIndirectChildren($locked->id, $counts);

            foreach (self::DELETE_ORDER as $table) {
                $counts[$table] = DB::table($table)->where('company_id', $locked->id)->delete();
            }

            $counts['users'] = DB::table('users')->where('company_id', $locked->id)->delete();
            $this->assertNoTenantRowsRemain($locked->id);

            $counts['companies'] = 1;
            $audit = CompanyDeletionAudit::create([
                'deleted_company_id' => $locked->id,
                'deleted_company_name' => $locked->company_name,
                'requested_by' => $actorId,
                'requested_at' => $requestedAt,
                'completed_at' => now(),
                'result' => 'deleted',
                'deleted_counts' => $counts,
                'file_manifest' => $manifest,
                'file_cleanup_state' => 'pending',
            ]);

            if ($challengeId !== null) {
                DB::table('company_destructive_challenges')->where('id', $challengeId)->delete();
            }
            DB::table('company_destructive_challenges')->where('company_id', $locked->id)->delete();
            if (DB::table('companies')->where('id', $locked->id)->delete() !== 1) {
                throw new RuntimeException('The target Company could not be deleted safely.');
            }

            return $audit;
        }, 3);

        $this->cleanupFiles($audit, $manifest);
        return $audit->fresh();
    }

    public function retryFileCleanup(CompanyDeletionAudit $audit): CompanyDeletionAudit
    {
        if ($audit->file_cleanup_state !== 'failed' || ! is_array($audit->file_manifest)) {
            throw new RuntimeException('No failed Company file cleanup is available for retry.');
        }
        $this->cleanupFiles($audit, $audit->file_manifest);
        return $audit->fresh();
    }

    private function deleteAuthenticationResidue(array $userIds, array $emails, array &$counts): void
    {
        if ($userIds === []) {
            return;
        }

        $counts['sessions'] = DB::table('sessions')->whereIn('user_id', $userIds)->delete();
        $counts['user_permissions'] = DB::table('user_permissions')->whereIn('user_id', $userIds)->delete();
        $counts['password_reset_tokens'] = $emails === [] ? 0 : DB::table('password_reset_tokens')->whereIn('email', $emails)->delete();
        $counts['password_reset_requests'] = DB::table('password_reset_requests')
            ->where(function ($query) use ($userIds, $emails): void {
                $query->whereIn('user_id', $userIds)->orWhereIn('initiated_by', $userIds);
                if ($emails !== []) {
                    $query->orWhereIn('user_email', $emails)->orWhereIn('initiated_by_email', $emails);
                }
            })->delete();
    }

    private function deleteIndirectChildren(int $companyId, array &$counts): void
    {
        $entryIds = DB::table('accounting_entries')->where('company_id', $companyId)->pluck('id');
        $counts['accounting_entry_lines'] = $entryIds->isEmpty() ? 0 : DB::table('accounting_entry_lines')->whereIn('accounting_entry_id', $entryIds)->delete();

        $openingIds = DB::table('opening_balances')->where('company_id', $companyId)->pluck('id');
        $counts['opening_balance_lines'] = $openingIds->isEmpty() ? 0 : DB::table('opening_balance_lines')->whereIn('opening_balance_id', $openingIds)->delete();
    }

    private function assertNoTenantRowsRemain(int $companyId, array $except = []): void
    {
        foreach (array_diff(self::COMPANY_TABLES, $except) as $table) {
            if (DB::table($table)->where('company_id', $companyId)->exists()) {
                throw new RuntimeException("Company-owned rows remain in {$table}; deletion was rolled back.");
            }
        }
    }

    private function assertInventoryComplete(): void
    {
        $known = array_merge(self::COMPANY_TABLES, ['company_destructive_challenges']);
        $unknown = [];
        foreach (Schema::getTables() as $tableInfo) {
            $table = $tableInfo['name'];
            if (in_array('company_id', Schema::getColumnListing($table), true) && ! in_array($table, $known, true)) {
                $unknown[] = $table;
            }
        }
        if ($unknown !== []) {
            throw new RuntimeException('Unknown Company-owned tables block deletion: '.implode(', ', $unknown));
        }
    }

    private function assertQueueIsSafe(): void
    {
        if (Schema::hasTable('jobs') && DB::table('jobs')->exists()) {
            throw new RuntimeException('Pending queued-job ownership cannot be proven. Company deletion is blocked safely.');
        }
    }

    private function buildFileManifest(Company $company): array
    {
        $manifest = [
            ['type' => 'directory', 'path' => $this->publicCompanyRoot().DIRECTORY_SEPARATOR.$company->id, 'root' => $this->publicCompanyRoot()],
            ['type' => 'directory', 'path' => $this->storageCompanyRoot().DIRECTORY_SEPARATOR.$company->id, 'root' => $this->storageCompanyRoot()],
            ['type' => 'directory', 'path' => $this->crmRoot().DIRECTORY_SEPARATOR.$company->id, 'root' => $this->crmRoot()],
        ];

        foreach (DB::table('subscription_payments')->where('company_id', $company->id)->pluck('proof_path')->filter() as $path) {
            $manifest[] = ['type' => 'file', 'path' => $this->storagePublicRoot().DIRECTORY_SEPARATOR.ltrim((string) $path, '/\\'), 'root' => $this->storagePublicRoot()];
        }

        foreach ($manifest as $item) {
            $this->assertSafePath($item['path'], $item['root']);
        }
        return $manifest;
    }

    private function cleanupFiles(CompanyDeletionAudit $audit, array $manifest): void
    {
        try {
            foreach ($manifest as $item) {
                $this->assertSafePath($item['path'], $item['root']);
                if (is_link($item['path'])) {
                    throw new RuntimeException('A symbolic-link path blocked safe Company file cleanup.');
                }
                if ($item['type'] === 'directory' && File::isDirectory($item['path'])) {
                    File::deleteDirectory($item['path']);
                } elseif ($item['type'] === 'file' && File::isFile($item['path'])) {
                    File::delete($item['path']);
                }
            }
            $audit->update(['file_cleanup_state' => 'completed']);
        } catch (Throwable $e) {
            $audit->update([
                'file_cleanup_state' => 'failed',
                'safe_error' => 'Verified Company file cleanup requires retry.',
            ]);
        }
    }

    private function assertSafePath(string $path, string $root): void
    {
        $normalPath = str_replace('\\', '/', rtrim($path, '/\\'));
        $normalRoot = str_replace('\\', '/', rtrim($root, '/\\'));
        if ($normalPath === $normalRoot || ! str_starts_with($normalPath.'/', $normalRoot.'/') || str_contains($normalPath, '/../')) {
            throw new RuntimeException('An unsafe Company file path blocked destructive execution.');
        }
        $realPath = file_exists($path) ? realpath($path) : false;
        $realRoot = file_exists($root) ? realpath($root) : false;
        if ($realPath !== false && $realRoot !== false) {
            $realPath = str_replace('\\', '/', $realPath);
            $realRoot = str_replace('\\', '/', rtrim($realRoot, '/\\'));
            if (! str_starts_with($realPath.'/', $realRoot.'/')) {
                throw new RuntimeException('A symbolic-link escape blocked Company file cleanup.');
            }
        }
    }

    private function publicCompanyRoot(): string
    {
        return (string) config('dg-erp.destructive_files.public_company_root', public_path('companies'));
    }

    private function storageCompanyRoot(): string
    {
        return (string) config('dg-erp.destructive_files.storage_company_root', storage_path('app/public/companies'));
    }

    private function crmRoot(): string
    {
        return (string) config('dg-erp.destructive_files.crm_root', storage_path('app/crm'));
    }

    private function storagePublicRoot(): string
    {
        return (string) config('dg-erp.destructive_files.storage_public_root', storage_path('app/public'));
    }
}
