<?php

namespace App\Services\Accounting\Builders;

use App\Models\SalesCostSnapshot;
use App\Models\SalesInvoice;
use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;
use RuntimeException;

class SalesCogsAccountingDataBuilder
{
    public function build(SalesInvoice $sale): array
    {
        if (! $sale->exists) {
            throw new InvalidArgumentException('The sales invoice must be saved before COGS can be posted.');
        }

        if ((int) $sale->status !== 1) {
            throw new RuntimeException('Only active sales invoices can be posted to COGS.');
        }

        $companyId = $this->positiveInteger($sale->company_id, 'company_id');
        $snapshots = SalesCostSnapshot::query()
            ->where('company_id', $companyId)
            ->where('sales_invoice_id', $sale->id)
            ->orderBy('id')
            ->get();

        if ($snapshots->isEmpty()) {
            throw new RuntimeException('The sales invoice has no product cost snapshots to post.');
        }

        $cost = '0.0000';

        foreach ($snapshots as $snapshot) {
            if ((int) $snapshot->company_id !== $companyId || (int) $snapshot->sales_invoice_id !== (int) $sale->id) {
                throw new RuntimeException('A sales cost snapshot belongs to another company or sales invoice.');
            }

            $amount = $this->amount($snapshot->movement_value, 'sales cost snapshot movement_value');

            if ($this->isZero($amount)) {
                throw new RuntimeException('A sales cost snapshot amount must be greater than zero.');
            }

            $cost = $this->add($cost, $amount);
        }

        return [
            'company_id' => $companyId,
            'entry_date' => $this->date($sale->sale_date),
            'reference_number' => $sale->invoice_no,
            'source_module' => 'sales_cogs',
            'source_type' => 'sales_cogs',
            'source_id' => (int) $sale->id,
            'source_event' => 'created',
            'source_key' => 'sales-cogs:' . $sale->id . ':created',
            'description' => 'Cost of goods sold for sales invoice ' . $sale->invoice_no,
            'posted_by' => $sale->created_by,
            'lines' => [
                ['chart_account_system_code' => 'COST_OF_GOODS_SOLD', 'description' => 'Cost of goods sold', 'debit' => $cost, 'credit' => '0.0000', 'operational_account_id' => null, 'subledger_type' => null, 'subledger_id' => null],
                ['chart_account_system_code' => 'INVENTORY', 'description' => 'Inventory issued on sale', 'debit' => '0.0000', 'credit' => $cost, 'operational_account_id' => null, 'subledger_type' => null, 'subledger_id' => null],
            ],
        ];
    }

    public function hasSnapshots(SalesInvoice $sale): bool
    {
        return $sale->exists && SalesCostSnapshot::query()
            ->where('company_id', $sale->company_id)
            ->where('sales_invoice_id', $sale->id)
            ->exists();
    }

    public function hasProductItems(SalesInvoice $sale): bool
    {
        return $sale->exists && $sale->items()
            ->where('company_id', $sale->company_id)
            ->where('item_type', 'product')
            ->exists();
    }

    private function date(mixed $value): string
    {
        if ($value instanceof DateTimeInterface) {
            $value = $value->format('Y-m-d');
        }

        if (! is_string($value) || trim($value) === '') {
            throw new InvalidArgumentException('The sale_date value must be a valid Y-m-d date.');
        }

        $value = trim($value);
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        $errors = DateTimeImmutable::getLastErrors();

        if (! $date
            || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
            || $date->format('Y-m-d') !== $value) {
            throw new InvalidArgumentException('The sale_date value must be a valid Y-m-d date.');
        }

        return $date->format('Y-m-d');
    }

    private function positiveInteger(mixed $value, string $field): int
    {
        if (filter_var($value, FILTER_VALIDATE_INT) === false || (int) $value < 1) {
            throw new InvalidArgumentException("The {$field} value must be a positive integer.");
        }

        return (int) $value;
    }

    private function amount(mixed $value, string $field): string
    {
        if (is_int($value)) {
            $value = (string) $value;
        }

        if (! is_string($value) || ! preg_match('/^\d+(?:\.\d{1,4})?$/', trim($value))) {
            throw new InvalidArgumentException("The {$field} value must be a non-negative decimal amount.");
        }

        [$whole, $fraction] = array_pad(explode('.', trim($value), 2), 2, '');

        return (ltrim($whole, '0') ?: '0') . '.' . str_pad($fraction, 4, '0');
    }

    private function isZero(string $amount): bool
    {
        return $this->scaled($amount) === '0';
    }

    private function add(string $left, string $right): string
    {
        return bcadd($left, $right, 4);
    }

    private function scaled(string $amount): string
    {
        return ltrim(str_replace('.', '', $amount), '0') ?: '0';
    }

}
