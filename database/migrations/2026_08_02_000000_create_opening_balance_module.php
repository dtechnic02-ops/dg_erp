<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->preflightLegacyOpeningBalances();

        Schema::table('financial_years', function (Blueprint $table) {
            $table->boolean('is_closed')->default(false)->after('is_active');
            $table->boolean('is_locked')->default(false)->after('is_closed');
        });
        Schema::table('chart_accounts', fn (Blueprint $table) => $table->boolean('is_locked')->default(false)->after('status'));

        Schema::create('accounting_period_locks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('financial_year_id')->constrained()->restrictOnDelete();
            $table->date('date_from');
            $table->date('date_to');
            $table->boolean('is_locked')->default(true);
            $table->text('reason');
            $table->foreignId('locked_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['company_id', 'financial_year_id', 'is_locked'], 'period_lock_company_fy_idx');
            $table->index(['company_id', 'date_from', 'date_to'], 'period_lock_company_dates_idx');
        });

        Schema::table('journals', function (Blueprint $table) {
            $table->string('source_module')->nullable()->after('reference_no');
            $table->string('source_type')->nullable()->after('source_module');
            $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
            $table->string('source_key')->nullable()->after('source_id');
            $table->unique(['company_id', 'source_key'], 'journals_company_source_unique');
        });

        Schema::table('journal_items', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id')->nullable()->change();
            $table->unsignedBigInteger('chart_account_id')->nullable()->after('account_id');
            $table->index('chart_account_id');
        });

        Schema::table('journals', fn (Blueprint $table) => $table->decimal('total_amount', 20, 4)->change());
        Schema::table('journal_items', fn (Blueprint $table) => $table->decimal('amount', 20, 4)->change());

        foreach (['account_transactions' => 'at', 'customer_transactions' => 'ct', 'supplier_transactions' => 'st'] as $tableName => $prefix) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName, $prefix) {
                $table->unique(['company_id', 'journal_item_id'], "{$prefix}_company_journal_unique");
                $table->unique('reversed_transaction_id', "{$prefix}_reversal_unique");
                $table->foreign('journal_item_id', "{$prefix}_journal_item_fk")->references('id')->on('journal_items')->restrictOnDelete();
                $table->foreign('reversed_transaction_id', "{$prefix}_reversal_fk")->references('id')->on($tableName)->restrictOnDelete();
            });
        }

        Schema::create('opening_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('financial_year_id')->constrained()->restrictOnDelete();
            $table->date('business_date');
            $table->string('type', 30);
            $table->string('reference_number');
            $table->text('remarks')->nullable();
            $table->string('status', 30)->default('draft');
            $table->string('active_key', 20)->nullable()->default('active');
            $table->uuid('request_key');
            $table->foreignId('journal_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('accounting_entry_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('locked_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->text('reversal_reason')->nullable();
            $table->text('lock_reason')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->timestamps();

            $table->unique(['company_id', 'request_key'], 'ob_company_request_unique');
            $table->unique(['company_id', 'financial_year_id', 'active_key'], 'ob_company_fy_active_unique');
            $table->unique(['company_id', 'financial_year_id', 'reference_number'], 'ob_company_fy_reference_unique');
            $table->unique('journal_id', 'ob_journal_unique');
            $table->unique('accounting_entry_id', 'ob_accounting_entry_unique');
            $table->index(['company_id', 'financial_year_id', 'status'], 'ob_company_fy_status_idx');
            $table->index(['company_id', 'business_date'], 'ob_company_date_idx');
        });

        Schema::create('opening_balance_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opening_balance_id')->constrained()->cascadeOnDelete();
            $table->foreignId('chart_account_id')->constrained('chart_accounts')->restrictOnDelete();
            $table->foreignId('operational_account_id')->nullable()->constrained('accounts')->restrictOnDelete();
            $table->unsignedInteger('line_number');
            $table->decimal('debit', 20, 4)->default(0);
            $table->decimal('credit', 20, 4)->default(0);
            $table->string('subledger_type', 30)->nullable();
            $table->unsignedBigInteger('subledger_id')->nullable();
            $table->text('description')->nullable();
            $table->string('line_reference')->nullable();
            $table->string('currency', 3)->nullable();
            $table->decimal('exchange_rate', 20, 8)->nullable();
            $table->decimal('base_debit', 20, 4)->default(0);
            $table->decimal('base_credit', 20, 4)->default(0);
            $table->timestamps();
            $table->unique(['opening_balance_id', 'line_number'], 'ob_line_number_unique');
            $table->index(['subledger_type', 'subledger_id'], 'ob_line_subledger_idx');
        });

        Schema::create('opening_balance_audit_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('financial_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('opening_balance_id')->constrained()->restrictOnDelete();
            $table->string('event', 40);
            $table->string('previous_status', 30)->nullable();
            $table->string('new_status', 30)->nullable();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();
            $table->index(['company_id', 'financial_year_id', 'occurred_at'], 'ob_audit_company_fy_idx');
        });

        Schema::create('opening_balance_legacy_records', function (Blueprint $table) {
            $table->id();
            $table->string('source_table');
            $table->unsignedBigInteger('source_id');
            $table->unsignedBigInteger('company_id');
            $table->decimal('stored_amount', 20, 4);
            $table->string('classification', 30);
            $table->text('evidence');
            $table->timestamps();
            $table->unique(['source_table', 'source_id'], 'ob_legacy_source_unique');
            $table->index(['company_id', 'classification'], 'ob_legacy_company_class_idx');
        });

        $this->classifyLegacyOpeningBalances();
    }

    public function down(): void
    {
        if (Schema::hasTable('opening_balances') && DB::table('opening_balances')->whereNotIn('status', ['draft', 'cancelled'])->exists()) {
            throw new \RuntimeException('Cannot rollback the Opening Balance module while submitted, approved, posted, or reversed records exist.');
        }
        if (DB::table('financial_years')->where('is_closed', 1)->orWhere('is_locked', 1)->exists()
            || DB::table('chart_accounts')->where('is_locked', 1)->exists()) {
            throw new \RuntimeException('Cannot rollback Opening Balance financial controls while a Financial Year is closed or locked.');
        }
        Schema::dropIfExists('opening_balance_legacy_records');
        Schema::dropIfExists('opening_balance_audit_events');
        Schema::dropIfExists('opening_balance_lines');
        Schema::dropIfExists('opening_balances');
        Schema::dropIfExists('accounting_period_locks');
        foreach (['account_transactions' => 'at', 'customer_transactions' => 'ct', 'supplier_transactions' => 'st'] as $tableName => $prefix) {
            Schema::table($tableName, function (Blueprint $table) use ($prefix) {
                $table->dropForeign("{$prefix}_journal_item_fk");
                $table->dropForeign("{$prefix}_reversal_fk");
                $table->dropUnique("{$prefix}_company_journal_unique");
                $table->dropUnique("{$prefix}_reversal_unique");
            });
        }
        Schema::table('journal_items', function (Blueprint $table) {
            $table->dropIndex(['chart_account_id']);
            $table->dropColumn('chart_account_id');
            $table->unsignedBigInteger('account_id')->nullable(false)->change();
        });
        Schema::table('journals', fn (Blueprint $table) => $table->decimal('total_amount', 18, 2)->change());
        Schema::table('journal_items', fn (Blueprint $table) => $table->decimal('amount', 18, 2)->change());
        Schema::table('journals', function (Blueprint $table) {
            $table->dropUnique('journals_company_source_unique');
            $table->dropColumn(['source_module', 'source_type', 'source_id', 'source_key']);
        });
        Schema::table('financial_years', fn (Blueprint $table) => $table->dropColumn(['is_closed', 'is_locked']));
        Schema::table('chart_accounts', fn (Blueprint $table) => $table->dropColumn('is_locked'));
    }

    private function preflightLegacyOpeningBalances(): void
    {
        foreach (['customers', 'suppliers', 'accounts'] as $table) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'opening_balance')) {
                throw new \RuntimeException("Cannot classify legacy {$table}: opening_balance is missing.");
            }
        }
        if (Schema::hasTable('opening_balances')) {
            throw new \RuntimeException('Opening Balance schema already exists outside migration control.');
        }
        foreach (['account_transactions', 'customer_transactions', 'supplier_transactions'] as $table) {
            if (! Schema::hasTable($table)) continue;
            $duplicateJournal = DB::table($table)->select('company_id', 'journal_item_id', DB::raw('COUNT(*) AS aggregate'))
                ->whereNotNull('journal_item_id')->groupBy('company_id', 'journal_item_id')->havingRaw('COUNT(*) > 1')->first();
            $duplicateReversal = DB::table($table)->select('reversed_transaction_id', DB::raw('COUNT(*) AS aggregate'))
                ->whereNotNull('reversed_transaction_id')->groupBy('reversed_transaction_id')->havingRaw('COUNT(*) > 1')->first();
            $orphanJournal = DB::table($table)->whereNotNull('journal_item_id')->whereNotExists(fn ($q) => $q->selectRaw('1')->from('journal_items')->whereColumn('journal_items.id', "{$table}.journal_item_id"))->exists();
            $orphanReversal = DB::table($table . ' as source')->whereNotNull('source.reversed_transaction_id')->whereNotExists(fn ($q) => $q->selectRaw('1')->from($table . ' as original')->whereColumn('original.id', 'source.reversed_transaction_id'))->exists();
            if ($duplicateJournal || $duplicateReversal || $orphanJournal || $orphanReversal) {
                throw new \RuntimeException("Cannot enforce Opening Balance source integrity on {$table}: duplicate or orphan Journal/reversal linkage requires manual reconciliation.");
            }
        }
    }

    private function classifyLegacyOpeningBalances(): void
    {
        foreach (['customers' => 'customer', 'suppliers' => 'supplier', 'accounts' => 'account'] as $table => $type) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            DB::table($table)->where('opening_balance', '!=', 0)->orderBy('id')->chunkById(100, function ($rows) use ($table, $type) {
                foreach ($rows as $row) {
                    [$classification, $evidence] = $this->classifyLegacyRow($type, $row);
                    DB::table('opening_balance_legacy_records')->insert([
                        'source_table' => $table,
                        'source_id' => $row->id,
                        'company_id' => $row->company_id,
                        'stored_amount' => $row->opening_balance,
                        'classification' => $classification,
                        'evidence' => $evidence,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            });
        }
    }

    private function classifyLegacyRow(string $type, object $row): array
    {
        if ($type === 'account') {
            $transactions = DB::table('account_transactions')->where('company_id', $row->company_id)
                ->where('account_id', $row->id)->where('reference_type', 'opening_balance')->count();
            return $transactions === 0
                ? ['unaccounted', 'No opening-balance AccountTransaction or official AccountingEntry was found.']
                : ['ambiguous', "{$transactions} AccountTransaction record(s) exist but legacy Cash/Bank opening balances have no balanced official accounting source."];
        }
        $transactionsTable = $type . '_transactions';
        $partyColumn = $type . '_id';
        $sourceType = $type . '_opening_balance';
        $transactions = DB::table($transactionsTable)->where('company_id', $row->company_id)
            ->where($partyColumn, $row->id)->where('reference_type', 'opening_balance')->where('status', 1)->count();
        $entries = DB::table('accounting_entries')->where('company_id', $row->company_id)
            ->where('source_type', $sourceType)->where('source_id', $row->id)->count();
        if ($transactions === 1 && $entries === 1) {
            return ['already_accounted', 'Exactly one active subledger transaction and one source-linked AccountingEntry exist.'];
        }
        if ($transactions === 0 && $entries === 0) {
            return ['unaccounted', 'No subledger transaction or AccountingEntry exists; no posting was inferred.'];
        }
        return ['ambiguous', "Found {$transactions} active subledger transaction(s) and {$entries} AccountingEntry record(s); manual reconciliation is required."];
    }
};
