<?php

namespace Tests\Unit;

use App\Models\PurchaseInvoice;
use App\Models\PurchaseItem;
use App\Services\Accounting\PurchaseReturnValuationService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class PurchaseReturnValuationServiceTest extends TestCase
{
    public function test_original_discount_vat_and_partial_quantities_are_allocated_deterministically(): void
    {
        $product=new PurchaseItem(['item_type'=>'product','quantity'=>'10.0000','total_price'=>'110.00','vat_amount'=>'10.00']); $product->id=1;
        $service=new PurchaseItem(['item_type'=>'service','quantity'=>'2.0000','total_price'=>'55.00','vat_amount'=>'5.00']); $service->id=2;
        $invoice=new class(new Collection([$product,$service])) extends PurchaseInvoice {
            private Collection $fixtureItems;
            public function __construct(?Collection $fixtureItems=null){$this->fixtureItems=$fixtureItems??new Collection();parent::__construct($fixtureItems?['discount'=>'15.00']:[]);}
            public function items(){return new class($this->fixtureItems){public function __construct(private Collection $items){} public function where(){return $this;} public function orderBy(){return $this;} public function get(){return $this->items;}};}
        };

        $values=(new PurchaseReturnValuationService())->calculate($invoice,[1=>'5.0000',2=>'2.0000']);

        $this->assertSame('45.0000',$values['product_net']);
        $this->assertSame('45.0000',$values['service_net']);
        $this->assertSame('10.0000',$values['tax']);
        $this->assertSame('100.0000',$values['total']);
        $this->assertSame(['net'=>'45.0000','tax'=>'5.0000','total'=>'50.0000'],$values['items'][1]);
        $this->assertSame(['net'=>'45.0000','tax'=>'5.0000','total'=>'50.0000'],$values['items'][2]);
    }
}
