@extends('company.layout')

@section('content')

<div class="dg-page">

    <header class="dg-toolbar">
        <div class="container-fluid">
            <div class="row align-items-center g-2">
                <div class="col">
                    <h1 class="h4 mb-0">Purchase Return</h1>
                    <p class="text-muted small mb-0">Return products and services against purchase invoice</p>
                </div>
                <div class="col-auto">
                    <nav class="btn-group" aria-label="Purchase return create toolbar">
                        <a href="{{ route('company.purchases.show', $invoice->id) }}" class="btn btn-outline-secondary dg-btn">Back to Invoice</a>
                        <a href="{{ route('company.purchase-return.index') }}" class="btn btn-outline-secondary dg-btn">Return List</a>
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

            <form method="POST" action="{{ route('company.purchase-return.store') }}" enctype="multipart/form-data" id="dgPurchaseReturnForm">
                @csrf

                <input type="hidden" name="purchase_invoice_id" value="{{ $invoice->id }}">
                <input type="hidden" name="supplier_id" value="{{ $invoice->supplier_id }}">

                <div class="row g-3">
                    <div class="col-lg-8">
                        <section class="dg-section">
                            <article class="card dg-card mb-3">
                                <header class="card-header dg-card-header">
                                    <h2 class="h6 mb-0">Return Details</h2>
                                </header>
                                <div class="card-body dg-card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="return_no_display" class="form-label">Return No</label>
                                            <input type="text" id="return_no_display" class="form-control dg-input" value="{{ $returnNo }}" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="return_date" class="form-label">Return Date</label>
                                            <input type="date" name="return_date" id="return_date" class="form-control dg-input" value="{{ date('Y-m-d') }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="invoice_display" class="form-label">Invoice No</label>
                                            <input type="text" id="invoice_display" class="form-control dg-input" value="{{ $invoice->invoice_no }}" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="supplier_display" class="form-label">Supplier</label>
                                            <input type="text" id="supplier_display" class="form-control dg-input" value="{{ $invoice->supplier->name ?? '-' }}" readonly>
                                        </div>
                                        <div class="col-12">
                                            <label for="damage_photo" class="form-label">Damage Photo</label>
                                            <input type="file" name="damage_photo" id="damage_photo" class="form-control dg-input" accept=".jpg,.jpeg,.png">
                                            <small class="text-muted">Optional proof image for damaged items.</small>
                                        </div>
                                    </div>
                                </div>
                            </article>

                            <article class="card dg-card">
                                <header class="card-header dg-card-header">
                                    <h2 class="h6 mb-0">Return Items</h2>
                                </header>
                                <div class="card-body dg-card-body">
                                    <div class="table-responsive">
                                        <table class="table dg-table" id="dgPurchaseReturnItems">
                                            <thead class="dg-head">
                                                <tr>
                                                    <th scope="col">Item</th>
                                                    <th scope="col" class="text-end">Available Qty</th>
                                                    <th scope="col" class="text-end">Return Qty</th>
                                                    <th scope="col" class="text-end">Unit Price</th>
                                                    <th scope="col" class="text-end">VAT %</th>
                                                    <th scope="col" class="text-end">Total</th>
                                                </tr>
                                            </thead>
                                            <tbody class="dg-body">
                                                @foreach ($invoice->items as $item)
                                                    @php
                                                        $availableQty = $availableQuantities[$item->id] ?? 0;
                                                        $itemName = $item->item_type === 'service'
                                                            ? ($item->service->name ?? '-')
                                                            : ($item->product->name ?? '-');
                                                    @endphp
                                                    <tr class="dg-row dg-return-item-row">
                                                        <td>
                                                            <strong>{{ $itemName }}</strong>
                                                            @if ($item->item_type === 'service')
                                                                <span class="badge bg-secondary ms-1">Service</span>
                                                            @endif
                                                            <input type="hidden" name="purchase_item_id[]" value="{{ $item->id }}">
                                                        </td>
                                                        <td class="text-end">
                                                            <span class="badge bg-primary">{{ number_format($availableQty, 2) }}</span>
                                                        </td>
                                                        <td class="text-end">
                                                            <input type="number"
                                                                   name="quantity[]"
                                                                   class="form-control dg-input return-qty text-end"
                                                                   min="0"
                                                                   max="{{ $availableQty }}"
                                                                   step="0.01"
                                                                   value="{{ old('quantity.' . $loop->index, 0) }}"
                                                                   data-available="{{ $availableQty }}">
                                                        </td>
                                                        <td class="text-end">
                                                            {{ number_format($item->unit_price, 2) }}
                                                            <input type="hidden" class="unit-price" value="{{ $item->unit_price }}">
                                                        </td>
                                                        <td class="text-end">
                                                            {{ number_format($item->vat_rate, 2) }}%
                                                            <input type="hidden" class="vat-rate" value="{{ $item->vat_rate }}">
                                                        </td>
                                                        <td class="text-end">
                                                            <input type="text" class="form-control dg-input row-total text-end" value="0.00" readonly>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </article>
                        </section>
                    </div>

                    <div class="col-lg-4">
                        <section class="dg-section dg-summary">
                            <article class="card dg-card">
                                <header class="card-header dg-card-header">
                                    <h2 class="h6 mb-0">Return Summary</h2>
                                </header>
                                <div class="card-body dg-card-body">
                                    <div class="mb-3">
                                        <label for="subtotal" class="form-label">Subtotal</label>
                                        <input type="text" id="subtotal" class="form-control dg-input text-end" value="0.00" readonly>
                                    </div>
                                    <div class="mb-3">
                                        <label for="totalVat" class="form-label">VAT</label>
                                        <input type="text" id="totalVat" class="form-control dg-input text-end" value="0.00" readonly>
                                    </div>
                                    <div class="mb-3">
                                        <label for="grandTotal" class="form-label">Grand Total</label>
                                        <input type="text" id="grandTotal" class="form-control dg-input text-end fw-bold" value="0.00" readonly>
                                    </div>
                                    <div class="mb-3">
                                        <label for="note" class="form-label">Note</label>
                                        <textarea name="note" id="note" class="form-control dg-input" rows="4" placeholder="Return reason or notes...">{{ old('note') }}</textarea>
                                    </div>
                                    <button type="submit" class="btn btn-danger dg-btn w-100">Save Return</button>
                                </div>
                            </article>
                        </section>
                    </div>
                </div>
            </form>

        </div>
    </main>

</div>

@endsection
