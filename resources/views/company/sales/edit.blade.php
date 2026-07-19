@extends('company.layout')

@section('content')

<div class="dg-page">

    <header class="dg-toolbar">
        <div class="container-fluid">
            <div class="row align-items-center g-2">
                <div class="col">
                    <h1 class="h4 mb-0">Edit Sales Invoice</h1>
                </div>
                <div class="col-auto">
                    <nav class="btn-group" aria-label="Sales toolbar">
                        <a href="{{ route('company.dashboard') }}" class="btn btn-outline-secondary dg-btn">Dashboard</a>
                        <a href="{{ route('company.sales.index') }}" class="btn btn-outline-secondary dg-btn">Sales List</a>
                        <a href="{{ route('company.sales.show', $invoice->id) }}" class="btn btn-outline-secondary dg-btn">View Invoice</a>
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

            @if (session('success'))
                <div class="alert alert-success dg-alert" role="alert">
                    {{ session('success') }}
                </div>
            @endif

            @php
                $paymentAccountName = '-';

                if ($invoice->relationLoaded('payments')) {
                    $paymentAccountName = $invoice->payments->first()?->account?->account_name ?? '-';
                }
            @endphp

            <form id="dgForm" method="POST" action="{{ route('company.sales.update', $invoice->id) }}">
                @csrf
                @method('PUT')

                <section class="dg-section">
                    <article class="card dg-card">
                        <header class="card-header dg-card-header">
                            <h2 class="h6 mb-0">Invoice Information</h2>
                        </header>

                        <div class="card-body dg-card-body">
                            <div class="row g-3 align-items-start">

                                <div class="col-md-2">
                                    <label for="invoice_no" class="form-label">Invoice No</label>
                                    <input type="text" id="invoice_no" class="form-control dg-input" value="{{ $invoice->invoice_no }}" readonly>
                                </div>

                                <div class="col-md-2">
                                    <label for="financial_year" class="form-label">Financial Year</label>
                                    <input type="text" id="financial_year" class="form-control dg-input" value="{{ $invoice->financialYear->name ?? '' }}" readonly>
                                </div>

                                <div class="col-md-2">
                                    <label for="sale_date" class="form-label">Sale Date</label>
                                    <input type="date" name="sale_date" id="sale_date" class="form-control dg-input" value="{{ old('sale_date', \Illuminate\Support\Carbon::parse($invoice->sale_date)->format('Y-m-d')) }}" required>
                                </div>

                                <div class="col-md-3">
                                    <label for="customer_name" class="form-label">Customer</label>
                                    <input type="text" id="customer_name" class="form-control dg-input" value="{{ $invoice->customer->name ?? '-' }}" readonly>
                                    <small class="form-text text-muted dg-note">Customer Balance: {{ number_format($invoice->customer->current_balance ?? 0, 2) }}</small>
                                </div>

                                <div class="col-md-3">
                                    <label for="payment_status" class="form-label">Payment Status</label>
                                    <input type="text" id="payment_status" class="form-control dg-input" value="{{ ucfirst($invoice->payment_status) }}" readonly>
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
                                            <th scope="col">Type</th>
                                            <th scope="col" width="25%">Product / Service</th>
                                            <th scope="col" width="8%">Quantity</th>
                                            <th scope="col" width="8%">Unit</th>
                                            <th scope="col">Unit Price</th>
                                            <th scope="col">VAT Rate</th>
                                            <th scope="col">VAT Amount</th>
                                            <th scope="col">Total Price</th>
                                        </tr>
                                    </thead>

                                    <tbody class="dg-body">
                                        @foreach ($invoice->items as $index => $item)
                                            <tr class="dg-row">
                                                <td>{{ $index + 1 }}</td>

                                                <td>
                                                    <input type="text" class="form-control dg-input" value="{{ ucfirst($item->item_type) }}" readonly aria-label="Item Type">
                                                </td>

                                                <td>
                                                    <input type="text" class="form-control dg-input" value="{{ $item->item_type === 'service' ? ($item->service->name ?? '-') : ($item->product->name ?? '-') }}" readonly aria-label="Product or Service">
                                                </td>

                                                <td>
                                                    <input type="text" class="form-control dg-input text-end" value="{{ $item->quantity }}" readonly aria-label="Quantity">
                                                </td>

                                                <td>
                                                    <input type="text" class="form-control dg-input" value="{{ $item->item_type === 'product' ? ($item->product->unit?->short_name ?? $item->product->unit?->name ?? '-') : '-' }}" readonly aria-label="Unit">
                                                </td>

                                                <td>
                                                    <input type="text" class="form-control dg-input text-end" value="{{ number_format($item->unit_price, 2) }}" readonly aria-label="Unit Price">
                                                </td>

                                                <td>
                                                    <input type="text" class="form-control dg-input text-end" value="{{ number_format($item->vat_rate, 2) }}%" readonly aria-label="VAT Rate">
                                                </td>

                                                <td>
                                                    <input type="text" class="form-control dg-input text-end" value="{{ number_format($item->vat_amount, 2) }}" readonly aria-label="VAT Amount">
                                                </td>

                                                <td>
                                                    <input type="text" class="form-control dg-input text-end" value="{{ number_format($item->total_price, 2) }}" readonly aria-label="Total Price">
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
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
                                            <label for="account_name" class="form-label">Payment Account</label>
                                            <input type="text" id="account_name" class="form-control dg-input" value="{{ $paymentAccountName }}" readonly>
                                        </div>

                                        <div class="col-md-3">
                                            <label for="paid_amount" class="form-label">Paid Amount</label>
                                            <input type="text" id="paid_amount" class="form-control dg-input text-end" value="{{ number_format($invoice->paid_amount, 2) }}" readonly>
                                        </div>

                                        <div class="col-md-3">
                                            <label for="discount_amount" class="form-label">Discount</label>
                                            <input type="text" id="discount_amount" class="form-control dg-input text-end" value="{{ number_format($invoice->discount, 2) }}" readonly>
                                        </div>

                                        <div class="col-md-12">
                                            <label for="note" class="form-label">Note</label>
                                            <textarea name="note" id="note" class="form-control dg-textarea" rows="3">{{ old('note', $invoice->note) }}</textarea>
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
                                            <input type="text" id="subtotal" class="form-control form-control-sm dg-input text-end" value="{{ number_format($invoice->subtotal, 2) }}" readonly>
                                        </div>
                                    </div>

                                    <div class="row g-2 mb-1">
                                        <div class="col-6">
                                            <label for="total_vat" class="form-label mb-0 small">Total VAT</label>
                                        </div>
                                        <div class="col-6">
                                            <input type="text" id="total_vat" class="form-control form-control-sm dg-input text-end" value="{{ number_format($invoice->total_vat, 2) }}" readonly>
                                        </div>
                                    </div>

                                    <hr class="my-1">

                                    <div class="row g-2 mb-1">
                                        <div class="col-6">
                                            <label for="grand_total" class="form-label mb-0 small fw-bold">Grand Total</label>
                                        </div>
                                        <div class="col-6">
                                            <input type="text" id="grand_total" class="form-control form-control-sm dg-input text-end fw-bold" value="{{ number_format($invoice->grand_total, 2) }}" readonly>
                                        </div>
                                    </div>

                                    <div class="row g-2 mb-1">
                                        <div class="col-6">
                                            <label for="summary_paid_amount" class="form-label mb-0 small">Paid Amount</label>
                                        </div>
                                        <div class="col-6">
                                            <input type="text" id="summary_paid_amount" class="form-control form-control-sm dg-input text-end" value="{{ number_format($invoice->paid_amount, 2) }}" readonly>
                                        </div>
                                    </div>

                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label for="due_amount" class="form-label mb-0 small fw-bold">Due Amount</label>
                                        </div>
                                        <div class="col-6">
                                            <input type="text" id="due_amount" class="form-control form-control-sm dg-input text-end fw-bold" value="{{ number_format($invoice->due_amount, 2) }}" readonly>
                                        </div>
                                    </div>

                                </div>
                            </article>
                        </div>

                    </div>

                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-primary dg-btn">Update Invoice</button>
                        <a href="{{ route('company.sales.index') }}" class="btn btn-outline-secondary dg-btn">Back</a>
                    </div>
                </section>

            </form>

        </div>
    </main>

</div>

@endsection
