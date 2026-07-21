@extends('company.layout')

@section('title', 'Purchase Invoice')

@section('content')

<div class="dg-page dg-invoice">

    <header class="dg-toolbar dg-invoice-toolbar d-print-none">
        <div class="container-fluid">
            <div class="d-flex flex-nowrap align-items-center justify-content-end gap-2">
                <nav class="btn-group" aria-label="Purchase invoice toolbar">
                    <a href="{{ route('company.purchases.index') }}" class="btn btn-outline-secondary dg-btn">Back</a>
                    <a href="{{ route('company.purchases.print', $invoice->id) }}" target="_blank" class="btn btn-outline-secondary dg-btn">Print</a>
                    @if ($invoice->status == 1)
                        <a href="{{ route('company.purchases.edit', $invoice->id) }}" class="btn btn-outline-primary dg-btn">Edit</a>
                    @endif
                    @if ($invoice->due_amount > 0)
                        <a href="{{ route('company.purchase-payments.create', $invoice->id) }}" class="btn btn-outline-success dg-btn">Payment</a>
                    @endif
                    @if (
                        (int) $invoice->status === 1
                        && (float) $invoice->paid_amount <= 0
                        && $invoice->items->contains(fn ($item) => (float) $item->quantity > (float) $item->returned_qty)
                    )
                        <a href="{{ route('company.purchase-return.create', $invoice->id) }}" class="btn btn-outline-warning dg-btn">Return</a>
                    @endif
                </nav>
            </div>
        </div>
    </header>

    <main class="dg-container">
        <div class="container-fluid">

            @if (session('success'))
                <div class="alert alert-success dg-alert d-print-none" role="alert">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger dg-alert d-print-none" role="alert">
                    {{ session('error') }}
                </div>
            @endif

            <article class="dg-invoice-sheet dg-print">

                <h1 class="dg-invoice-doc-title">PURCHASE INVOICE</h1>

                <div class="dg-invoice-parties">
                    <section class="dg-invoice-party dg-invoice-party-company">
                        <h2 class="dg-invoice-party-title">Company Information</h2>

                        <div class="dg-invoice-company-block">
                            @if ($invoice->company?->logo_path)
                                <img
                                    src="{{ asset('companies/' . $invoice->company->id . '/' . $invoice->company->logo_path) }}"
                                    alt="{{ $invoice->company->company_name ?? 'Company' }}"
                                    class="dg-invoice-logo">
                            @endif

                            <div class="dg-invoice-field-list">
                                @if (!empty($invoice->company?->company_name))
                                    <div class="dg-invoice-field-row">
                                        <span class="dg-invoice-field-label">Company Name</span>
                                        <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                        <span class="dg-invoice-field-value dg-invoice-company-name">{{ $invoice->company->company_name }}</span>
                                    </div>
                                @endif

                                @if (!empty($invoice->company?->address))
                                    <div class="dg-invoice-field-row">
                                        <span class="dg-invoice-field-label">Address</span>
                                        <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                        <span class="dg-invoice-field-value">{{ $invoice->company->address }}</span>
                                    </div>
                                @endif

                                @if (!empty($invoice->company?->address_line_2))
                                    <div class="dg-invoice-field-row">
                                        <span class="dg-invoice-field-label">Address Line 2</span>
                                        <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                        <span class="dg-invoice-field-value">{{ $invoice->company->address_line_2 }}</span>
                                    </div>
                                @endif

                                @if (!empty($invoice->company?->mobile))
                                    <div class="dg-invoice-field-row">
                                        <span class="dg-invoice-field-label">Phone</span>
                                        <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                        <span class="dg-invoice-field-value">{{ $invoice->company->mobile }}</span>
                                    </div>
                                @elseif (!empty($invoice->company?->telephone))
                                    <div class="dg-invoice-field-row">
                                        <span class="dg-invoice-field-label">Phone</span>
                                        <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                        <span class="dg-invoice-field-value">{{ $invoice->company->telephone }}</span>
                                    </div>
                                @endif

                                @if (!empty($invoice->company?->email))
                                    <div class="dg-invoice-field-row">
                                        <span class="dg-invoice-field-label">Email</span>
                                        <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                        <span class="dg-invoice-field-value">{{ $invoice->company->email }}</span>
                                    </div>
                                @endif

                                @if (!empty($invoice->company?->vat_number))
                                    <div class="dg-invoice-field-row">
                                        <span class="dg-invoice-field-label">VAT No</span>
                                        <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                        <span class="dg-invoice-field-value">{{ $invoice->company->vat_number }}</span>
                                    </div>
                                @endif

                                @if (!empty($invoice->company?->pan_number))
                                    <div class="dg-invoice-field-row">
                                        <span class="dg-invoice-field-label">PAN No</span>
                                        <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                        <span class="dg-invoice-field-value">{{ $invoice->company->pan_number }}</span>
                                    </div>
                                @endif

                                @if (!empty($invoice->company?->website))
                                    <div class="dg-invoice-field-row">
                                        <span class="dg-invoice-field-label">Website</span>
                                        <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                        <span class="dg-invoice-field-value">{{ $invoice->company->website }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </section>

                    <section class="dg-invoice-party dg-invoice-party-customer">
                        <h2 class="dg-invoice-party-title">Supplier Information</h2>
                        <div class="dg-invoice-field-list">
                            @if (!empty($invoice->supplier?->name))
                                <div class="dg-invoice-field-row">
                                    <span class="dg-invoice-field-label">Supplier Name</span>
                                    <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                    <span class="dg-invoice-field-value">{{ $invoice->supplier->name }}</span>
                                </div>
                            @endif

                            @if (!empty($invoice->supplier?->authority_name))
                                <div class="dg-invoice-field-row">
                                    <span class="dg-invoice-field-label">Contact Person</span>
                                    <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                    <span class="dg-invoice-field-value">{{ $invoice->supplier->authority_name }}</span>
                                </div>
                            @endif

                            @if (!empty($invoice->supplier?->address))
                                <div class="dg-invoice-field-row">
                                    <span class="dg-invoice-field-label">Address</span>
                                    <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                    <span class="dg-invoice-field-value">{{ $invoice->supplier->address }}</span>
                                </div>
                            @endif

                            @if (!empty($invoice->supplier?->mobile))
                                <div class="dg-invoice-field-row">
                                    <span class="dg-invoice-field-label">Phone</span>
                                    <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                    <span class="dg-invoice-field-value">{{ $invoice->supplier->mobile }}</span>
                                </div>
                            @elseif (!empty($invoice->supplier?->telephone))
                                <div class="dg-invoice-field-row">
                                    <span class="dg-invoice-field-label">Phone</span>
                                    <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                    <span class="dg-invoice-field-value">{{ $invoice->supplier->telephone }}</span>
                                </div>
                            @endif

                            @if (!empty($invoice->supplier?->email))
                                <div class="dg-invoice-field-row">
                                    <span class="dg-invoice-field-label">Email</span>
                                    <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                    <span class="dg-invoice-field-value">{{ $invoice->supplier->email }}</span>
                                </div>
                            @endif

                            @if ($invoice->supplier)
                                <div class="dg-invoice-field-row">
                                    <span class="dg-invoice-field-label">VAT No</span>
                                    <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                    <span class="dg-invoice-field-value">{{ !empty($invoice->supplier->tax_no) ? $invoice->supplier->tax_no : '-' }}</span>
                                </div>

                                <div class="dg-invoice-field-row">
                                    <span class="dg-invoice-field-label">PAN No</span>
                                    <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                    <span class="dg-invoice-field-value">-</span>
                                </div>
                            @endif
                        </div>
                    </section>

                    <section class="dg-invoice-party dg-invoice-party-details">
                        <h2 class="dg-invoice-party-title">Invoice Information</h2>
                        <div class="dg-invoice-field-list">
                            @if (!empty($invoice->invoice_no))
                                <div class="dg-invoice-field-row">
                                    <span class="dg-invoice-field-label">Invoice No</span>
                                    <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                    <span class="dg-invoice-field-value">{{ $invoice->invoice_no }}</span>
                                </div>
                            @endif

                            @if (!empty($invoice->purchase_date))
                                <div class="dg-invoice-field-row">
                                    <span class="dg-invoice-field-label">Invoice Date</span>
                                    <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                    <span class="dg-invoice-field-value">{{ \Illuminate\Support\Carbon::parse($invoice->purchase_date)->format('d-m-Y') }}</span>
                                </div>
                            @endif

                            <div class="dg-invoice-field-row">
                                <span class="dg-invoice-field-label">Status</span>
                                <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                <span class="dg-invoice-field-value">
                                    @if ((int) $invoice->status === 1)
                                        <span class="dg-badge dg-badge-status dg-badge-success">Active</span>
                                    @else
                                        <span class="dg-badge dg-badge-status dg-badge-secondary">Cancelled</span>
                                    @endif
                                </span>
                            </div>

                            @if (!empty($invoice->payment_status))
                                <div class="dg-invoice-field-row">
                                    <span class="dg-invoice-field-label">Payment Status</span>
                                    <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                    <span class="dg-invoice-field-value">
                                        <span class="dg-badge dg-badge-status dg-badge-{{ $invoice->payment_status == 'paid' ? 'success' : ($invoice->payment_status == 'partial' ? 'warning' : ($invoice->payment_status == 'cancelled' ? 'secondary' : 'danger')) }}">
                                            {{ ucfirst($invoice->payment_status) }}
                                        </span>
                                    </span>
                                </div>
                            @endif

                            @if ($invoice->relationLoaded('financialYear') && !empty($invoice->financialYear?->name))
                                <div class="dg-invoice-field-row">
                                    <span class="dg-invoice-field-label">Financial Year</span>
                                    <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                    <span class="dg-invoice-field-value">{{ $invoice->financialYear->name }}</span>
                                </div>
                            @endif

                            @if ($invoice->relationLoaded('creator') && !empty($invoice->creator?->name))
                                <div class="dg-invoice-field-row">
                                    <span class="dg-invoice-field-label">Created By</span>
                                    <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                    <span class="dg-invoice-field-value">{{ $invoice->creator->name }}</span>
                                </div>
                            @endif
                        </div>
                    </section>
                </div>

                <section class="dg-invoice-lines">
                    <h2 class="dg-invoice-lines-title">Invoice Items</h2>
                    <div class="dg-table-scroll">
                        <table class="table dg-table dg-invoice-table">
                            <thead class="dg-head">
                                <tr>
                                    <th scope="col" class="dg-col-num">#</th>
                                    <th scope="col">Item</th>
                                    <th scope="col" class="dg-col-num">Qty</th>
                                    <th scope="col">Unit</th>
                                    <th scope="col" class="dg-col-num">Unit Cost</th>
                                    <th scope="col" class="dg-col-num">VAT %</th>
                                    <th scope="col" class="dg-col-num">VAT Amount</th>
                                    <th scope="col" class="dg-col-num">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="dg-body">
                                @foreach ($invoice->items as $key => $item)
                                    <tr class="dg-row">
                                        <td class="dg-col-num">{{ $key + 1 }}</td>
                                        <td class="dg-invoice-item-name">
                                            @if ($item->item_type === 'service' && !empty($item->service?->name))
                                                {{ $item->service->name }}
                                            @elseif ($item->item_type !== 'service' && !empty($item->product?->name))
                                                {{ $item->product->name }}
                                            @endif
                                            @if ($item->returned_qty > 0)
                                                <span class="dg-return-note">Returned: {{ $item->returned_qty }}</span>
                                            @endif
                                        </td>
                                        <td class="dg-col-num">{{ $item->quantity }}</td>
                                        <td>
                                            @if ($item->item_type === 'service')
                                                Service
                                            @elseif ($item->product)
                                                {{ $item->product->unit?->short_name ?? $item->product->unit?->name ?? 'Unit' }}
                                            @endif
                                        </td>
                                        <td class="dg-col-num">{{ number_format($item->unit_price, 2) }}</td>
                                        <td class="dg-col-num">{{ number_format($item->vat_rate, 2) }}%</td>
                                        <td class="dg-col-num">{{ number_format($item->vat_amount, 2) }}</td>
                                        <td class="dg-col-num">{{ number_format($item->total_price, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>

                <div class="dg-invoice-footer">
                    <section class="dg-invoice-payment">
                        <h2 class="dg-invoice-party-title">Payment Information</h2>
                        <dl class="dg-invoice-dl">
                            <div class="dg-invoice-dl-row">
                                <dt class="dg-invoice-dl-label">Paid Amount</dt>
                                <dd class="dg-invoice-dl-value dg-summary-paid">{{ number_format($invoice->paid_amount, 2) }}</dd>
                            </div>
                            <div class="dg-invoice-dl-row">
                                <dt class="dg-invoice-dl-label">Due Amount</dt>
                                <dd class="dg-invoice-dl-value dg-summary-due">{{ number_format($invoice->due_amount, 2) }}</dd>
                            </div>
                        </dl>

                        @if (!empty($invoice->note))
                            <div class="dg-invoice-note-block">
                                <h3 class="dg-invoice-note-title">Note</h3>
                                <div class="dg-invoice-note-body">{{ $invoice->note }}</div>
                            </div>
                        @endif
                    </section>

                    <section class="dg-invoice-totals">
                        <h2 class="dg-invoice-party-title">Summary</h2>
                        <div class="dg-invoice-totals-box">
                            <div class="dg-summary-item">
                                <span class="dg-summary-label">Subtotal</span>
                                <span class="dg-summary-value">{{ number_format($invoice->subtotal, 2) }}</span>
                            </div>
                            <div class="dg-summary-item">
                                <span class="dg-summary-label">Discount</span>
                                <span class="dg-summary-value">{{ number_format($invoice->discount, 2) }}</span>
                            </div>
                            <div class="dg-summary-item">
                                <span class="dg-summary-label">Taxable Amount</span>
                                <span class="dg-summary-value">{{ number_format(max(0, (float) $invoice->subtotal - (float) $invoice->discount), 2) }}</span>
                            </div>
                            <div class="dg-summary-item">
                                <span class="dg-summary-label">VAT</span>
                                <span class="dg-summary-value">{{ number_format($invoice->total_vat, 2) }}</span>
                            </div>

                            <div class="dg-invoice-totals-divider"></div>

                            <div class="dg-summary-item dg-summary-total">
                                <span class="dg-summary-label">Grand Total</span>
                                <span class="dg-summary-value">{{ number_format($invoice->grand_total, 2) }}</span>
                            </div>
                        </div>
                    </section>
                </div>

                @php
                    $grandTotalAmount = (float) $invoice->grand_total;
                    $amountRupees = (int) floor($grandTotalAmount);
                    $amountPaisa = (int) round(($grandTotalAmount - $amountRupees) * 100);
                    $rupeeWords = trim(preg_replace('/\s+only$/i', '', preg_replace('/\s+only\s+thousand\s+/i', ' Thousand ', numberToWords($amountRupees))));
                    if ($amountPaisa > 0) {
                        $paisaWords = trim(preg_replace('/\s+only$/i', '', preg_replace('/\s+only\s+thousand\s+/i', ' Thousand ', numberToWords($amountPaisa))));
                        $amountInWords = $rupeeWords . ' Rupees and ' . $paisaWords . ' Paisa Only';
                    } else {
                        $amountInWords = $rupeeWords . ' Rupees Only';
                    }
                @endphp

                <section class="dg-invoice-amount-words">
                    <h2 class="dg-invoice-amount-words-title">Amount in Words</h2>
                    <p class="dg-invoice-amount-words-value">{{ $amountInWords }}</p>
                </section>

            </article>

        </div>
    </main>

</div>

@endsection
