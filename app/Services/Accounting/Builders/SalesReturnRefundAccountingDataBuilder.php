<?php

namespace App\Services\Accounting\Builders;

use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\AccountingEntry;
use App\Models\ChartAccount;
use App\Models\CustomerTransaction;
use App\Models\FinancialYear;
use App\Models\SalesInvoice;
use App\Models\SalesItem;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\SalesReturnRefund;
use App\Models\SalesReturnRefundAdjustment;
use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;
use RuntimeException;

class SalesReturnRefundAccountingDataBuilder
{
    public function build(SalesReturnRefund $refund): array
    {
        if (! $refund->exists || ! $refund->isActive()) {
            throw new RuntimeException('Only a persisted active sales return refund can be posted to accounting.');
        }

        $companyId = $this->id($refund->company_id, 'company_id');
        $refundId = $this->id($refund->id, 'refund_id');
        $date = $this->date($refund->refund_date, 'refund_date');
        $financialYear = FinancialYear::where('company_id', $companyId)->find($this->id($refund->financial_year_id, 'financial_year_id'));
        if (! $financialYear || $date < $financialYear->start_date || $date > $financialYear->end_date) {
            throw new RuntimeException('The refund date does not belong to its company financial year.');
        }

        $return = SalesReturn::where('company_id', $companyId)->find($this->id($refund->sales_return_id, 'sales_return_id'));
        if (! $return || (int) $return->status !== 1) {
            throw new RuntimeException('The related sales return is not active.');
        }

        $customerId = $this->id($refund->customer_id, 'customer_id');
        $invoice = SalesInvoice::where('company_id', $companyId)->find($this->id($return->sales_invoice_id, 'sales_invoice_id'));
        if (! $invoice || (int) $return->customer_id !== $customerId || (int) $invoice->customer_id !== $customerId) {
            throw new RuntimeException('The refund, sales return, invoice, and customer must belong together.');
        }

        $adjust = $this->money($refund->adjust_amount, 'refund adjust_amount');
        $cash = $this->money($refund->cash_amount, 'refund cash_amount');
        $settlement = $this->money($refund->refund_amount, 'refund refund_amount');
        if ($this->zero($settlement) || ! $this->same($this->add($adjust, $cash), $settlement)) {
            throw new RuntimeException('Persisted refund settlement must equal adjust_amount plus cash_amount.');
        }

        $this->validateAdjustments($companyId, $refundId, $customerId, $adjust);
        $account = $this->validateCashEffect($companyId, $refund, $cash);
        $this->validateCustomerEffect($companyId, $refundId, $customerId, $settlement);

        [$productGross, $serviceGross, $tax, $returnGross] = $this->returnedComponents($return, $invoice, $companyId);
        if (! $this->same($returnGross, $this->money($return->grand_total, 'sales return grand_total')) || ! $this->same($tax, $this->money($return->total_vat, 'sales return total_vat'))) {
            throw new RuntimeException('Sales return items do not reconcile with the persisted header.');
        }

        [$originalEligible, $originalDiscount] = $this->originalEligibleAmounts($invoice, $companyId);
        $returnEligible = $this->add($productGross, $serviceGross);
        $allocatedDiscount = $this->proportion($originalDiscount, $returnEligible, $originalEligible);
        $productDiscount = $this->proportion($allocatedDiscount, $productGross, $returnEligible);
        $serviceDiscount = $this->sub($allocatedDiscount, $productDiscount);
        $fullNetReturn = $this->sub($returnEligible, $allocatedDiscount);

        if (! $this->same($this->add($fullNetReturn, $tax), $returnGross)) {
            throw new RuntimeException('Persisted sales return totals do not reconcile with the allocated invoice discount.');
        }

        $priorRefundIds = $this->priorActiveRefundIds($companyId, $return->id, $refundId, $return, $settlement);
        $prior = $this->actualPostedComponents($companyId, $priorRefundIds);
        $final = $this->same($this->add($prior['settlement'], $settlement), $returnGross);

        $netReturnComponent = $final
            ? $this->sub($fullNetReturn, $prior['sales_returns'])
            : $this->proportion($fullNetReturn, $settlement, $returnGross);
        $taxComponent = $final
            ? $this->sub($tax, $prior['tax'])
            : $this->proportion($tax, $settlement, $returnGross);
        $discountComponent = $final
            ? $this->sub($allocatedDiscount, $prior['discount'])
            : $this->proportion($allocatedDiscount, $settlement, $returnGross);
        $productShare = $this->proportion($netReturnComponent, $this->sub($productGross, $productDiscount), $fullNetReturn);
        $serviceShare = $this->sub($netReturnComponent, $productShare);
        $components = [
            'product_gross' => $productShare,
            'service_gross' => $serviceShare,
            'gross_eligible' => $this->add($netReturnComponent, $discountComponent),
            'discount' => $discountComponent,
            'net_return' => $netReturnComponent,
            'tax' => $taxComponent,
        ];

        if (! $this->same($this->sub($components['gross_eligible'], $components['discount']), $components['net_return'])
            || ! $this->same($this->add($components['net_return'], $components['tax']), $settlement)) {
            throw new RuntimeException('Refund accounting components do not balance to the persisted settlement.');
        }
        if ($final && (! $this->same($this->add($prior['sales_returns'], $components['net_return']), $fullNetReturn)
            || ! $this->same($this->add($prior['tax'], $components['tax']), $tax))) {
            throw new RuntimeException('Final refund accounting components do not close the complete sales return.');
        }

        return [
            'company_id' => $companyId,
            'refund_id' => $refundId,
            'refund_date' => $date,
            'refund_number' => $this->text($refund->refund_no, 'refund_no'),
            'customer_id' => $customerId,
            'created_by' => $refund->created_by === null ? null : $this->id($refund->created_by, 'created_by'),
            'adjust_amount' => $adjust,
            'cash_amount' => $cash,
            'settlement_amount' => $settlement,
            'account_id' => $account?->id,
            'account_type' => $account?->account_type,
            'components' => $components,
        ];
    }

    private function validateAdjustments(int $companyId, int $refundId, int $customerId, string $expected): void
    {
        $total = '0.0000';
        foreach (SalesReturnRefundAdjustment::where('company_id', $companyId)->where('sales_return_refund_id', $refundId)->where('status', 1)->get() as $adjustment) {
            $invoice = SalesInvoice::where('company_id', $companyId)->find($this->id($adjustment->sales_invoice_id, 'adjustment invoice id'));
            if (! $invoice || (int) $invoice->customer_id !== $customerId) throw new RuntimeException('A refund adjustment is outside the refund company or customer.');
            $total = $this->add($total, $this->money($adjustment->adjust_amount, 'adjustment amount'));
        }
        if (! $this->same($total, $expected)) throw new RuntimeException('Refund adjust_amount does not equal active persisted adjustments.');
    }

    private function validateCashEffect(int $companyId, SalesReturnRefund $refund, string $cash): ?Account
    {
        if ($this->zero($cash)) {
            if ($refund->account_id !== null) throw new RuntimeException('A zero-cash refund cannot have an operational account.');
            return null;
        }
        $account = Account::where('company_id', $companyId)->where('status', 'active')->find($this->id($refund->account_id, 'refund account_id'));
        if (! $account || ! in_array($account->account_type, ['Cash', 'Bank', 'ATM', 'Wallet'], true)) throw new RuntimeException('The refund account is unsupported.');
        $transactions = AccountTransaction::where('company_id', $companyId)->where('reference_type', 'sales_return_refund')->where('reference_id', $refund->id)->where('status', 1)->get();
        if ($transactions->count() !== 1 || ! $this->zero($this->money($transactions->first()->debit, 'account transaction debit')) || ! $this->same($this->money($transactions->first()->credit, 'account transaction credit'), $cash)) throw new RuntimeException('The refund cash amount does not match its operational transaction.');
        return $account;
    }

    private function validateCustomerEffect(int $companyId, int $refundId, int $customerId, string $settlement): void
    {
        $total = '0.0000';
        foreach (CustomerTransaction::where('company_id', $companyId)->where('reference_id', $refundId)->whereIn('reference_type', ['sales_return_refund', 'sales_return_refund_adjustment'])->where('status', 1)->get() as $transaction) {
            if ((int) $transaction->customer_id !== $customerId || ! $this->zero($this->money($transaction->debit, 'customer transaction debit'))) throw new RuntimeException('The refund customer transaction is invalid.');
            $total = $this->add($total, $this->money($transaction->credit, 'customer transaction credit'));
        }
        if (! $this->same($total, $settlement)) throw new RuntimeException('Customer transaction credits do not equal the refund settlement.');
    }

    private function returnedComponents(SalesReturn $return, SalesInvoice $invoice, int $companyId): array
    {
        $product = '0.0000'; $service = '0.0000'; $tax = '0.0000'; $gross = '0.0000';
        foreach (SalesReturnItem::where('company_id', $companyId)->where('sales_return_id', $return->id)->with('salesItem')->get() as $item) {
            $saleItem = $item->salesItem;
            if (! $saleItem || (int) $saleItem->company_id !== $companyId || (int) $saleItem->sales_invoice_id !== (int) $invoice->id) throw new RuntimeException('A return item does not match the original invoice.');
            $total = $this->money($item->total_price, 'return item total'); $vat = $this->money($item->vat_amount, 'return item VAT');
            if ($this->cmp($vat, $total) > 0) throw new RuntimeException('Return item VAT exceeds its total.');
            $net = $this->sub($total, $vat); $gross = $this->add($gross, $total); $tax = $this->add($tax, $vat);
            if ($saleItem->item_type === 'product') $product = $this->add($product, $net); elseif ($saleItem->item_type === 'service') $service = $this->add($service, $net); else throw new RuntimeException('Unsupported original sales classification.');
        }
        return [$product, $service, $tax, $gross];
    }

    private function originalEligibleAmounts(SalesInvoice $invoice, int $companyId): array
    {
        $eligible = '0.0000';
        foreach (SalesItem::where('company_id', $companyId)->where('sales_invoice_id', $invoice->id)->get() as $item) {
            $eligible = $this->add($eligible, $this->sub($this->money($item->total_price, 'original item total'), $this->money($item->vat_amount, 'original item VAT')));
        }
        if ($this->zero($eligible)) throw new RuntimeException('Original eligible sales amount cannot be zero.');
        return [$eligible, $this->money($invoice->discount, 'invoice discount')];
    }

    private function priorActiveRefundIds(int $companyId, int $returnId, int $currentRefundId, SalesReturn $return, string $settlement): array
    {
        $ids = []; $cumulative = $settlement;
        foreach (SalesReturnRefund::where('company_id', $companyId)->where('sales_return_id', $returnId)->active()->where('id', '!=', $currentRefundId)->orderBy('id')->get() as $refund) {
            $ids[] = (int) $refund->id;
            $cumulative = $this->add($cumulative, $this->money($refund->refund_amount, 'prior refund amount'));
        }
        $grand = $this->money($return->grand_total, 'sales return grand total');
        if ($this->cmp($cumulative, $grand) > 0 || ! $this->same($cumulative, $this->money($return->adjust_amount, 'sales return adjust amount')) || ! $this->same($this->sub($grand, $cumulative), $this->money($return->refund_amount, 'sales return refund amount'))) throw new RuntimeException('Sales return settlement totals are inconsistent.');
        return $ids;
    }

    private function actualPostedComponents(int $companyId, array $refundIds): array
    {
        $totals = ['sales_returns'=>'0.0000','tax'=>'0.0000','receivable'=>'0.0000','operational'=>'0.0000','settlement'=>'0.0000','product_net'=>'0.0000','service_net'=>'0.0000','discount'=>'0.0000'];
        if ($refundIds === []) return $totals;
        $entries = AccountingEntry::where('company_id', $companyId)->whereIn('source_type', ['sales_return_refund', SalesReturnRefund::class])->whereIn('source_id', $refundIds)->where('source_event', 'created')->where('status', 'posted')->whereNull('reversal_of_id')->with('lines.chartAccount')->get();
        if ($entries->count() !== count($refundIds)) throw new RuntimeException('Every prior active refund must have one active posted original accounting entry.');
        foreach ($entries as $entry) foreach ($entry->lines as $line) {
            $code = $line->chartAccount?->system_code;
            if ($code === 'SALES_RETURNS') { $totals['sales_returns'] = $this->add($totals['sales_returns'], $this->money($line->debit, 'prior sales returns debit')); }
            elseif ($code === 'OUTPUT_TAX_PAYABLE') { $totals['tax'] = $this->add($totals['tax'], $this->money($line->debit, 'prior tax debit')); }
            elseif ($code === 'ACCOUNTS_RECEIVABLE') { $totals['receivable'] = $this->add($totals['receivable'], $this->money($line->credit, 'prior receivable credit')); }
            elseif (in_array($code, ['CASH_IN_HAND','BANK_ACCOUNTS'], true)) { $totals['operational'] = $this->add($totals['operational'], $this->money($line->credit, 'prior operational credit')); }
        }
        $totals['settlement'] = $this->add($totals['receivable'], $totals['operational']);
        return $totals;
    }

    private function text(mixed $value, string $field): string { if (! is_string($value) || trim($value) === '') throw new InvalidArgumentException("The {$field} value is required."); return trim($value); }
    private function id(mixed $value, string $field): int { if (filter_var($value, FILTER_VALIDATE_INT) === false || (int) $value < 1) throw new InvalidArgumentException("The {$field} value must be positive."); return (int) $value; }
    private function date(mixed $value, string $field): string { if ($value instanceof DateTimeInterface) $value = $value->format('Y-m-d'); if (! is_string($value)) throw new InvalidArgumentException("The {$field} value must be Y-m-d."); $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value); $errors = DateTimeImmutable::getLastErrors(); if (! $date || ($errors !== false && ($errors['warning_count'] || $errors['error_count'])) || $date->format('Y-m-d') !== $value) throw new InvalidArgumentException("The {$field} value must be Y-m-d."); return $value; }
    private function money(mixed $value, string $field): string { if (is_int($value)) $value = (string) $value; if (! is_string($value) || ! preg_match('/^\d+(?:\.\d{1,4})?$/', trim($value))) throw new InvalidArgumentException("The {$field} value must be a non-negative decimal."); [$whole,$fraction] = array_pad(explode('.', trim($value), 2), 2, ''); return (ltrim($whole, '0') ?: '0').'.'.str_pad($fraction, 4, '0'); }
    private function zero(string $value): bool { return $this->scaled($value) === '0'; }
    private function same(string $left, string $right): bool { return $this->scaled($left) === $this->scaled($right); }
    private function cmp(string $left, string $right): int { return $this->cmpu($this->scaled($left), $this->scaled($right)); }
    private function add(string $left, string $right): string { return $this->decimal($this->addu($this->scaled($left), $this->scaled($right))); }
    private function sub(string $left, string $right): string { $left=$this->scaled($left);$right=$this->scaled($right);if($this->cmpu($left,$right)<0)throw new RuntimeException('A refund accounting amount cannot become negative.');return $this->decimal($this->subu($left,$right)); }
    private function proportion(string $amount, string $part, string $total): string { if ($this->zero($amount) || $this->zero($part)) return '0.0000'; return $this->decimal($this->divu($this->mulu($this->scaled($amount), $this->scaled($part)), $this->scaled($total))); }
    private function scaled(string $value): string { [$whole,$fraction]=explode('.',$value,2);return ltrim($whole.$fraction,'0')?:'0'; }
    private function decimal(string $value): string { $value=str_pad(ltrim($value,'0')?:'0',5,'0',STR_PAD_LEFT);return substr($value,0,-4).'.'.substr($value,-4); }
    private function cmpu(string $left,string $right):int{$left=ltrim($left,'0')?:'0';$right=ltrim($right,'0')?:'0';return strlen($left)===strlen($right)?$left<=>$right:strlen($left)<=>strlen($right);}
    private function addu(string $left,string $right):string{$carry=0;$result='';$i=strlen($left)-1;$j=strlen($right)-1;while($i>=0||$j>=0||$carry){$sum=($i>=0?(int)$left[$i--]:0)+($j>=0?(int)$right[$j--]:0)+$carry;$result=($sum%10).$result;$carry=intdiv($sum,10);}return ltrim($result,'0')?:'0';}
    private function subu(string $left,string $right):string{$borrow=0;$result='';$i=strlen($left)-1;$j=strlen($right)-1;while($i>=0){$value=(int)$left[$i--]-($j>=0?(int)$right[$j--]:0)-$borrow;if($value<0){$value+=10;$borrow=1;}else $borrow=0;$result=$value.$result;}return ltrim($result,'0')?:'0';}
    private function mulu(string $left,string $right):string{$result='0';for($j=strlen($right)-1,$zeros=0;$j>=0;$j--,$zeros++){$carry=0;$partial='';for($i=strlen($left)-1;$i>=0;$i--){$value=(int)$left[$i]*(int)$right[$j]+$carry;$partial=($value%10).$partial;$carry=intdiv($value,10);}$result=$this->addu($result,(ltrim((string)$carry.$partial,'0')?:'0').str_repeat('0',$zeros));}return ltrim($result,'0')?:'0';}
    private function divu(string $dividend,string $divisor):string{if($this->cmpu($divisor,'0')===0)throw new RuntimeException('Division by zero.');$quotient='';$remainder='0';foreach(str_split($dividend) as $digit){$remainder=ltrim($remainder.$digit,'0')?:'0';$value=0;while($this->cmpu($remainder,$divisor)>=0){$remainder=$this->subu($remainder,$divisor);$value++;}$quotient.=$value;}return ltrim($quotient,'0')?:'0';}
}
