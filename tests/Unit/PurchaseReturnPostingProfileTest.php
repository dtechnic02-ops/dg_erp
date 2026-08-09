<?php

namespace Tests\Unit;

use App\Services\Accounting\Profiles\PurchaseReturnPostingProfile;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class PurchaseReturnPostingProfileTest extends TestCase
{
    public function test_product_return_uses_frozen_mapping(): void
    {
        $entry=$this->profile()->build($this->data(product:'90.0000',tax:'10.0000',total:'100.0000'));
        $this->assertLine($entry,'SUPPLIER_RETURN_RECEIVABLE','100.0000','0.0000');
        $this->assertLine($entry,'INVENTORY','0.0000','90.0000');
        $this->assertLine($entry,'INPUT_TAX_RECEIVABLE','0.0000','10.0000');
        $this->assertForbiddenSettlementLinesAbsent($entry);
    }

    public function test_service_return_uses_original_resolved_service_account(): void
    {
        $entry=$this->profile()->build($this->data(service:'180.0000',tax:'20.0000',total:'200.0000'));
        $this->assertLine($entry,'SUPPLIER_RETURN_RECEIVABLE','200.0000','0.0000');
        $this->assertLine($entry,'SERVICE_PURCHASE_EXPENSE','0.0000','180.0000');
        $this->assertLine($entry,'INPUT_TAX_RECEIVABLE','0.0000','20.0000');
        $this->assertForbiddenSettlementLinesAbsent($entry);
    }

    public function test_mixed_product_and_service_return_is_balanced(): void
    {
        $entry=$this->profile()->build($this->data(product:'90.0000',service:'45.0000',tax:'15.0000',total:'150.0000'));
        $this->assertSame('150.0000',array_reduce($entry['lines'],fn($c,$l)=>bcadd($c,$l['debit'],4),'0.0000'));
        $this->assertSame('150.0000',array_reduce($entry['lines'],fn($c,$l)=>bcadd($c,$l['credit'],4),'0.0000'));
        $this->assertSame('purchase_return:9:created',$entry['source_key']);
    }

    public function test_unbalanced_return_values_fail_clearly(): void
    {
        $this->expectException(RuntimeException::class);
        $this->profile()->build($this->data(product:'90.0000',tax:'10.0000',total:'99.0000'));
    }

    private function data(string $product='0.0000',string $service='0.0000',string $tax='0.0000',string $total='0.0000'): array
    {
        return ['company_id'=>1,'financial_year_id'=>1,'return_id'=>9,'return_date'=>'2026-06-15','return_number'=>'PR-9','supplier_id'=>5,
            'created_by'=>1,'product_net'=>$product,'service_net'=>$service,'tax'=>$tax,'total'=>$total,
            'service_account_system_code'=>$service==='0.0000'?null:'SERVICE_PURCHASE_EXPENSE'];
    }

    private function assertLine(array $entry,string $code,string $debit,string $credit): void
    {
        $line=null; foreach($entry['lines'] as $candidate){ if($candidate['chart_account_system_code']===$code){$line=$candidate;break;} }
        $this->assertNotNull($line); $this->assertSame($debit,$line['debit']); $this->assertSame($credit,$line['credit']);
    }

    private function assertForbiddenSettlementLinesAbsent(array $entry): void
    {
        $codes=array_column($entry['lines'],'chart_account_system_code');
        $this->assertNotContains('ACCOUNTS_PAYABLE',$codes); $this->assertNotContains('CASH_IN_HAND',$codes); $this->assertNotContains('BANK_ACCOUNTS',$codes);
    }

    private function profile(): PurchaseReturnPostingProfile { return new PurchaseReturnPostingProfile(); }
}
