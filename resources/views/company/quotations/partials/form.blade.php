@php
    $editing = isset($quotation);
    $quotationRecord = $quotation ?? null;
    $rows = old('item_type')
        ? collect(old('item_type'))->map(fn ($type, $i) => [
            'item_type' => $type, 'product_id' => old('product_id')[$i] ?? null,
            'service_id' => old('service_id')[$i] ?? null, 'quantity' => old('quantity')[$i] ?? 1,
            'unit_price' => old('unit_price')[$i] ?? 0, 'vat_rate' => old('vat_rate')[$i] ?? 0,
            'vat_amount' => old('vat_amount')[$i] ?? 0, 'total_price' => old('total_price')[$i] ?? 0,
        ])
        : ($editing ? $quotationRecord->items : collect([['item_type' => '', 'quantity' => 1, 'unit_price' => 0, 'vat_rate' => 0, 'vat_amount' => 0, 'total_price' => 0]]));
@endphp

@if ($errors->any())<div class="alert alert-danger dg-alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@csrf
@if ($editing) @method('PUT') @endif

<section class="dg-section"><article class="card dg-card"><header class="card-header dg-card-header"><h2 class="h6 mb-0">Quotation Information</h2></header><div class="card-body dg-card-body"><div class="row g-3">
    <div class="col-md-3"><label class="form-label" for="quotation_date">Quotation Date (AD)</label><input type="date" class="form-control dg-input" id="quotation_date" name="quotation_date" value="{{ old('quotation_date', $quotationRecord?->quotation_date?->format('Y-m-d') ?? date('Y-m-d')) }}" required></div>
    @include('company.components.nepali-date-field', ['adInputId' => 'quotation_date', 'adDate' => old('quotation_date', $quotationRecord?->quotation_date ?? date('Y-m-d')), 'columnClass' => 'col-md-3'])
    <div class="col-md-3"><label class="form-label" for="valid_until">Valid Until</label><input type="date" class="form-control dg-input" id="valid_until" name="valid_until" value="{{ old('valid_until', $quotationRecord?->valid_until?->format('Y-m-d') ?? '') }}"></div>
    <div class="col-md-3"><label class="form-label" for="customer_id">Customer</label><select class="form-select dg-select" id="customer_id" name="customer_id" required><option value="">Select Customer</option>@foreach($customers as $customer)<option value="{{ $customer->id }}" @selected(old('customer_id', $quotationRecord?->customer_id) == $customer->id)>{{ $customer->name }}</option>@endforeach</select></div>
    <div class="col-md-4"><label class="form-label" for="reference_no">Reference</label><input class="form-control dg-input" id="reference_no" name="reference_no" value="{{ old('reference_no', $quotationRecord?->reference_no ?? '') }}"></div>
    <div class="col-md-8"><label class="form-label" for="note">Description / Note</label><textarea class="form-control dg-textarea" id="note" name="note">{{ old('note', $quotationRecord?->note ?? '') }}</textarea></div>
    <input type="hidden" id="price_type" value="retail">
</div></div></article></section>

<section class="dg-section"><article class="card dg-card"><header class="card-header dg-card-header"><h2 class="h6 mb-0">Quotation Lines</h2></header><div class="card-body dg-card-body"><div class="table-responsive"><table class="table dg-table"><thead><tr><th>#</th><th>Type</th><th>Product / Service</th><th>Quantity</th><th>Unit</th><th>Unit Price</th><th>VAT</th><th>VAT Amount</th><th>Total</th><th></th></tr></thead><tbody class="dg-body">
@foreach($rows as $i => $row)
@php $row = is_array($row) ? (object) $row : $row; @endphp
<tr class="dg-row"><td>{{ $i + 1 }}</td><td><select name="item_type[]" class="form-select dg-select"><option value="">Select Type</option><option value="product" @selected($row->item_type === 'product')>Product</option><option value="service" @selected($row->item_type === 'service')>Service</option></select></td>
<td><div class="dg-product-picker"><select class="form-select dg-select dg-product-select"><option value="">Select Product</option>@foreach($products as $product)<option value="{{ $product->id }}" data-unit="{{ $product->unit?->short_name ?? $product->unit?->name }}" data-retail-price="{{ $product->retail_price }}" data-wholesale-price="{{ $product->wholesale_price }}" data-stock="{{ $product->current_stock }}" data-vat-rate="{{ $product->vat?->rate }}" @selected(($row->product_id ?? null) == $product->id)>{{ $product->name }}</option>@endforeach</select></div><div class="dg-service-picker"><select class="form-select dg-select dg-service-select"><option value="">Select Service</option>@foreach($services as $service)<option value="{{ $service->id }}" data-price="{{ $service->price }}" data-vat-rate="{{ $service->vat?->rate }}" @selected(($row->service_id ?? null) == $service->id)>{{ $service->name }}</option>@endforeach</select></div><input type="hidden" name="product_id[]" class="dg-product-id" value="{{ $row->product_id ?? '' }}"><input type="hidden" name="service_id[]" class="dg-service-id" value="{{ $row->service_id ?? '' }}"></td>
<td><input type="number" name="quantity[]" class="form-control dg-input" min="1" step="0.0001" value="{{ $row->quantity ?? 1 }}" required><div class="invalid-feedback">Invalid quantity.</div></td><td><input type="text" class="form-control dg-input dg-unit-display" value="-" readonly><small class="dg-stock-note"></small></td><td><input type="number" name="unit_price[]" class="form-control dg-input" min="0" step="0.0001" value="{{ $row->unit_price ?? 0 }}" required></td>
<td><select name="vat_rate[]" class="form-select dg-select"><option value="0">No VAT</option>@foreach($vats as $vat)<option value="{{ $vat->rate }}" @selected((float)($row->vat_rate ?? 0) === (float)$vat->rate)>{{ $vat->name }} ({{ $vat->rate }}%)</option>@endforeach</select></td><td><input type="number" name="vat_amount[]" class="form-control dg-input" value="{{ $row->vat_amount ?? 0 }}" readonly></td><td><input type="number" name="total_price[]" class="form-control dg-input" value="{{ $row->total_price ?? 0 }}" readonly></td><td><button type="button" class="btn btn-sm btn-outline-danger">Delete</button></td></tr>
@endforeach
</tbody></table></div><button type="button" class="btn btn-sm btn-outline-primary dg-btn dg-add-item">+ Add Item</button></div></article></section>

<section class="dg-section"><article class="card dg-card"><div class="card-body dg-card-body"><div class="row g-3"><div class="col-md-3"><label class="form-label" for="subtotal">Subtotal</label><input id="subtotal" class="form-control dg-input" value="{{ old('subtotal', $quotationRecord?->subtotal ?? 0) }}" readonly></div><div class="col-md-3"><label class="form-label" for="total_vat">VAT</label><input id="total_vat" class="form-control dg-input" value="{{ old('total_vat', $quotationRecord?->total_vat ?? 0) }}" readonly></div><div class="col-md-3"><label class="form-label" for="discount_amount">Discount</label><input type="number" id="discount_amount" name="discount_amount" class="form-control dg-input" min="0" step="0.0001" value="{{ old('discount_amount', $quotationRecord?->discount ?? 0) }}"></div><div class="col-md-3"><label class="form-label" for="grand_total">Grand Total</label><input id="grand_total" class="form-control dg-input" value="{{ old('grand_total', $quotationRecord?->grand_total ?? 0) }}" readonly></div></div></div></article></section>

<button class="btn btn-primary dg-btn" type="submit">{{ $editing ? 'Update Draft' : 'Save Draft' }}</button>
<a href="{{ route('company.quotations.index') }}" class="btn btn-outline-secondary dg-btn">Cancel</a>

@push('scripts')<script src="{{ asset('assets/company/js/dg.js') }}?v={{ filemtime(public_path('assets/company/js/dg.js')) }}"></script>@endpush
