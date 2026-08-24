<?php

namespace Tests\Feature;

use App\Models\Journal;
use App\Services\JournalService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class JournalOperationalAccountTest extends OpeningBalanceModuleTest
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::table('financial_years', fn (Blueprint $t) => [$t->boolean('is_closed')->default(false), $t->boolean('is_locked')->default(false)]);
        Schema::table('chart_accounts', fn (Blueprint $t) => $t->boolean('is_locked')->default(false));
        Schema::table('journals', function (Blueprint $t) {
            $t->string('journal_type')->nullable();
            $t->text('description')->nullable();
            $t->text('remarks')->nullable();
            $t->uuid('request_key')->nullable();
            foreach (['submitted_by', 'approved_by', 'rejected_by', 'locked_by', 'unlocked_by'] as $column) {
                $t->unsignedBigInteger($column)->nullable();
            }
            foreach (['submitted_at', 'approved_at', 'rejected_at', 'cancelled_at', 'locked_at', 'unlocked_at'] as $column) {
                $t->timestamp($column)->nullable();
            }
            foreach (['rejection_reason', 'cancellation_reason', 'reversal_reason', 'lock_reason', 'unlock_reason'] as $column) {
                $t->text($column)->nullable();
            }
            $t->boolean('is_locked')->default(false);
        });
        Schema::table('journal_items', function (Blueprint $t) {
            $t->decimal('debit', 20, 4)->default(0);
            $t->decimal('credit', 20, 4)->default(0);
            $t->text('description')->nullable();
            $t->string('reference')->nullable();
            $t->unsignedInteger('line_number')->nullable();
        });
        Schema::create('journal_number_sequences', fn (Blueprint $t) => [
            $t->unsignedBigInteger('company_id'),
            $t->unsignedBigInteger('financial_year_id'),
            $t->unsignedBigInteger('next_number'),
            $t->timestamps(),
            $t->primary(['company_id', 'financial_year_id']),
        ]);
        Schema::create('journal_audit_events', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('company_id');
            $t->unsignedBigInteger('financial_year_id');
            $t->unsignedBigInteger('journal_id');
            $t->string('event');
            $t->string('previous_status')->nullable();
            $t->string('new_status')->nullable();
            $t->unsignedBigInteger('actor_id');
            $t->timestamp('event_at');
            $t->text('reason')->nullable();
            $t->json('metadata')->nullable();
        });
        DB::table('chart_accounts')->insert([
            ['id' => 8, 'company_id' => 1, 'code' => '1120', 'name' => 'Bank', 'account_class' => 'asset', 'normal_balance' => 'debit', 'system_code' => 'BANK_ACCOUNTS', 'level' => 3, 'is_control' => 0, 'allow_manual_entry' => 1, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 9, 'company_id' => 1, 'code' => '1110', 'name' => 'Cash', 'account_class' => 'asset', 'normal_balance' => 'debit', 'system_code' => 'CASH_IN_HAND', 'level' => 3, 'is_control' => 0, 'allow_manual_entry' => 1, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('accounts')->updateOrInsert(['id' => 5], [
            'company_id' => 1,
            'account_name' => 'Petty Cash',
            'account_type' => 'Cash',
            'status' => 'active',
            'opening_balance' => 0,
            'current_balance' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_bank_and_cash_control_accounts_require_operational_account(): void
    {
        $service = app(JournalService::class);

        foreach ([
            [['chart_account_id' => 8, 'debit' => '10', 'credit' => '0'], ['chart_account_id' => 3, 'debit' => '0', 'credit' => '10']],
            [['chart_account_id' => 9, 'debit' => '10', 'credit' => '0'], ['chart_account_id' => 3, 'debit' => '0', 'credit' => '10']],
        ] as $lines) {
            try {
                $service->createDraft($this->payload($lines), 1, 1);
                $this->fail('Cash/Bank line without operational account was accepted.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('lines.0.account_id', $exception->errors());
            }
        }

        $journal = $service->createDraft($this->payload([
            ['chart_account_id' => 3, 'debit' => '10', 'credit' => '0'],
            ['chart_account_id' => 1, 'debit' => '0', 'credit' => '10'],
        ]), 1, 1);

        $this->assertSame(Journal::STATUS_DRAFT, $journal->status);
    }

    public function test_posting_creates_account_transactions_for_selected_operational_accounts(): void
    {
        $service = app(JournalService::class);
        DB::table('accounts')->where('id', 1)->update(['account_type' => 'Bank']);
        DB::table('accounts')->where('id', 5)->update(['account_type' => 'Cash']);

        $posted = $service->post($this->approved($service, [
            ['chart_account_id' => 9, 'account_id' => 5, 'debit' => '500', 'credit' => '0'],
            ['chart_account_id' => 8, 'account_id' => 1, 'debit' => '0', 'credit' => '500'],
        ]), 3);

        $posted->load('items');

        $this->assertSame(Journal::STATUS_POSTED, $posted->status);
        $this->assertSame(5, (int) $posted->items->firstWhere('chart_account_id', 9)?->account_id);
        $this->assertSame(1, (int) $posted->items->firstWhere('chart_account_id', 8)?->account_id);
        $this->assertSame(1, DB::table('account_transactions')->where('reference_id', $posted->id)->where('account_id', 1)->count());
        $this->assertSame(1, DB::table('account_transactions')->where('reference_id', $posted->id)->where('account_id', 5)->count());
    }

    private function approved(JournalService $service, array $lines): Journal
    {
        $journal = $service->createDraft($this->payload($lines), 1, 1);
        $journal = $service->submit($journal, 1);

        return $service->approve($journal, 2);
    }

    private function payload(array $lines): array
    {
        return [
            'financial_year_id' => 1,
            'journal_date' => '2026-06-15',
            'journal_type' => 'general',
            'reference_no' => 'REF',
            'description' => 'Operational account test',
            'remarks' => 'Test',
            'request_key' => (string) Str::uuid(),
            'lines' => $lines,
        ];
    }
}
