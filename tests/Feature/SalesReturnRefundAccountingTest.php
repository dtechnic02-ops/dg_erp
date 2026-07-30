<?php

namespace Tests\Feature;

use App\Services\Accounting\AccountingPostingService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class SalesReturnRefundAccountingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('accounting_entry_lines');
        Schema::dropIfExists('accounting_entries');
        Schema::dropIfExists('chart_accounts');
        Schema::dropIfExists('accounts');

        Schema::create('accounts', fn (Blueprint $table) => $table->id()->unsignedBigInteger('company_id'));
        Schema::create('chart_accounts', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('company_id'); $table->string('system_code'); $table->string('status')->default('active'); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('accounting_entries', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('company_id'); $table->string('entry_number'); $table->date('entry_date'); $table->string('reference_number')->nullable(); $table->string('source_module'); $table->string('source_type')->nullable(); $table->unsignedBigInteger('source_id')->nullable(); $table->string('source_event')->nullable(); $table->string('source_key')->nullable(); $table->text('description')->nullable(); $table->string('status'); $table->unsignedBigInteger('reversal_of_id')->nullable(); $table->timestamp('posted_at')->nullable(); $table->unsignedBigInteger('posted_by')->nullable(); $table->timestamps(); $table->unique(['company_id', 'source_key']);
        });
        Schema::create('accounting_entry_lines', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('accounting_entry_id'); $table->unsignedBigInteger('chart_account_id'); $table->unsignedBigInteger('operational_account_id')->nullable(); $table->unsignedInteger('line_number'); $table->text('description')->nullable(); $table->decimal('debit', 20, 4); $table->decimal('credit', 20, 4); $table->string('subledger_type')->nullable(); $table->unsignedBigInteger('subledger_id')->nullable(); $table->timestamps();
        });
        DB::table('chart_accounts')->insert([
            ['company_id'=>1,'system_code'=>'SALES_RETURNS','status'=>'active'], ['company_id'=>1,'system_code'=>'OUTPUT_TAX_PAYABLE','status'=>'active'], ['company_id'=>1,'system_code'=>'ACCOUNTS_RECEIVABLE','status'=>'active'], ['company_id'=>1,'system_code'=>'CASH_IN_HAND','status'=>'active'],
        ]);
    }

    public function test_persists_cash_only_refund_lines(): void
    {
        $entry = app(AccountingPostingService::class)->post($this->payload(1, '300.0000', '270.0000', '30.0000'));
        $this->assertDatabaseHas('accounting_entries', ['id'=>$entry->id, 'source_type'=>'sales_return_refund', 'source_event'=>'created', 'status'=>'posted']);
        $this->assertSame(3, $entry->lines()->count());
    }

    public function test_blocks_canonical_duplicate_source_identity(): void
    {
        $service = app(AccountingPostingService::class); $service->post($this->payload(1, '300.0000', '270.0000', '30.0000'));
        $this->expectException(RuntimeException::class); $service->post($this->payload(1, '300.0000', '270.0000', '30.0000'));
    }

    public function test_reverses_exact_persisted_lines(): void
    {
        $service = app(AccountingPostingService::class); $original = $service->post($this->payload(1, '300.0000', '270.0000', '30.0000'));
        $reversal = $service->reverseBySource(['company_id'=>1,'entry_date'=>'2026-01-02','original_source_key'=>'sales_return_refund:1:created','original_source_event'=>'created','original_source_types'=>['App\\Models\\SalesReturnRefund'],'reversal_source_key'=>'sales_return_refund_cancel:1:cancelled','source_module'=>'sales_return_refund','source_type'=>'sales_return_refund','source_id'=>1,'source_event'=>'cancelled']);
        $this->assertDatabaseHas('accounting_entries', ['id'=>$original->id,'status'=>'reversed']);
        $this->assertDatabaseHas('accounting_entries', ['id'=>$reversal->id,'reversal_of_id'=>$original->id,'source_event'=>'cancelled']);
        $this->assertSame('0.0000', $reversal->lines()->orderBy('line_number')->first()->debit);
        $this->assertSame('270.0000', $reversal->lines()->orderBy('line_number')->first()->credit);
    }

    private function payload(int $refundId, string $settlement, string $net, string $tax): array
    {
        return ['company_id'=>1,'entry_date'=>'2026-01-01','source_module'=>'sales_return_refund','source_type'=>'sales_return_refund','source_type_aliases'=>['App\\Models\\SalesReturnRefund'],'source_id'=>$refundId,'source_event'=>'created','source_key'=>"sales_return_refund:{$refundId}:created",'lines'=>[['chart_account_system_code'=>'SALES_RETURNS','operational_account_id'=>null,'debit'=>$net,'credit'=>'0.0000'],['chart_account_system_code'=>'OUTPUT_TAX_PAYABLE','operational_account_id'=>null,'debit'=>$tax,'credit'=>'0.0000'],['chart_account_system_code'=>'CASH_IN_HAND','operational_account_id'=>null,'debit'=>'0.0000','credit'=>$settlement]]];
    }
}
