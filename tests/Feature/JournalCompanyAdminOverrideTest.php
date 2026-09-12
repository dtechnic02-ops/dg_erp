<?php

namespace Tests\Feature;

use App\Models\AccountingEntry;
use App\Models\Journal;
use App\Models\ChartAccount;
use App\Models\JournalAuditEvent;
use App\Models\User;
use App\Services\JournalService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class JournalCompanyAdminOverrideTest extends OpeningBalanceModuleTest
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
        ChartAccount::create([
    'id' => 10,
    'company_id' => 1,
    'code' => '1195',
    'name' => 'Journal Test Account',
    'account_class' => 'asset',
    'normal_balance' => 'debit',
    'level' => 3,
    'is_control' => 0,
    'allow_manual_entry' => 1,
    'status' => 'active',
]);

    }


    public function test_company_staff_cannot_self_approve_submitted_journal(): void
    {
        $service = $this->journalService();
        $staffId = 3;
        $journal = $service->createDraft($this->journalPayload($this->journalLines()), 1, $staffId);
        $journal = $service->submit($journal, $staffId);

        try {
            $service->approve($journal, $staffId);
            $this->fail('Company Staff self-approval was accepted.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('self-approval', strtolower($e->getMessage()));
            $this->assertSame(Journal::STATUS_SUBMITTED, $journal->fresh()->status);
        }
    }

    public function test_company_staff_cannot_post_journal_they_created(): void
    {
        $service = $this->journalService();
        $staffId = 3;
        $journal = $service->createDraft($this->journalPayload($this->journalLines()), 1, $staffId);
        $journal = $service->submit($journal, 1);
        $journal = $service->approve($journal, 1);

        try {
            $service->post($journal, $staffId);
            $this->fail('Company Staff self-post was accepted.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('segregation', strtolower($e->getMessage()));
            $this->assertSame(Journal::STATUS_APPROVED, $journal->fresh()->status);
        }
    }

    public function test_company_staff_cannot_post_journal_they_approved(): void
    {
        $service = $this->journalService();
        $staffId = 3;
        $journal = $service->createDraft($this->journalPayload($this->journalLines()), 1, 1);
        $journal = $service->submit($journal, 1);
        $journal = $service->approve($journal, $staffId);

        try {
            $service->post($journal, $staffId);
            $this->fail('Company Staff post after self-approval was accepted.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('segregation', strtolower($e->getMessage()));
            $this->assertSame(Journal::STATUS_APPROVED, $journal->fresh()->status);
        }
    }

    public function test_company_admin_may_self_approve_and_records_override_audit(): void
    {
        $service = $this->journalService();
        $adminId = 1;
        $journal = $service->createDraft($this->journalPayload($this->journalLines()), 1, $adminId);
        $journal = $service->submit($journal, $adminId);
        $approved = $service->approve($journal, $adminId);

        $this->assertSame(Journal::STATUS_APPROVED, $approved->status);
        $this->assertSame($adminId, (int) $approved->approved_by);
        $this->assertSame($adminId, (int) $approved->created_by);
        $this->assertSame($adminId, (int) $approved->submitted_by);

        $audit = JournalAuditEvent::where('journal_id', $approved->id)->where('event', 'approved')->first();
        $this->assertSame($adminId, (int) $audit->actor_id);
        $this->assertTrue((bool) ($audit->metadata['admin_override'] ?? false));
        $this->assertSame('self_approval', $audit->metadata['override_type'] ?? null);
    }

    public function test_company_admin_may_self_post_full_workflow_with_accounting_and_audit(): void
    {
        $service = $this->journalService();
        $adminId = 1;
        $journal = $service->createDraft($this->journalPayload($this->journalLines('100.1234')), 1, $adminId);
        $journal = $service->submit($journal, $adminId);
        $journal = $service->approve($journal, $adminId);
        $posted = $service->post($journal, $adminId);

        $this->assertSame(Journal::STATUS_POSTED, $posted->status);
        $this->assertSame($adminId, (int) $posted->created_by);
        $this->assertSame($adminId, (int) $posted->approved_by);
        $this->assertSame($adminId, (int) $posted->posted_by);

        $entry = AccountingEntry::with('lines')->where('source_type', 'manual_journal')->where('source_id', $posted->id)->sole();
        $this->assertSame(1, $entry->financial_year_id);
        $this->assertSame('2026-06-15', $entry->entry_date->format('Y-m-d'));
        $this->assertSame('100.1234', number_format((float) $entry->lines->sum('debit'), 4, '.', ''));
        $this->assertSame('100.1234', number_format((float) $entry->lines->sum('credit'), 4, '.', ''));

        $audit = JournalAuditEvent::where('journal_id', $posted->id)->where('event', 'posted')->first();
        $this->assertTrue((bool) ($audit->metadata['admin_override'] ?? false));
        $this->assertSame('self_post', $audit->metadata['override_type'] ?? null);
        $this->assertSame(['created', 'submitted', 'approved', 'posted'], JournalAuditEvent::where('journal_id', $posted->id)->orderBy('id')->pluck('event')->all());
    }

    public function test_company_admin_cannot_skip_workflow_stages_or_bypass_integrity_rules(): void
    {
        $service = $this->journalService();
        $adminId = 1;
        $draft = $service->createDraft($this->journalPayload($this->journalLines()), 1, $adminId);
        $submitted = $service->submit($draft, $adminId);

        foreach ([$draft, $submitted] as $journal) {
            try {
                $service->post($journal, $adminId);
                $this->fail('Direct posting from '.$journal->status.' was accepted.');
            } catch (RuntimeException) {
                $this->assertTrue(true);
            }
        }

        $locked = $service->approve($submitted, $adminId);
        $service->setLock($locked, $adminId, true, 'Hold');
        try {
            $service->post($locked->fresh(), $adminId);
            $this->fail('Locked journal posting was accepted.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('locked', strtolower($e->getMessage()));
        }

        try {
            $service->createDraft($this->journalPayload([
                ['chart_account_id' => 1, 'debit' => '10.0000', 'credit' => '0.0000'],
                ['chart_account_id' => 10, 'debit' => '0.0000', 'credit' => '9.0000'],
            ]), 1, $adminId);
            $this->fail('Unbalanced journal create was accepted.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }

        $invalidFy = $this->journalPayload($this->journalLines());
        $invalidFy['journal_date'] = '2027-01-01';
        try {
            $service->createDraft($invalidFy, 1, $adminId);
            $this->fail('Invalid financial year journal was accepted.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }

        $foreignPayload = $this->journalPayload($this->journalLines());
        $foreignPayload['lines'] = [
            ['chart_account_id' => 6, 'debit' => '10.0000', 'credit' => '0.0000'],
            ['chart_account_id' => 3, 'debit' => '0.0000', 'credit' => '10.0000'],
        ];
        try {
            $service->createDraft($foreignPayload, 1, $adminId);
            $this->fail('Cross-company chart account was accepted.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }
    }

    public function test_company_admin_override_requires_same_company_membership(): void
    {
        $service = $this->journalService();
        User::insert([
            'id' => 99,
            'name' => 'Foreign Admin',
            'company_id' => 2,
            'role_id' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $journal = $service->createDraft($this->journalPayload($this->journalLines()), 1, 99);
        $journal = $service->submit($journal, 99);

        try {
            $service->approve($journal, 99);
            $this->fail('Foreign company admin self-approval was accepted.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('self-approval', strtolower($e->getMessage()));
            $this->assertSame(Journal::STATUS_SUBMITTED, $journal->fresh()->status);
        }
    }

    public function test_duplicate_posting_remains_blocked_for_company_admin(): void
    {
        $service = $this->journalService();
        $adminId = 1;
        $journal = $service->createDraft($this->journalPayload($this->journalLines()), 1, $adminId);
        $journal = $service->submit($journal, $adminId);
        $journal = $service->approve($journal, $adminId);
        $posted = $service->post($journal, $adminId);

        try {
            $service->post($posted->fresh(), $adminId);
            $this->fail('Duplicate posting was accepted.');
        } catch (RuntimeException) {
            $this->assertSame(Journal::STATUS_POSTED, $posted->fresh()->status);
            $this->assertSame(1, AccountingEntry::where('source_id', $posted->id)->count());
        }
    }

    private function journalService(): JournalService
    {
        return app(JournalService::class);
    }

    private function journalPayload(array $lines): array
    {
        return [
            'financial_year_id' => 1,
            'journal_date' => '2026-06-15',
            'journal_type' => 'general',
            'reference_no' => 'REF',
            'description' => 'Admin override test',
            'remarks' => 'Test',
            'request_key' => (string) Str::uuid(),
            'lines' => $lines,
        ];
    }

    private function journalLines(string $amount = '10.0000'): array
    {
        return [
            ['chart_account_id' => 1, 'debit' => $amount, 'credit' => '0.0000'],
            ['chart_account_id' => 10, 'debit' => '0.0000', 'credit' => $amount],
        ];
    }
}
