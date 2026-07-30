<?php

namespace Tests\Feature;

use App\Models\AccountingEntry;
use App\Services\Accounting\AccountingPostingService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class AccountingPostingServiceExplicitChartAccountTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['accounting_entry_lines', 'accounting_entries', 'chart_accounts', 'accounts'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('chart_accounts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('code');
            $table->string('name');
            $table->string('account_class');
            $table->string('system_code')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('accounts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('account_type');
            $table->integer('status')->default(1);
            $table->timestamps();
        });

        Schema::create('accounting_entries', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('entry_number');
            $table->date('entry_date');
            $table->string('reference_number')->nullable();
            $table->string('source_module');
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('source_event')->nullable();
            $table->string('source_key')->nullable();
            $table->text('description')->nullable();
            $table->string('status');
            $table->unsignedBigInteger('reversal_of_id')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'source_key']);
        });

        Schema::create('accounting_entry_lines', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('accounting_entry_id');
            $table->unsignedBigInteger('chart_account_id');
            $table->unsignedBigInteger('operational_account_id')->nullable();
            $table->unsignedInteger('line_number');
            $table->text('description')->nullable();
            $table->decimal('debit', 20, 4)->default(0);
            $table->decimal('credit', 20, 4)->default(0);
            $table->string('subledger_type')->nullable();
            $table->unsignedBigInteger('subledger_id')->nullable();
            $table->timestamps();
        });
    }

    public function test_explicit_chart_account_posting_validates_scope_status_and_reversal_history(): void
    {
        $explicitId = $this->chart(1, '4001', null, 'active');
        $cashId = $this->chart(1, '1110', 'CASH_IN_HAND', 'active');
        $foreignId = $this->chart(2, '4001', null, 'active');
        $inactiveId = $this->chart(1, '4002', null, 'inactive');
        $deletedId = $this->chart(1, '4003', null, 'active');
        \DB::table('chart_accounts')->where('id', $deletedId)->update(['deleted_at' => now()]);

        $entry = $this->service()->post($this->payload('explicit:1', 1, [
            $this->line($explicitId, '10.0000', '0.0000'),
            $this->systemLine('CASH_IN_HAND', '0.0000', '10.0000'),
        ]));

        $this->assertSame($explicitId, $entry->lines()->orderBy('line_number')->firstOrFail()->chart_account_id);
        $this->assertSame($cashId, $entry->lines()->orderBy('line_number')->skip(1)->firstOrFail()->chart_account_id);

        $reversal = $this->service()->reverseBySource([
            'company_id' => 1,
            'entry_date' => '2026-07-28',
            'original_source_key' => 'explicit:1',
            'original_source_event' => 'created',
            'original_source_types' => ['explicit_test'],
            'reversal_source_key' => 'explicit:1:cancelled',
            'source_module' => 'explicit_test',
            'source_type' => 'explicit_test',
            'source_id' => 1,
            'source_event' => 'cancelled',
        ]);

        $this->assertSame($entry->id, $reversal->reversal_of_id);
        $this->assertSame($explicitId, $reversal->lines()->orderBy('line_number')->firstOrFail()->chart_account_id);

        foreach ([$foreignId, $inactiveId, $deletedId] as $invalidId) {
            try {
                $this->service()->post($this->payload('explicit:invalid:' . $invalidId, 100 + $invalidId, [
                    $this->line($invalidId, '10.0000', '0.0000'),
                    $this->systemLine('CASH_IN_HAND', '0.0000', '10.0000'),
                ]));
                $this->fail('Invalid explicit chart account must be rejected.');
            } catch (RuntimeException $exception) {
                $this->assertSame('One or more explicit chart accounts could not be resolved for this company.', $exception->getMessage());
            }
        }

        try {
            $this->service()->post($this->payload('explicit:conflict', 999, [
                [
                    'chart_account_id' => $explicitId,
                    'chart_account_system_code' => 'CASH_IN_HAND',
                    'debit' => '10.0000',
                    'credit' => '0.0000',
                ],
                $this->systemLine('CASH_IN_HAND', '0.0000', '10.0000'),
            ]));
            $this->fail('Conflicting chart account identifiers must be rejected.');
        } catch (RuntimeException $exception) {
            $this->assertSame('The explicit chart account conflicts with chart_account_system_code.', $exception->getMessage());
        }
    }

    private function service(): AccountingPostingService
    {
        return app(AccountingPostingService::class);
    }

    private function chart(int $companyId, string $code, ?string $systemCode, string $status): int
    {
        return \DB::table('chart_accounts')->insertGetId([
            'company_id' => $companyId,
            'code' => $code,
            'name' => $code,
            'account_class' => 'income',
            'system_code' => $systemCode,
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function payload(string $sourceKey, int $sourceId, array $lines): array
    {
        return [
            'company_id' => 1,
            'entry_date' => '2026-07-28',
            'source_module' => 'explicit_test',
            'source_type' => 'explicit_test',
            'source_id' => $sourceId,
            'source_event' => 'created',
            'source_key' => $sourceKey,
            'lines' => $lines,
        ];
    }

    private function line(int $chartAccountId, string $debit, string $credit): array
    {
        return [
            'chart_account_id' => $chartAccountId,
            'debit' => $debit,
            'credit' => $credit,
        ];
    }

    private function systemLine(string $systemCode, string $debit, string $credit): array
    {
        return [
            'chart_account_system_code' => $systemCode,
            'debit' => $debit,
            'credit' => $credit,
        ];
    }
}
