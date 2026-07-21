@extends('company.layout')

@section('content')

<div class="dg-page">

    <header class="dg-toolbar">
        <div class="container-fluid">
            <div class="row align-items-center g-2">
                <div class="col">
                    <h1 class="h4 mb-0">Create Purchase Invoice</h1>
                </div>
                <div class="col-auto">
                    <nav class="btn-group" aria-label="Purchase toolbar">
                        <a href="{{ route('company.dashboard') }}" class="btn btn-outline-secondary dg-btn">Dashboard</a>
                        <a href="{{ route('company.purchases.index') }}" class="btn btn-outline-secondary dg-btn">Purchase List</a>
                        <a href="{{ route('company.purchases.create') }}" class="btn btn-outline-secondary dg-btn">Refresh</a>
                        <a href="{{ route('company.suppliers.index') }}" class="btn btn-outline-secondary dg-btn">Supplier</a>
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
                $selectedSupplierBalance = '0.00';

                if (old('supplier_id')) {
                    $selectedSupplier = $suppliers->firstWhere('id', (int) old('supplier_id'));

                    if ($selectedSupplier) {
                        $selectedSupplierBalance = number_format($selectedSupplier->current_balance, 2);
                    }
                }

                $selectedAccountBalance = '0.00';

                if (old('account_id')) {
                    $selectedAccount = $accounts->firstWhere('id', (int) old('account_id'));

                    if ($selectedAccount) {
                        $selectedAccountBalance = number_format($selectedAccount->current_balance, 2);
                    }
                }
            @endphp

            <form id="dgForm" method="POST" action="{{ route('company.purchases.store') }}">
                @csrf

                <section class="dg-section">
                    <article class="card dg-card">
                        <header class="card-header dg-card-header">
                            <h2 class="h6 mb-0">Invoice Information</h2>
                        </header>

                        <div class="card-body dg-card-body">
                            <div class="row g-3 align-items-start">

                                <div class="col-md-2">
                                    <label for="invoice_no" class="form-label">Invoice No</label>
                                    <input type="text" id="invoice_no" class="form-control dg-input" value="{{ $invoiceNo }}" readonly>
                                </div>

                                <div class="col-md-2 d-none">
                                    <label for="financial_year" class="form-label">Financial Year</label>
                                    <input type="text" id="financial_year" class="form-control dg-input" value="{{ $activeFy->name ?? '' }}" readonly>
                                </div>

                                <div class="col-md-2">
                                    <label for="purchase_date" class="form-label">Purchase Date</label>
                                    <input type="date" name="purchase_date" id="purchase_date" class="form-control dg-input" value="{{ old('purchase_date', date('Y-m-d')) }}" required>
                                </div>

                                <div class="col-md-3">
                                    <label for="supplier_id" class="form-label">Supplier</label>
                                    <select name="supplier_id" id="supplier_id" class="form-select dg-select" required>
                                        <option value="">Select Supplier</option>
                                        @foreach ($suppliers as $supplier)
                                            <option value="{{ $supplier->id }}" data-balance="{{ $supplier->current_balance }}" @selected(old('supplier_id') == $supplier->id)>{{ $supplier->name }}</option>
                                        @endforeach
                                    </select>
                                    <small class="form-text text-muted dg-note">Supplier Balance: {{ $selectedSupplierBalance }}</small>
                                </div>

                                <div class="col-md-5">
                                    <label for="barcode" class="form-label">Barcode</label>
                                    <input type="text" name="barcode" id="barcode" class="form-control dg-input" placeholder="Scan or enter barcode">
                                </div>

                            </div>
                        </div>
                    </article>
                </section>

                <section class="dg-section">
                    <article class="card dg-card">
                        <header class="card-header dg-card-header">
                            <h2 class="h6 mb-0">Items</h2>
                        </header>

                        <div class="card-body dg-card-body">
                            <div class="table-responsive">
                                <table class="table dg-table">
                                    <thead class="dg-head">
                                        <tr>
                                            <th scope="col">#</th>
                                            <th scope="col" width="30%">Product / Service</th>
                                            <th scope="col" width="8%">Quantity</th>
                                            <th scope="col" width="8%">Unit</th>
                                            <th scope="col">Unit Cost</th>
                                            <th scope="col">VAT Rate</th>
                                            <th scope="col">VAT Amount</th>
                                            <th scope="col">Line Total</th>
                                            <th scope="col" class="dg-action-col-compact d-print-none">Delete</th>
                                        </tr>
                                    </thead>

                                    <tbody class="dg-body">
                                        <tr class="dg-row">
                                            <td>1</td>

                                            <td>
                                                <label class="form-label visually-hidden">Product or Service</label>
                                                <select class="form-select dg-select dg-item-select" aria-label="Product or Service">
                                                    <option value="">Select Item</option>
                                                    <optgroup label="Products">
                                                        @foreach ($products as $product)
                                                            <option
                                                                value="product:{{ $product->id }}"
                                                                data-item-type="product"
                                                                data-product-id="{{ $product->id }}"
                                                                data-unit="{{ $product->unit?->short_name ?? $product->unit?->name }}"
                                                                data-cost-price="{{ $product->cost_price }}"
                                                                data-stock="{{ $product->current_stock }}"
                                                                data-barcode="{{ $product->barcode }}"
                                                                data-vat-rate="{{ $product->vat?->rate }}"
                                                            >{{ $product->name }}</option>
                                                        @endforeach
                                                    </optgroup>
                                                    <optgroup label="Services">
                                                        @foreach ($services as $service)
                                                            <option
                                                                value="service:{{ $service->id }}"
                                                                data-item-type="service"
                                                                data-service-id="{{ $service->id }}"
                                                                data-price="{{ $service->price }}"
                                                                data-vat-rate="{{ $service->vat?->rate }}"
                                                            >{{ $service->name }}</option>
                                                        @endforeach
                                                    </optgroup>
                                                </select>
                                                <input type="hidden" name="item_type[]" class="dg-item-type" value="product">
                                                <input type="hidden" name="product_id[]" class="dg-product-id" value="">
                                                <input type="hidden" name="service_id[]" class="dg-service-id" value="">
                                            </td>

                                            <td>
                                                <label class="form-label visually-hidden">Quantity</label>
                                                <input type="number" name="quantity[]" class="form-control dg-input" min="1" step="1" aria-label="Quantity">
                                            </td>

                                            <td>
                                                <label class="form-label visually-hidden">Unit</label>
                                                <input type="text" class="form-control dg-input dg-unit-display" value="-" readonly aria-label="Unit">
                                                <small class="form-text text-muted dg-note dg-stock-note"></small>
                                            </td>

                                            <td>
                                                <label class="form-label visually-hidden">Unit Cost</label>
                                                <input type="number" name="unit_price[]" class="form-control dg-input" min="0" step="0.01" aria-label="Unit Cost">
                                            </td>

                                            <td>
                                                <label class="form-label visually-hidden">VAT Rate</label>
                                                <select name="vat_rate[]" class="form-select dg-select" aria-label="VAT Rate">
                                                    <option value="0">No VAT</option>
                                                    @foreach ($vats as $vat)
                                                        <option value="{{ $vat->rate }}">{{ $vat->name }} ({{ $vat->rate }}%)</option>
                                                    @endforeach
                                                </select>
                                            </td>

                                            <td>
                                                <label class="form-label visually-hidden">VAT Amount</label>
                                                <input type="number" name="vat_amount[]" class="form-control dg-input" min="0" step="0.01" value="0" aria-label="VAT Amount">
                                            </td>

                                            <td>
                                                <label class="form-label visually-hidden">Line Total</label>
                                                <input type="number" name="total_price[]" class="form-control dg-input" min="0" step="0.01" value="0" aria-label="Line Total">
                                            </td>

                                            <td class="dg-action-col-compact d-print-none">
                                                <div class="dg-action-group" role="group" aria-label="Delete row 1">
                                                    <button type="button" class="btn btn-sm btn-outline-danger dg-action-btn" aria-label="Delete row 1">Delete</button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <button type="button" class="btn btn-outline-primary btn-sm dg-btn dg-add-item mt-2">+ Add Item</button>
                        </div>
                    </article>
                </section>

                <section class="dg-section">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <article class="card dg-card dg-payment">
                                <header class="card-header dg-card-header">
                                    <h2 class="h6 mb-0">Payment Information</h2>
                                </header>

                                <div class="card-body dg-card-body">
                                    <div class="row g-3">

                                        <div class="col-md-6">
                                            <label for="account_id" class="form-label">Payment Account</label>
                                            <select name="account_id" id="account_id" class="form-select dg-select">
                                                <option value="">Select Account</option>
                                                @forelse ($accounts as $account)
                                                    <option value="{{ $account->id }}" data-balance="{{ $account->current_balance }}" @selected(old('account_id') == $account->id)>{{ $account->account_name }}</option>
                                                @empty
                                                    <option value="" disabled>No active accounts found</option>
                                                @endforelse
                                            </select>
                                            <small class="form-text text-muted dg-note">Account Balance: {{ $selectedAccountBalance }}</small>
                                        </div>

                                        <div class="col-md-3">
                                            <label for="paid_amount" class="form-label">Paid Amount</label>
                                            <input type="number" name="paid_amount" id="paid_amount" class="form-control dg-input" min="0" step="0.01" value="{{ old('paid_amount', 0) }}">
                                        </div>

                                        <div class="col-md-3">
                                            <label for="discount_amount" class="form-label">Discount</label>
                                            <input type="number" name="discount_amount" id="discount_amount" class="form-control dg-input" min="0" step="0.01" value="{{ number_format(old('discount_amount', 0), 2) }}">
                                        </div>

                                        <div class="col-md-12">
                                            <label for="note" class="form-label">Note</label>
                                            <textarea name="note" id="note" class="form-control dg-textarea" rows="3">{{ old('note') }}</textarea>
                                        </div>

                                    </div>
                                </div>
                            </article>
                        </div>

                        <div class="col-md-6">
                            <article class="card dg-card">
                                <header class="card-header dg-card-header">
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

                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-primary dg-btn">Save Invoice</button>
                        <a href="{{ route('company.purchases.index') }}" class="btn btn-outline-secondary dg-btn">Cancel</a>
                    </div>
                </section>

            </form>

        </div>
    </main>

</div>
@endsection
