@extends('company.layout')

@section('content')

<div class="dg-page">

    <header class="dg-toolbar py-2">
        <div class="container-fluid">
            <div class="row align-items-center g-2">
                <div class="col">
                    <h1 class="h4 mb-0">Create Sales Invoice</h1>
                </div>
                <div class="col-auto">
                    <nav class="btn-group" aria-label="Sales toolbar">
                        <a href="{{ route('company.dashboard') }}" class="btn btn-outline-secondary dg-btn">Dashboard</a>
                        <a href="{{ route('company.sales.index') }}" class="btn btn-outline-secondary dg-btn">Sales List</a>
                        <a href="{{ route('company.sales.create') }}" class="btn btn-outline-secondary dg-btn">Refresh</a>
                        <a href="{{ route('company.customers.index') }}" class="btn btn-outline-secondary dg-btn">Customer</a>
                        <a href="{{ route('company.products.index') }}" class="btn btn-outline-secondary dg-btn">Product</a>
                        <a href="{{ route('company.services.index') }}" class="btn btn-outline-secondary dg-btn">Service</a>
                        <a href="{{ route('company.service-categories.index') }}" class="btn btn-outline-secondary dg-btn">Service Category</a>
                        <a href="{{ route('company.units.index') }}" class="btn btn-outline-secondary dg-btn">Unit</a>
                        <a href="{{ route('company.categories.index') }}" class="btn btn-outline-secondary dg-btn">Category</a>
                    </nav>
                </div>
            </div>
        </div>
    </header>

    <main class="dg-container">
        <div class="container-fluid">

            @if ($errors->any())
                <div class="alert alert-danger dg-alert" role="alert">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger dg-alert" role="alert">
                    {{ session('error') }}
                </div>
            @endif

            @php
                $selectedCustomerBalance = '0.00';

                if (old('customer_id')) {
                    $selectedCustomer = $customers->firstWhere('id', (int) old('customer_id'));

                    if ($selectedCustomer) {
                        $selectedCustomerBalance = number_format($selectedCustomer->current_balance, 2);
                    }
                }

                $selectedAccountBalance = '0.00';
                $columnClass = 'col-xl-2 col-md-4 col-sm-6';

                if (old('account_id')) {
                    $selectedAccount = $accounts->firstWhere('id', (int) old('account_id'));

                    if ($selectedAccount) {
                        $selectedAccountBalance = number_format($selectedAccount->current_balance, 2);
                    }
                }

                $oldItemFields = [
                    'item_type', 'product_id', 'service_id', 'quantity', 'unit_price',
                    'vat_rate', 'tax_classification', 'vat_amount', 'total_price',
                    'line_discount_amount',
                ];
                $oldItemRowCount = collect($oldItemFields)
                    ->map(fn ($field) => count((array) old($field, [])))
                    ->max() ?: 0;
                $salesRowCount = $oldItemRowCount > 0 ? $oldItemRowCount : 1;
            @endphp

            <form id="dgForm" method="POST" action="{{ route('company.sales.store') }}" data-fiscal-line-discount="{{ $isFiscalDiscountMode ? '1' : '0' }}">
                @csrf

                <section class="dg-section mb-2">
                    <article class="card dg-card">
                        <header class="card-header dg-card-header py-2 px-3">
                            <h2 class="h6 mb-0">Invoice Information</h2>
                        </header>

                        <div class="card-body dg-card-body p-3">
                            <div class="row g-2 align-items-start">

                                <div class="col-xl-2 col-md-4 col-sm-6">
                                    <label for="invoice_no" class="form-label small fw-semibold mb-1">Invoice No</label>
                                    <input type="text" id="invoice_no" class="form-control form-control-sm dg-input" value="{{ $invoiceNo }}" readonly>
                                </div>

                                <div class="col-md-2 d-none">
                                    <label for="financial_year" class="form-label">Financial Year</label>
                                    <input type="text" id="financial_year" class="form-control dg-input" value="{{ $activeFy->name ?? '' }}" readonly>
                                </div>

                                <div class="col-xl-2 col-md-4 col-sm-6">
                                    <label for="sale_date" class="form-label small fw-semibold mb-1">Sale Date</label>
                                    <input type="date" name="sale_date" id="sale_date" class="form-control form-control-sm dg-input" value="{{ old('sale_date', date('Y-m-d')) }}" required>
                                </div>

                                @include('company.components.nepali-date-field')

                                <div class="col-xl-2 col-md-4 col-sm-6">
                                    <label for="customer_id" class="form-label small fw-semibold mb-1">Customer</label>
                                    <select name="customer_id" id="customer_id" class="form-select form-select-sm dg-select" required>
                                        <option value="">Select Customer</option>
                                        @foreach ($customers as $customer)
                                            <option value="{{ $customer->id }}" data-balance="{{ $customer->current_balance }}" @selected(old('customer_id') == $customer->id)>{{ $customer->name }}</option>
                                        @endforeach
                                    </select>
                                    <small class="form-text text-muted dg-note d-block mt-1 lh-sm">Customer Balance: {{ $selectedCustomerBalance }}</small>
                                </div>

                                <div class="col-xl-2 col-md-4 col-sm-6">
                                    <label for="price_type" class="form-label small fw-semibold mb-1">Price Type</label>
                                    <select name="price_type" id="price_type" class="form-select form-select-sm dg-select">
                                        <option value="retail" @selected(old('price_type', 'retail') == 'retail')>Retail</option>
                                        <option value="wholesale" @selected(old('price_type') == 'wholesale')>Wholesale</option>
                                    </select>
                                </div>

                                <div class="col-xl-2 col-md-4 col-sm-6">
                                    <label for="barcode" class="form-label small fw-semibold mb-1">Barcode</label>
                                    <input type="text" name="barcode" id="barcode" class="form-control form-control-sm dg-input" value="{{ old('barcode') }}" placeholder="Scan or enter barcode">
                                </div>

                            </div>
                        </div>
                    </article>
                </section>

                <section class="dg-section mb-2">
                    <article class="card dg-card">
                        <header class="card-header dg-card-header py-2 px-3">
                            <h2 class="h6 mb-0">Items</h2>
                        </header>

                        <div class="card-body dg-card-body p-2">
                            <div class="table-responsive dg-sales-items-table">
                                <table class="table table-sm align-middle mb-0 dg-table dg-table-compact">
                                    <thead class="dg-head">
                                        <tr>
                                            <th scope="col">#</th>
                                            <th scope="col" width="25%">Product / Service</th>
                                            <th scope="col" width="8%">Quantity</th>
                                            <th scope="col" width="8%">Unit</th>
                                            <th scope="col">Unit Price</th>
                                            @if ($isFiscalDiscountMode)
                                                <th scope="col">Line Discount</th>
                                            @endif
                                            <th scope="col">VAT Rate</th>
                                            <th scope="col">VAT Amount</th>
                                            <th scope="col">Total Price</th>
                                            <th scope="col" class="dg-action-col-compact d-print-none">Delete</th>
                                        </tr>
                                    </thead>

                                    <tbody class="dg-body">
                                        @for ($rowIndex = 0; $rowIndex < $salesRowCount; $rowIndex++)
                                        @php
                                            $rowType = old("item_type.$rowIndex", '');
                                            $rowProductId = old("product_id.$rowIndex");
                                            $rowServiceId = old("service_id.$rowIndex");
                                            $rowProduct = $rowProductId ? $products->firstWhere('id', (int) $rowProductId) : null;
                                            $rowService = $rowServiceId ? $services->firstWhere('id', (int) $rowServiceId) : null;
                                            $rowSelectedName = $rowType === 'service' ? ($rowService?->name ?? '') : ($rowProduct?->name ?? '');
                                            $rowUnit = $rowType === 'service'
                                                ? ($rowService ? 'Service' : '-')
                                                : ($rowProduct?->unit?->short_name ?? $rowProduct?->unit?->name ?? '-');
                                        @endphp
                                        <tr class="dg-row">
                                            <td>{{ $rowIndex + 1 }}</td>

                                            <td>
                                                <label class="form-label visually-hidden">Product or Service</label>
                                                <div class="dg-item-combobox">
                                                    <input type="search" class="form-control form-control-sm dg-input dg-item-combobox-input @error("product_id.$rowIndex") is-invalid @enderror @error("service_id.$rowIndex") is-invalid @enderror" value="{{ $rowSelectedName }}" placeholder="Select Product / Service" aria-label="Product or Service" aria-autocomplete="list" aria-expanded="false" autocomplete="off">
                                                    <div class="dg-item-combobox-menu" role="listbox" hidden></div>
                                                </div>
                                                <select class="dg-sales-item-select d-none" aria-hidden="true" tabindex="-1">
                                                    <option value="">Select Item</option>
                                                    <optgroup label="Products">
                                                        @foreach ($products as $product)
                                                            <option value="product:{{ $product->id }}" data-item-type="product" data-item-id="{{ $product->id }}" @selected($rowType === 'product' && (int) $rowProductId === (int) $product->id)>{{ $product->name }}</option>
                                                        @endforeach
                                                    </optgroup>
                                                    <optgroup label="Services">
                                                        @foreach ($services as $service)
                                                            <option value="service:{{ $service->id }}" data-item-type="service" data-item-id="{{ $service->id }}" @selected($rowType === 'service' && (int) $rowServiceId === (int) $service->id)>{{ $service->name }}</option>
                                                        @endforeach
                                                    </optgroup>
                                                </select>
                                                <select name="item_type[]" class="d-none dg-sales-item-type" aria-hidden="true" tabindex="-1">
                                                    <option value="">Select Type</option>
                                                    <option value="product" @selected($rowType === 'product')>Product</option>
                                                    <option value="service" @selected($rowType === 'service')>Service</option>
                                                </select>
                                                <div class="dg-product-picker d-none">
                                                    <select class="dg-product-select" aria-hidden="true" tabindex="-1">
                                                        <option value="">Select Product</option>
                                                        @foreach ($products as $product)
                                                            <option value="{{ $product->id }}" data-unit="{{ $product->unit?->short_name ?? $product->unit?->name }}" data-retail-price="{{ $product->retail_price }}" data-wholesale-price="{{ $product->wholesale_price }}" data-stock="{{ $product->current_stock }}" data-barcode="{{ $product->barcode }}" data-vat-rate="{{ $product->vat?->rate }}" @selected($rowType === 'product' && (int) $rowProductId === (int) $product->id)>{{ $product->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="dg-service-picker d-none">
                                                    <select class="dg-service-select" aria-hidden="true" tabindex="-1">
                                                        <option value="">Select Service</option>
                                                        @foreach ($services as $service)
                                                            <option value="{{ $service->id }}" data-price="{{ $service->price }}" data-vat-rate="{{ $service->vat?->rate }}" @selected($rowType === 'service' && (int) $rowServiceId === (int) $service->id)>{{ $service->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <input type="hidden" name="product_id[]" class="dg-product-id" value="{{ $rowProductId }}">
                                                <input type="hidden" name="service_id[]" class="dg-service-id" value="{{ $rowServiceId }}">
                                            </td>

                                            <td>
                                                <label class="form-label visually-hidden">Quantity</label>
                                                <input type="number" name="quantity[]" class="form-control form-control-sm dg-input @error("quantity.$rowIndex") is-invalid @enderror" min="1" step="1" value="{{ old("quantity.$rowIndex") }}" aria-label="Quantity">
                                                <div class="invalid-feedback">Quantity exceeds available stock.</div>
                                            </td>

                                            <td>
                                                <label class="form-label visually-hidden">Unit</label>
                                                <input type="text" class="form-control form-control-sm dg-input dg-unit-display" value="{{ $rowUnit }}" readonly aria-label="Unit">
                                                <small class="form-text text-muted dg-note dg-stock-note">{{ $rowProduct ? 'Stock: '.$rowProduct->current_stock : '' }}</small>
                                            </td>

                                            <td>
                                                <label class="form-label visually-hidden">Unit Price</label>
                                                <input type="number" name="unit_price[]" class="form-control form-control-sm dg-input @error("unit_price.$rowIndex") is-invalid @enderror" min="0" step="0.01" value="{{ old("unit_price.$rowIndex") }}" aria-label="Unit Price">
                                            </td>

                                            @if ($isFiscalDiscountMode)
                                                <td>
                                                    <label class="form-label visually-hidden">Line Discount</label>
                                                    <input type="number" name="line_discount_amount[]" class="form-control form-control-sm dg-input @error("line_discount_amount.$rowIndex") is-invalid @enderror" min="0" step="0.01" value="{{ old("line_discount_amount.$rowIndex", 0) }}" aria-label="Line Discount">
                                                </td>
                                            @endif

                                            <td>
                                                <label class="form-label visually-hidden">VAT Rate</label>
                                                @if ($isNepalCompany)
                                                    <label class="form-label small mb-1">Tax Classification</label>
                                                    <select name="tax_classification[]" class="form-select form-select-sm dg-select dg-tax-classification mb-1" aria-label="Tax Classification" required>
                                                        <option value="">Select classification</option>
                                                        <option value="vat_taxable" @selected(old("tax_classification.$rowIndex") === 'vat_taxable')>VAT Taxable</option>
                                                        <option value="vat_exempt" @selected(old("tax_classification.$rowIndex") === 'vat_exempt')>VAT Exempt</option>
                                                        <option value="zero_rated" @selected(old("tax_classification.$rowIndex") === 'zero_rated')>Zero Rated</option>
                                                        <option value="export" @selected(old("tax_classification.$rowIndex") === 'export')>Export</option>
                                                        <option value="out_of_scope" @selected(old("tax_classification.$rowIndex") === 'out_of_scope')>Out of Scope</option>
                                                    </select>
                                                @endif
                                                <select name="vat_rate[]" class="form-select form-select-sm dg-select" aria-label="VAT Rate">
                                                    <option value="0" @selected((string) old("vat_rate.$rowIndex", '0') === '0')>No VAT</option>
                                                    @foreach ($vats as $vat)
                                                        <option value="{{ $vat->rate }}" @selected((float) old("vat_rate.$rowIndex", 0) === (float) $vat->rate)>{{ $vat->name }} ({{ $vat->rate }}%)</option>
                                                    @endforeach
                                                </select>
                                            </td>

                                            <td>
                                                <label class="form-label visually-hidden">VAT Amount</label>
                                                <input type="number" name="vat_amount[]" class="form-control form-control-sm dg-input" min="0" step="0.01" value="{{ old("vat_amount.$rowIndex", 0) }}" aria-label="VAT Amount">
                                            </td>

                                            <td>
                                                <label class="form-label visually-hidden">Total Price</label>
                                                <input type="number" name="total_price[]" class="form-control form-control-sm dg-input" min="0" step="0.01" value="{{ old("total_price.$rowIndex", 0) }}" aria-label="Total Price">
                                            </td>

                                            <td class="dg-action-col-compact d-print-none">
                                                <div class="dg-action-group" role="group" aria-label="Delete row 1">
                                                    <button type="button" class="btn btn-sm btn-outline-danger dg-action-btn" aria-label="Delete row 1">Delete</button>
                                                </div>
                                            </td>
                                        </tr>
                                        @endfor
                                    </tbody>
                                </table>
                            </div>

                            <button type="button" class="btn btn-outline-primary btn-sm dg-btn dg-add-item mt-1">+ Add Item</button>
                        </div>
                    </article>
                </section>

                <section class="dg-section mb-2">
                    <div class="row g-2">

                        <div class="col-md-6">
                            <article class="card dg-card dg-payment h-100">
                                <header class="card-header dg-card-header py-2 px-3">
                                    <h2 class="h6 mb-0">Payment Information</h2>
                                </header>

                                <div class="card-body dg-card-body p-3">
                                    <div class="row g-2">

                                        <div class="col-md-6">
                                            <label for="account_id" class="form-label small fw-semibold mb-1">Payment Account</label>
                                            <select name="account_id" id="account_id" class="form-select form-select-sm dg-select">
                                                <option value="">Select Account</option>
                                                @forelse ($accounts as $account)
                                                    <option value="{{ $account->id }}" data-balance="{{ $account->current_balance }}" @selected(old('account_id') == $account->id)>{{ $account->account_name }}</option>
                                                @empty
                                                    <option value="" disabled>No active accounts found</option>
                                                @endforelse
                                            </select>
                                            <small class="form-text text-muted dg-note d-block mt-1 lh-sm">Account Balance: {{ $selectedAccountBalance }}</small>
                                        </div>

                                        <div class="col-md-3">
                                            <label for="paid_amount" class="form-label small fw-semibold mb-1">Paid Amount</label>
                                            <input type="number" name="paid_amount" id="paid_amount" class="form-control form-control-sm dg-input" min="0" step="0.01" value="{{ old('paid_amount', 0) }}">
                                        </div>

                                        <div class="col-md-3">
                                            <label for="discount_amount" class="form-label small fw-semibold mb-1">Discount</label>
                                            <input type="number" name="discount_amount" id="discount_amount" class="form-control form-control-sm dg-input" min="0" step="0.01" value="{{ number_format(old('discount_amount', 0), 2) }}" @readonly($isFiscalDiscountMode)>
                                            @if ($isFiscalDiscountMode)
                                                <small class="form-text text-muted">Calculated from line discounts.</small>
                                            @endif
                                        </div>

                                        <div class="col-md-12">
                                            <label for="note" class="form-label small fw-semibold mb-1">Note</label>
                                            <textarea name="note" id="note" class="form-control form-control-sm dg-textarea" rows="2">{{ old('note') }}</textarea>
                                        </div>

                                    </div>
                                </div>
                            </article>
                        </div>

                        <div class="col-md-6">
                            <article class="card dg-card h-100">
                                <header class="card-header dg-card-header py-2 px-3">
                                    <h2 class="h6 mb-0">Summary</h2>
                                </header>
                                <div class="card-body dg-card-body dg-summary py-2">

                                    <div class="row g-2 mb-1">
                                        <div class="col-6">
                                            <label for="subtotal" class="form-label mb-0 small">Subtotal</label>
                                        </div>
                                        <div class="col-6">
                                            <input type="number" name="subtotal" id="subtotal" class="form-control form-control-sm dg-input text-end" min="0" step="0.01" value="{{ number_format(old('subtotal', 0), 2) }}" readonly>
                                        </div>
                                    </div>

                                    <div class="row g-2 mb-1">
                                        <div class="col-6">
                                            <label for="taxable_amount" class="form-label mb-0 small">Taxable Amount</label>
                                        </div>
                                        <div class="col-6">
                                            <input type="number" id="taxable_amount" class="form-control form-control-sm dg-input text-end" min="0" step="0.01" value="0.00" readonly>
                                        </div>
                                    </div>

                                    <div class="row g-2 mb-1">
                                        <div class="col-6">
                                            <label for="total_vat" class="form-label mb-0 small">Total VAT</label>
                                        </div>
                                        <div class="col-6">
                                            <input type="number" name="total_vat" id="total_vat" class="form-control form-control-sm dg-input text-end" min="0" step="0.01" value="{{ number_format(old('total_vat', 0), 2) }}" readonly>
                                        </div>
                                    </div>

                                    <hr class="my-1">

                                    <div class="row g-2 mb-1">
                                        <div class="col-6">
                                            <label for="grand_total" class="form-label mb-0 small fw-bold">Grand Total</label>
                                        </div>
                                        <div class="col-6">
                                            <input type="number" name="grand_total" id="grand_total" class="form-control form-control-sm dg-input text-end fw-bold" min="0" step="0.01" value="{{ number_format(old('grand_total', 0), 2) }}" readonly>
                                        </div>
                                    </div>

                                    <div class="row g-2 mb-1">
                                        <div class="col-6">
                                            <label for="summary_paid_amount" class="form-label mb-0 small">Paid Amount</label>
                                        </div>
                                        <div class="col-6">
                                            <input type="number" id="summary_paid_amount" class="form-control form-control-sm dg-input text-end" min="0" step="0.01" value="0.00" readonly>
                                        </div>
                                    </div>

                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label for="due_amount" class="form-label mb-0 small fw-bold">Due Amount</label>
                                        </div>
                                        <div class="col-6">
                                            <input type="number" id="due_amount" class="form-control form-control-sm dg-input text-end fw-bold" min="0" step="0.01" value="0.00" readonly>
                                        </div>
                                    </div>

                                </div>
                            </article>
                        </div>

                    </div>

                    <div class="d-flex gap-2 mt-2">
                        <button type="submit" class="btn btn-primary dg-btn">Save Invoice</button>
                        <a href="{{ route('company.sales.index') }}" class="btn btn-outline-secondary dg-btn">Cancel</a>
                    </div>
                </section>

            </form>

        </div>
    </main>

</div>
@endsection
