<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->preflight();

        Schema::table('journals', function (Blueprint $table) {
            $table->dropForeign(['financial_year_id']);
        });
        Schema::table('journals', function (Blueprint $table) {
            $table->unsignedBigInteger('financial_year_id')->nullable(false)->change();
            $table->string('status', 20)->default('draft')->change();
            $table->foreign('company_id')->references('id')->on('companies')->restrictOnDelete();
            $table->foreign('financial_year_id')->references('id')->on('financial_years')->restrictOnDelete();
        });

        Schema::table('journals', function (Blueprint $table) {
            $table->string('journal_type', 30)->nullable()->after('journal_date');
            $table->text('description')->nullable()->after('reference_no');
            $table->text('remarks')->nullable()->after('description');
            $table->uuid('request_key')->nullable()->after('source_key');
            $table->foreignId('submitted_by')->nullable()->after('updated_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('submitted_at')->nullable()->after('submitted_by');
            $table->foreignId('approved_by')->nullable()->after('submitted_at')->constrained('users')->restrictOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->foreignId('rejected_by')->nullable()->after('approved_at')->constrained('users')->restrictOnDelete();
            $table->timestamp('rejected_at')->nullable()->after('rejected_by');
            $table->text('rejection_reason')->nullable()->after('rejected_at');
            $table->timestamp('cancelled_at')->nullable()->after('cancelled_date');
            $table->text('cancellation_reason')->nullable()->after('cancel_reason');
            $table->text('reversal_reason')->nullable()->after('reversed_at');
            $table->boolean('is_locked')->default(false)->after('reversal_of_journal_id');
            $table->foreignId('locked_by')->nullable()->after('is_locked')->constrained('users')->restrictOnDelete();
            $table->timestamp('locked_at')->nullable()->after('locked_by');
            $table->text('lock_reason')->nullable()->after('locked_at');
            $table->foreignId('unlocked_by')->nullable()->after('lock_reason')->constrained('users')->restrictOnDelete();
            $table->timestamp('unlocked_at')->nullable()->after('unlocked_by');
            $table->text('unlock_reason')->nullable()->after('unlocked_at');
            $table->string('legacy_classification', 20)->nullable()->after('unlock_reason');
            $table->text('legacy_classification_reason')->nullable()->after('legacy_classification');
            $table->unique(['company_id', 'financial_year_id', 'journal_no'], 'journals_company_fy_number_unique');
            $table->unique(['company_id', 'request_key'], 'journals_company_request_unique');
            $table->unique('reversal_of_journal_id', 'journals_one_reversal_unique');
            $table->index(['company_id', 'financial_year_id', 'journal_date', 'status'], 'journals_company_fy_date_status_idx');
        });

        DB::statement("UPDATE journals SET status = CASE status WHEN '0' THEN 'cancelled' WHEN '1' THEN 'posted' WHEN '2' THEN 'reversed' ELSE status END");
        DB::table('journals')->whereNull('journal_type')->update(['journal_type' => DB::raw("CASE WHEN source_module = 'opening_balance' THEN 'opening' ELSE 'general' END")]);

        Schema::table('journal_items', function (Blueprint $table) {
            $table->dropForeign(['journal_id']);
            $table->dropForeign(['account_id']);
        });
        Schema::table('journal_items', function (Blueprint $table) {
            $table->decimal('debit', 20, 4)->default(0)->after('amount');
            $table->decimal('credit', 20, 4)->default(0)->after('debit');
            $table->text('description')->nullable()->after('credit');
            $table->string('reference')->nullable()->after('description');
            $table->unsignedInteger('line_number')->nullable()->after('reference');
            $table->foreign('chart_account_id', 'journal_items_chart_account_fk')->references('id')->on('chart_accounts')->restrictOnDelete();
            $table->foreign('journal_id')->references('id')->on('journals')->restrictOnDelete();
            $table->foreign('account_id')->references('id')->on('accounts')->restrictOnDelete();
            $table->index(['journal_id', 'line_number'], 'journal_items_journal_line_idx');
        });
        DB::table('journal_items')->where('type', 'debit')->update(['debit' => DB::raw('amount')]);
        DB::table('journal_items')->where('type', 'credit')->update(['credit' => DB::raw('amount')]);

        Schema::create('journal_number_sequences', function (Blueprint $table) {
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('financial_year_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('next_number')->default(1);
            $table->timestamps();
            $table->primary(['company_id', 'financial_year_id']);
        });

        Schema::create('journal_audit_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('financial_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('journal_id')->constrained()->restrictOnDelete();
            $table->string('event', 40);
            $table->string('previous_status', 20)->nullable();
            $table->string('new_status', 20)->nullable();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('event_at');
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->index(['company_id', 'financial_year_id', 'event_at'], 'journal_audit_company_fy_date_idx');
        });

        $this->classifyLegacy();
    }

    public function down(): void
    {
        if (DB::table('journals')->whereNotNull('request_key')->orWhere('status', 'draft')->exists()
            || DB::table('journal_audit_events')->exists()) {
            throw new \RuntimeException('Journal Phase 1 rollback is unsafe because Phase 1 Journal or audit data exists.');
        }

        Schema::dropIfExists('journal_audit_events');
        Schema::dropIfExists('journal_number_sequences');
        Schema::table('journal_items', function (Blueprint $table) {
            $table->dropForeign('journal_items_chart_account_fk');
            $table->dropForeign(['journal_id']);
            $table->dropForeign(['account_id']);
        });
        Schema::table('journal_items', function (Blueprint $table) {
            $table->dropIndex('journal_items_journal_line_idx');
            $table->dropColumn(['debit', 'credit', 'description', 'reference', 'line_number']);
        });
        Schema::table('journal_items', function (Blueprint $table) {
            $table->foreign('journal_id')->references('id')->on('journals')->cascadeOnDelete();
            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
        });
        Schema::table('journals', function (Blueprint $table) {
            $table->dropUnique('journals_company_fy_number_unique');
            $table->dropUnique('journals_company_request_unique');
            $table->dropUnique('journals_one_reversal_unique');
            $table->dropIndex('journals_company_fy_date_status_idx');
            $table->dropConstrainedForeignId('submitted_by');
            $table->dropConstrainedForeignId('approved_by');
            $table->dropConstrainedForeignId('rejected_by');
            $table->dropConstrainedForeignId('locked_by');
            $table->dropConstrainedForeignId('unlocked_by');
            $table->dropColumn(['journal_type', 'description', 'remarks', 'request_key', 'submitted_at', 'approved_at', 'rejected_at', 'rejection_reason', 'cancelled_at', 'cancellation_reason', 'reversal_reason', 'is_locked', 'locked_at', 'lock_reason', 'unlocked_at', 'unlock_reason', 'legacy_classification', 'legacy_classification_reason']);
            $table->dropForeign(['company_id']);
            $table->dropForeign(['financial_year_id']);
        });
        DB::table('journals')->where('status', 'cancelled')->update(['status' => '0']);
        DB::table('journals')->where('status', 'posted')->update(['status' => '1']);
        DB::table('journals')->where('status', 'reversed')->update(['status' => '2']);
        Schema::table('journals', function (Blueprint $table) {
            $table->unsignedBigInteger('financial_year_id')->nullable()->change();
            $table->tinyInteger('status')->default(1)->change();
            $table->foreign('financial_year_id')->references('id')->on('financial_years')->nullOnDelete();
        });
    }

    private function preflight(): void
    {
        $ambiguousMapping = DB::table('journal_items as ji')
            ->join('journals as j', 'j.id', '=', 'ji.journal_id')
            ->whereNull('ji.chart_account_id')
            ->where(function ($query) {
                $query->whereNull('ji.account_id')->orWhereRaw('(SELECT COUNT(DISTINCT ael.chart_account_id) FROM accounting_entry_lines ael JOIN accounting_entries ae ON ae.id = ael.accounting_entry_id WHERE ael.operational_account_id = ji.account_id AND ae.company_id = j.company_id) <> 1');
            })->exists();
        $checks = [
            'orphan company' => DB::table('journals')->leftJoin('companies', 'companies.id', '=', 'journals.company_id')->whereNull('companies.id')->exists(),
            'missing or orphan Financial Year' => DB::table('journals')->leftJoin('financial_years', 'financial_years.id', '=', 'journals.financial_year_id')->whereNull('financial_years.id')->exists(),
            'cross-company Financial Year' => DB::table('journals')->join('financial_years', 'financial_years.id', '=', 'journals.financial_year_id')->whereColumn('journals.company_id', '!=', 'financial_years.company_id')->exists(),
            'duplicate Journal number' => DB::table('journals')->select('company_id', 'financial_year_id', 'journal_no')->groupBy('company_id', 'financial_year_id', 'journal_no')->havingRaw('COUNT(*) > 1')->exists(),
            'multiple reversals' => DB::table('journals')->whereNotNull('reversal_of_journal_id')->groupBy('reversal_of_journal_id')->havingRaw('COUNT(*) > 1')->exists(),
            'orphan Chart Account line' => DB::table('journal_items')->whereNotNull('chart_account_id')->whereNotExists(fn ($q) => $q->selectRaw('1')->from('chart_accounts')->whereColumn('chart_accounts.id', 'journal_items.chart_account_id'))->exists(),
            'orphan Journal item' => DB::table('journal_items')->whereNotExists(fn ($q) => $q->selectRaw('1')->from('journals')->whereColumn('journals.id', 'journal_items.journal_id'))->exists(),
            'orphan operational Account line' => DB::table('journal_items')->whereNotNull('account_id')->whereNotExists(fn ($q) => $q->selectRaw('1')->from('accounts')->whereColumn('accounts.id', 'journal_items.account_id'))->exists(),
            'cross-company Journal item' => DB::table('journal_items')->join('journals', 'journals.id', '=', 'journal_items.journal_id')->whereColumn('journal_items.company_id', '!=', 'journals.company_id')->exists(),
            'ambiguous Chart Account mapping' => $ambiguousMapping,
        ];
        foreach ($checks as $label => $failed) {
            if ($failed) throw new \RuntimeException("Journal Phase 1 preflight failed: {$label}. Reconcile legacy data before migration.");
        }
    }

    private function classifyLegacy(): void
    {
        foreach (DB::table('journals')->orderBy('id')->cursor() as $journal) {
            $items = DB::table('journal_items')->where('journal_id', $journal->id)->get();
            $balanced = $items->isNotEmpty() && DB::table('journal_items')->where('journal_id', $journal->id)
                ->havingRaw("SUM(CASE WHEN type = 'debit' THEN amount ELSE 0 END) = SUM(CASE WHEN type = 'credit' THEN amount ELSE 0 END)")
                ->exists();
            $allMapped = $items->isNotEmpty() && $items->every(fn ($line) => $line->chart_account_id !== null);
            $hasAccount = $items->every(fn ($line) => $line->account_id === null || DB::table('accounts')->where('id', $line->account_id)->where('company_id', $journal->company_id)->exists());
            $hasSourceIdentity = filled($journal->source_module) && filled($journal->source_type) && filled($journal->source_id) && filled($journal->source_key);
            $accountingExists = $hasSourceIdentity && DB::table('accounting_entries')->where('company_id', $journal->company_id)
                ->where('source_key', $journal->source_key)->where('source_module', $journal->source_module)
                ->where('source_type', $journal->source_type)->where('source_id', $journal->source_id)->exists();
            $classification = !$hasAccount || !$balanced ? 'unsafe' : ($allMapped && $accountingExists ? 'compatible' : ($allMapped && $hasSourceIdentity ? 'mappable' : 'ambiguous'));
            DB::table('journals')->where('id', $journal->id)->update([
                'legacy_classification' => $classification,
                'legacy_classification_reason' => match ($classification) {
                    'compatible' => 'Balanced, Chart Accounts mapped, and source Accounting Entry found.',
                    'mappable' => 'Balanced and Chart Accounts mapped; no source Accounting Entry was found.',
                    'ambiguous' => 'Balanced legacy history exists but one or more Chart Account mappings are unresolved.',
                    default => 'Legacy Journal is unbalanced or contains an invalid company Account reference.',
                },
            ]);
        }
    }
};
