<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\Service;
use RuntimeException;

class SalesFiscalSnapshotService
{
    public function __construct(private readonly NepalIrdCbmsModeService $mode)
    {
    }

    public function invoiceAttributes(Company $company, Customer $customer): array
    {
        if (! $this->mode->isActiveForCompany($company)) return [];

        return [
            'fiscal_snapshot_captured_at' => now(),
            'seller_name_snapshot' => $company->company_name,
            'seller_address_snapshot' => $this->joinedAddress($company->address, $company->address_line_2),
            'seller_pan_snapshot' => $company->pan_number,
            'seller_vat_snapshot' => $company->vat_number,
            'buyer_name_snapshot' => $customer->name,
            'buyer_address_snapshot' => $customer->address,
            'buyer_tax_no_snapshot' => $customer->tax_no,
        ];
    }

    public function itemAttributes(string $type, ?int $productId, ?int $serviceId, int $companyId, bool $required): array
    {
        if (! $required) return [];

        if ($type === 'product') {
            $product = Product::with(['unit', 'brand'])->where('company_id', $companyId)->findOrFail($productId);
            $origin = $product->origin_type;

            if (! in_array($origin, ['domestic', 'imported'], true)) {
                throw new RuntimeException("This product's origin is not classified as Domestic or Imported, so fiscal H.S.-code applicability cannot be verified.");
            }
            if ($origin === 'imported' && ! $this->validHsCode($product->hs_code)) {
                throw new RuntimeException('This imported product requires a valid H.S. Code before issuing a Nepal CBMS invoice.');
            }
            if ($product->brand && (int) $product->brand->company_id !== $companyId) {
                throw new RuntimeException('The selected product has invalid company-owned brand information.');
            }

            return [
                'item_name_snapshot' => $product->name,
                'unit_name_snapshot' => $product->unit?->short_name ?? $product->unit?->name ?? 'Unit',
                'fiscal_origin_type' => $origin,
                'fiscal_hs_code' => $product->hs_code,
                'fiscal_brand_name' => $product->brand?->name,
                'fiscal_product_type' => $product->product_type,
                'fiscal_model' => $product->model,
                'fiscal_size' => $product->size,
            ];
        }

        $service = Service::where('company_id', $companyId)->findOrFail($serviceId);
        return ['item_name_snapshot' => $service->name, 'unit_name_snapshot' => 'Service'];
    }

    public function isComplete(SalesInvoice $invoice): bool
    {
        if ($invoice->fiscal_snapshot_captured_at === null
            || empty($invoice->seller_name_snapshot)
            || empty($invoice->buyer_name_snapshot)) return false;

        $invoice->loadMissing('items');
        return $invoice->items->isNotEmpty()
            && $invoice->items->every(fn ($item) => ! empty($item->item_name_snapshot));
    }

    public function itemDetailComplianceErrors(SalesInvoice $invoice): array
    {
        $invoice->loadMissing('items');
        $errors = [];

        foreach ($invoice->items->where('item_type', 'product') as $item) {
            if (! in_array($item->fiscal_origin_type, ['domestic', 'imported'], true)) {
                $errors[] = 'One or more physical sales lines have no authoritative fiscal origin snapshot.';
                continue;
            }
            if ($item->fiscal_origin_type === 'imported' && ! $this->validHsCode($item->fiscal_hs_code)) {
                $errors[] = 'One or more imported sales lines have no valid immutable H.S. Code snapshot.';
            }
        }

        return array_values(array_unique($errors));
    }

    private function validHsCode(?string $hsCode): bool
    {
        return is_string($hsCode) && preg_match('/^\d{4,20}$/', $hsCode) === 1;
    }

    private function joinedAddress(?string $first, ?string $second): ?string
    {
        $parts = array_values(array_filter([trim((string) $first), trim((string) $second)]));
        return $parts === [] ? null : implode(', ', $parts);
    }
}
