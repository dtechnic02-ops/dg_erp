<?php

namespace Tests\Feature;

use App\Models\AccountingEntry;
use App\Models\InventoryValuation;
use App\Models\Product;
use App\Models\SalesCostSnapshot;
use App\Models\SalesInvoice;
use App\Models\SalesItem;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\StockMovement;
use App\Services\Accounting\AccountingPostingService;
use App\Services\Accounting\Integrations\SalesCogsAccountingIntegrationService;
use App\Services\Accounting\Integrations\SalesReturnCogsAccountingIntegrationService;
use App\Services\Accounting\SalesAccountingIntegrationService;
use App\Services\SalesInventoryRestorationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class SalesCancellationReturnValuationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        foreach (['sales_return_items','sales_returns','sales_cost_snapshots','inventory_valuations','stock_movements','sales_items','sales_invoices','products','chart_accounts','accounting_entry_lines','accounting_entries'] as $table) Schema::dropIfExists($table);
        Schema::create('products', fn(Blueprint $t) => $this->productSchema($t));
        Schema::create('sales_invoices', function(Blueprint $t){$t->id();$t->unsignedBigInteger('company_id');$t->unsignedBigInteger('financial_year_id');$t->string('invoice_no');$t->date('sale_date');$t->integer('status')->default(1);$t->unsignedBigInteger('created_by')->nullable();$t->timestamps();});
        Schema::create('sales_items', function(Blueprint $t){$t->id();$t->unsignedBigInteger('company_id');$t->unsignedBigInteger('financial_year_id');$t->unsignedBigInteger('sales_invoice_id');$t->string('item_type');$t->unsignedBigInteger('product_id')->nullable();$t->unsignedBigInteger('service_id')->nullable();$t->decimal('quantity',20,6);$t->decimal('returned_qty',20,6)->default(0);$t->decimal('unit_price',20,4)->default(0);$t->decimal('vat_rate',20,4)->default(0);$t->decimal('vat_amount',20,4)->default(0);$t->decimal('total_price',20,4)->default(0);$t->timestamps();});
        Schema::create('sales_returns', function(Blueprint $t){$t->id();$t->unsignedBigInteger('company_id');$t->unsignedBigInteger('financial_year_id');$t->unsignedBigInteger('sales_invoice_id');$t->string('return_no');$t->date('return_date');$t->unsignedBigInteger('created_by')->nullable();$t->integer('status')->default(1);$t->timestamps();});
        Schema::create('sales_return_items', function(Blueprint $t){$t->id();$t->unsignedBigInteger('company_id');$t->unsignedBigInteger('financial_year_id');$t->unsignedBigInteger('sales_return_id');$t->unsignedBigInteger('sales_item_id');$t->unsignedBigInteger('product_id')->nullable();$t->unsignedBigInteger('service_id')->nullable();$t->decimal('quantity',20,6);$t->integer('status')->default(1);$t->timestamps();});
        Schema::create('stock_movements', function(Blueprint $t){$t->id();$t->unsignedBigInteger('company_id');$t->unsignedBigInteger('financial_year_id');$t->date('transaction_date');$t->unsignedBigInteger('product_id');$t->string('type');$t->decimal('quantity',20,6);$t->decimal('before_stock',20,6);$t->decimal('after_stock',20,6);$t->decimal('unit_price',20,8)->nullable();$t->string('reference_no')->nullable();$t->text('note')->nullable();$t->unsignedBigInteger('created_by')->nullable();$t->timestamps();});
        Schema::create('inventory_valuations', function(Blueprint $t){$t->id();$t->unsignedBigInteger('company_id');$t->unsignedBigInteger('product_id');$t->unsignedBigInteger('stock_movement_id')->unique();$t->unsignedBigInteger('valuation_sequence');$t->string('movement_type');$t->string('source_module');$t->string('source_type');$t->unsignedBigInteger('source_id');$t->string('source_event');foreach(['quantity_before','quantity_change','quantity_after'] as $c)$t->decimal($c,20,6);foreach(['inventory_value_before','inventory_value_change','inventory_value_after'] as $c)$t->decimal($c,20,4);foreach(['average_cost_before','movement_unit_cost','average_cost_after'] as $c)$t->decimal($c,20,8);$t->unsignedBigInteger('reversal_of_id')->nullable();$t->timestamp('valued_at');$t->timestamps();$t->unique(['company_id','product_id','valuation_sequence']);});
        Schema::create('sales_cost_snapshots', function(Blueprint $t){$t->id();$t->unsignedBigInteger('company_id');$t->unsignedBigInteger('sales_invoice_id');$t->unsignedBigInteger('sales_item_id')->unique();$t->unsignedBigInteger('product_id');$t->unsignedBigInteger('stock_movement_id')->unique();$t->unsignedBigInteger('inventory_valuation_id');$t->decimal('average_cost_used',20,8);$t->decimal('movement_unit_cost',20,8);$t->decimal('movement_value',20,4);$t->timestamps();});
        Schema::create('chart_accounts', function(Blueprint $t){$t->id();$t->unsignedBigInteger('company_id');$t->string('code');$t->string('name');$t->string('account_class');$t->string('normal_balance');$t->string('system_code')->nullable();$t->string('status')->default('active');$t->timestamps();$t->softDeletes();});
        Schema::create('accounting_entries', function(Blueprint $t){$t->id();$t->unsignedBigInteger('company_id');$t->string('entry_number');$t->date('entry_date');$t->string('reference_number')->nullable();$t->string('source_module');$t->string('source_type')->nullable();$t->unsignedBigInteger('source_id')->nullable();$t->string('source_event')->nullable();$t->string('source_key')->nullable();$t->text('description')->nullable();$t->string('status');$t->unsignedBigInteger('reversal_of_id')->nullable();$t->timestamp('posted_at')->nullable();$t->unsignedBigInteger('posted_by')->nullable();$t->timestamps();$t->unique(['company_id','source_key']);});
        Schema::create('accounting_entry_lines', function(Blueprint $t){$t->id();$t->unsignedBigInteger('accounting_entry_id');$t->unsignedBigInteger('chart_account_id');$t->unsignedBigInteger('operational_account_id')->nullable();$t->unsignedInteger('line_number');$t->text('description')->nullable();$t->decimal('debit',20,4);$t->decimal('credit',20,4);$t->string('subledger_type')->nullable();$t->unsignedBigInteger('subledger_id')->nullable();$t->timestamps();});
    }

    public function test_product_cancellation_restores_original_snapshot_cost_after_average_changes_and_reverses_exact_entries(): void
    {
        [$sale,$item,$snapshot] = $this->saleFixture(); $this->charts(); $this->postOriginalEntries($sale);
        $this->laterAverageChange($snapshot->product_id);
        $restoration = app(SalesInventoryRestorationService::class)->restore($snapshot, '2.000000', SalesItem::class, $item->id, 'cancelled', $sale->invoice_no, 1, '2026-02-01');
        app(SalesAccountingIntegrationService::class)->reverseSale($sale, '2026-02-01');
        app(SalesCogsAccountingIntegrationService::class)->reverseSaleCogs($sale, '2026-02-01');
        $this->assertSame('20.0000', $restoration->inventory_value_change); $this->assertSame('10.00000000', $restoration->movement_unit_cost);
        $this->assertSame('sales:' . $sale->id . ':cancelled', AccountingEntry::where('source_key','sales:'.$sale->id.':cancelled')->firstOrFail()->source_key);
        $this->assertSame('sales-cogs:' . $sale->id . ':cancelled', AccountingEntry::where('source_key','sales-cogs:'.$sale->id.':cancelled')->firstOrFail()->source_key);
        $this->assertBalanced();
    }

    public function test_full_and_partial_product_returns_use_original_cost_and_service_only_return_posts_no_inventory_cogs(): void
    {
        [$sale,$item,$snapshot] = $this->saleFixture(); $this->charts();
        $return = SalesReturn::create(['company_id'=>1,'financial_year_id'=>1,'sales_invoice_id'=>$sale->id,'return_no'=>'SR-1','return_date'=>'2026-01-10','status'=>1]);
        $returnItem = SalesReturnItem::create(['company_id'=>1,'financial_year_id'=>1,'sales_return_id'=>$return->id,'sales_item_id'=>$item->id,'product_id'=>$snapshot->product_id,'quantity'=>'1.000000','status'=>1]);
        $valuation=app(SalesInventoryRestorationService::class)->restore($snapshot,'1.000000',SalesReturnItem::class,$returnItem->id,'created',$return->return_no,1,'2026-01-10');
        app(SalesReturnCogsAccountingIntegrationService::class)->postReturn($return);
        $this->assertSame('10.0000',$valuation->inventory_value_change);$this->assertSame('10.0000',number_format((float) AccountingEntry::where('source_key','sales-return-cogs:'.$return->id.':created')->firstOrFail()->lines->sum('debit'),4,'.',''));
        $serviceReturn=SalesReturn::create(['company_id'=>1,'financial_year_id'=>1,'sales_invoice_id'=>$sale->id,'return_no'=>'SR-S','return_date'=>'2026-01-10','status'=>1]);SalesReturnItem::create(['company_id'=>1,'financial_year_id'=>1,'sales_return_id'=>$serviceReturn->id,'sales_item_id'=>$item->id,'service_id'=>1,'quantity'=>'1.000000','status'=>1]);app(SalesReturnCogsAccountingIntegrationService::class)->postReturn($serviceReturn);
        $this->assertSame(0,AccountingEntry::where('source_key','sales-return-cogs:'.$serviceReturn->id.':created')->count());
    }

    public function test_missing_snapshot_duplicate_and_foreign_company_restoration_are_rejected_without_partial_records(): void
    {
        [$sale,$item,$snapshot]=$this->saleFixture();
        InventoryValuation::whereKey($snapshot->inventory_valuation_id)->delete();
        try{app(SalesInventoryRestorationService::class)->restore($snapshot,'1.000000',SalesItem::class,$item->id,'cancelled',$sale->invoice_no,1,'2026-02-01');$this->fail();}catch(RuntimeException $e){$this->assertStringContainsString('could not be resolved',$e->getMessage());}
        $this->assertSame(0,InventoryValuation::count());
        [$sale,$item,$snapshot]=$this->saleFixture(); DB::table('sales_cost_snapshots')->where('id',$snapshot->id)->update(['company_id'=>2]);
        try{app(SalesInventoryRestorationService::class)->restore($snapshot,'1.000000',SalesItem::class,$item->id,'cancelled',$sale->invoice_no,1,'2026-02-01');$this->fail();}catch(\Throwable $e){$this->assertSame(1,InventoryValuation::count());}
    }

    public function test_multi_product_cancellation_restores_each_product_at_its_own_snapshot_cost(): void
    {
        [$saleOne,$itemOne,$snapshotOne]=$this->saleFixture();
        [$saleTwo,$itemTwo,$snapshotTwo]=$this->saleFixture();
        $this->laterAverageChange($snapshotOne->product_id);
        $this->laterAverageChange($snapshotTwo->product_id);
        $first=app(SalesInventoryRestorationService::class)->restore($snapshotOne,'2.000000',SalesItem::class,$itemOne->id,'cancelled',$saleOne->invoice_no,1,'2026-02-01');
        $second=app(SalesInventoryRestorationService::class)->restore($snapshotTwo,'2.000000',SalesItem::class,$itemTwo->id,'cancelled',$saleTwo->invoice_no,1,'2026-02-01');
        $this->assertSame('20.0000',$first->inventory_value_change);
        $this->assertSame('20.0000',$second->inventory_value_change);
        $this->assertSame(2,InventoryValuation::where('movement_type','sales_restore')->count());
        $this->assertSame(2,StockMovement::where('type','sales_restore')->count());
    }

    public function test_outer_transaction_rolls_back_all_product_restorations_when_one_snapshot_is_invalid(): void
    {
        [$saleOne,$itemOne,$snapshotOne]=$this->saleFixture();
        [$saleTwo,$itemTwo,$snapshotTwo]=$this->saleFixture();
        InventoryValuation::whereKey($snapshotTwo->inventory_valuation_id)->delete();
        try {
            DB::transaction(function() use($snapshotOne,$snapshotTwo,$itemOne,$itemTwo,$saleOne,$saleTwo):void {
                app(SalesInventoryRestorationService::class)->restore($snapshotOne,'2.000000',SalesItem::class,$itemOne->id,'cancelled',$saleOne->invoice_no,1,'2026-02-01');
                app(SalesInventoryRestorationService::class)->restore($snapshotTwo,'2.000000',SalesItem::class,$itemTwo->id,'cancelled',$saleTwo->invoice_no,1,'2026-02-01');
            });
            $this->fail('An invalid snapshot must reject the complete cancellation transaction.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('could not be resolved',$exception->getMessage());
        }
        $this->assertSame(0,InventoryValuation::where('movement_type','sales_restore')->count());
        $this->assertSame(0,StockMovement::where('type','sales_restore')->count());
    }

    private function productSchema(Blueprint $t):void{$t->id();$t->unsignedBigInteger('company_id');$t->string('name');$t->decimal('cost_price',20,8)->default(0);$t->decimal('current_stock',20,6)->default(0);$t->string('status')->default('active');$t->timestamps();}
    private function saleFixture():array{$product=Product::create(['company_id'=>1,'name'=>'P','current_stock'=>'8.000000']);$sale=SalesInvoice::create(['company_id'=>1,'financial_year_id'=>1,'invoice_no'=>'SA-1','sale_date'=>'2026-01-01','status'=>1]);$item=SalesItem::create(['company_id'=>1,'financial_year_id'=>1,'sales_invoice_id'=>$sale->id,'item_type'=>'product','product_id'=>$product->id,'quantity'=>'2.000000']);$movement=StockMovement::create(['company_id'=>1,'financial_year_id'=>1,'transaction_date'=>'2026-01-01','product_id'=>$product->id,'type'=>'sale','quantity'=>'-2.000000','before_stock'=>'10.000000','after_stock'=>'8.000000','unit_price'=>'0']);$valuation=InventoryValuation::create(['company_id'=>1,'product_id'=>$product->id,'stock_movement_id'=>$movement->id,'valuation_sequence'=>1,'movement_type'=>'sale','source_module'=>'sales','source_type'=>SalesInvoice::class,'source_id'=>$sale->id,'source_event'=>'created','quantity_before'=>'10.000000','quantity_change'=>'-2.000000','quantity_after'=>'8.000000','inventory_value_before'=>'100.0000','inventory_value_change'=>'-20.0000','inventory_value_after'=>'80.0000','average_cost_before'=>'10.00000000','movement_unit_cost'=>'10.00000000','average_cost_after'=>'10.00000000','valued_at'=>now()]);$snapshot=SalesCostSnapshot::create(['company_id'=>1,'sales_invoice_id'=>$sale->id,'sales_item_id'=>$item->id,'product_id'=>$product->id,'stock_movement_id'=>$movement->id,'inventory_valuation_id'=>$valuation->id,'average_cost_used'=>'10.00000000','movement_unit_cost'=>'10.00000000','movement_value'=>'20.0000']);return[$sale,$item,$snapshot];}
    private function charts():void{foreach([['INVENTORY','asset'],['COST_OF_GOODS_SOLD','expense'],['SALES_REVENUE','income'],['ACCOUNTS_RECEIVABLE','asset']]as[$code,$class])DB::table('chart_accounts')->insert(['company_id'=>1,'code'=>$code,'name'=>$code,'account_class'=>$class,'normal_balance'=>'debit','system_code'=>$code,'status'=>'active','created_at'=>now(),'updated_at'=>now()]);}
    private function postOriginalEntries(SalesInvoice $sale):void{$p=app(AccountingPostingService::class);$p->post(['company_id'=>1,'entry_date'=>'2026-01-01','source_module'=>'sales','source_type'=>SalesInvoice::class,'source_id'=>$sale->id,'source_event'=>'created','source_key'=>'sales:'.$sale->id.':created','lines'=>[['chart_account_system_code'=>'ACCOUNTS_RECEIVABLE','debit'=>'20.0000','credit'=>'0.0000'],['chart_account_system_code'=>'SALES_REVENUE','debit'=>'0.0000','credit'=>'20.0000']]]);$p->post(['company_id'=>1,'entry_date'=>'2026-01-01','source_module'=>'sales_cogs','source_type'=>'sales_cogs','source_id'=>$sale->id,'source_event'=>'created','source_key'=>'sales-cogs:'.$sale->id.':created','lines'=>[['chart_account_system_code'=>'COST_OF_GOODS_SOLD','debit'=>'20.0000','credit'=>'0.0000'],['chart_account_system_code'=>'INVENTORY','debit'=>'0.0000','credit'=>'20.0000']]]);}
    private function laterAverageChange(int $productId):void{$m=StockMovement::create(['company_id'=>1,'financial_year_id'=>1,'transaction_date'=>'2026-01-02','product_id'=>$productId,'type'=>'purchase','quantity'=>'2.000000','before_stock'=>'8.000000','after_stock'=>'10.000000','unit_price'=>'20']);InventoryValuation::create(['company_id'=>1,'product_id'=>$productId,'stock_movement_id'=>$m->id,'valuation_sequence'=>2,'movement_type'=>'purchase','source_module'=>'purchase','source_type'=>'purchase','source_id'=>1,'source_event'=>'created','quantity_before'=>'8.000000','quantity_change'=>'2.000000','quantity_after'=>'10.000000','inventory_value_before'=>'80.0000','inventory_value_change'=>'40.0000','inventory_value_after'=>'120.0000','average_cost_before'=>'10.00000000','movement_unit_cost'=>'20.00000000','average_cost_after'=>'12.00000000','valued_at'=>now()]);Product::whereKey($productId)->update(['current_stock'=>'10.000000']);}
    private function assertBalanced():void{foreach(AccountingEntry::with('lines')->get()as$entry)$this->assertSame((string)$entry->lines->sum('debit'),(string)$entry->lines->sum('credit'));}
}
