@extends('company.layout')

@section('title', 'Sales Invoice Print')

@section('content')

@php
    $company = auth()->user()->company;

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

<div class="dg-page dg-invoice dg-invoice-print">

    <main class="dg-container">
        <div class="container-fluid">

            <div id="printArea">
                <article class="dg-invoice-sheet">

                    <header class="dg-invoice-print-header">
                        <section class="dg-invoice-print-header-col dg-invoice-print-header-left">
                            <h2 class="dg-invoice-party-title">Company Information</h2>
                            <div class="dg-invoice-field-list">
                                @if (!empty($company?->company_name))
                                    <div class="dg-invoice-field-row">
                                        <span class="dg-invoice-field-label">Company Name</span>
                                        <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                        <span class="dg-invoice-field-value dg-invoice-company-name">{{ $company->company_name }}</span>
                                    </div>
                                @endif

                                @if (!empty($company?->address))
                                    <div class="dg-invoice-field-row">
                                        <span class="dg-invoice-field-label">Address</span>
                                        <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                        <span class="dg-invoice-field-value">{{ $company->address }}</span>
                                    </div>
                                @endif

                                @if (!empty($company?->address_line_2))
                                    <div class="dg-invoice-field-row">
                                        <span class="dg-invoice-field-label">Address Line 2</span>
                                        <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                        <span class="dg-invoice-field-value">{{ $company->address_line_2 }}</span>
                                    </div>
                                @endif

                                @if (!empty($company?->mobile))
                                    <div class="dg-invoice-field-row">
                                        <span class="dg-invoice-field-label">Phone</span>
                                        <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                        <span class="dg-invoice-field-value">{{ $company->mobile }}</span>
                                    </div>
                                @elseif (!empty($company?->telephone))
                                    <div class="dg-invoice-field-row">
                                        <span class="dg-invoice-field-label">Phone</span>
                                        <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                        <span class="dg-invoice-field-value">{{ $company->telephone }}</span>
                                    </div>
                                @endif

                                @if (!empty($company?->email))
                                    <div class="dg-invoice-field-row">
                                        <span class="dg-invoice-field-label">Email</span>
                                        <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                        <span class="dg-invoice-field-value">{{ $company->email }}</span>
                                    </div>
                                @endif

                                @if (!empty($company?->vat_number))
                                    <div class="dg-invoice-field-row">
                                        <span class="dg-invoice-field-label">VAT No</span>
                                        <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                        <span class="dg-invoice-field-value">{{ $company->vat_number }}</span>
                                    </div>
                                @endif

                                @if (!empty($company?->pan_number))
                                    <div class="dg-invoice-field-row">
                                        <span class="dg-invoice-field-label">PAN No</span>
                                        <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                        <span class="dg-invoice-field-value">{{ $company->pan_number }}</span>
                                    </div>
                                @endif

                                @if (!empty($company?->website))
                                    <div class="dg-invoice-field-row">
                                        <span class="dg-invoice-field-label">Website</span>
                                        <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                        <span class="dg-invoice-field-value">{{ $company->website }}</span>
                                    </div>
                                @endif
                            </div>
                        </section>

                        <div class="dg-invoice-print-header-col dg-invoice-print-header-center">
                            @if ($company?->logo_path)
                                <img
                                    src="{{ asset('companies/' . $company->id . '/' . $company->logo_path) }}"
                                    alt="{{ $company->company_name ?? 'Company' }}"
                                    class="dg-invoice-print-logo-center">
                            @endif
                            <h1 class="dg-invoice-print-title">SALES INVOICE</h1>
                        </div>

                        <section class="dg-invoice-print-header-col dg-invoice-print-header-right">
                            <h2 class="dg-invoice-party-title">Customer Information</h2>
                            <div class="dg-invoice-field-list">
                                @if (!empty($invoice->customer?->name))
                                    <div class="dg-invoice-field-row">
                                        <span class="dg-invoice-field-label">Customer Name</span>
                                        <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                        <span class="dg-invoice-field-value">{{ $invoice->customer->name }}</span>
                                    </div>
                                @endif

                                @if (!empty($invoice->customer?->authority_name))
                                    <div class="dg-invoice-field-row">
                                        <span class="dg-invoice-field-label">Contact Person</span>
                                        <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                        <span class="dg-invoice-field-value">{{ $invoice->customer->authority_name }}</span>
                                    </div>
                                @endif

                                @if (!empty($invoice->customer?->address))
                                    <div class="dg-invoice-field-row">
                                        <span class="dg-invoice-field-label">Address</span>
                                        <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                        <span class="dg-invoice-field-value">{{ $invoice->customer->address }}</span>
                                    </div>
                                @endif

                                @if (!empty($invoice->customer?->mobile))
                                    <div class="dg-invoice-field-row">
                                        <span class="dg-invoice-field-label">Phone</span>
                                        <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                        <span class="dg-invoice-field-value">{{ $invoice->customer->mobile }}</span>
                                    </div>
                                @elseif (!empty($invoice->customer?->telephone))
                                    <div class="dg-invoice-field-row">
                                        <span class="dg-invoice-field-label">Phone</span>
                                        <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                        <span class="dg-invoice-field-value">{{ $invoice->customer->telephone }}</span>
                                    </div>
                                @endif

                                @if (!empty($invoice->customer?->email))
                                    <div class="dg-invoice-field-row">
                                        <span class="dg-invoice-field-label">Email</span>
                                        <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                        <span class="dg-invoice-field-value">{{ $invoice->customer->email }}</span>
                                    </div>
                                @endif

                                @if ($invoice->customer)
                                    <div class="dg-invoice-field-row">
                                        <span class="dg-invoice-field-label">VAT No</span>
                                        <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                        <span class="dg-invoice-field-value">{{ !empty($invoice->customer->tax_no) ? $invoice->customer->tax_no : '-' }}</span>
                                    </div>

                                    <div class="dg-invoice-field-row">
                                        <span class="dg-invoice-field-label">PAN No</span>
                                        <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                        <span class="dg-invoice-field-value">-</span>
                                    </div>
                                @endif
                            </div>
                        </section>
                    </header>

                    <div class="dg-invoice-print-header-rule" role="presentation"></div>

                    <div class="dg-invoice-print-meta">
                        @if (!empty($invoice->invoice_no))
                            <div class="dg-invoice-field-row">
                                <span class="dg-invoice-field-label">Invoice No</span>
                                <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                <span class="dg-invoice-field-value">{{ $invoice->invoice_no }}</span>
                            </div>
                        @endif

                        @if (!empty($invoice->sale_date))
                            <div class="dg-invoice-field-row">
                                <span class="dg-invoice-field-label">Invoice Date</span>
                                <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                <span class="dg-invoice-field-value">{{ \Illuminate\Support\Carbon::parse($invoice->sale_date)->format('d-m-Y') }}</span>
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
                                        <th scope="col" class="dg-col-num">Rate</th>
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

                    <div class="dg-invoice-footer dg-invoice-print-footer">
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

                            <section class="dg-invoice-amount-words">
                                <h2 class="dg-invoice-amount-words-title">Amount in Words</h2>
                                <p class="dg-invoice-amount-words-value">{{ $amountInWords }}</p>
                            </section>
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
                                <div class="dg-summary-item">
                                    <span class="dg-summary-label">Paid</span>
                                    <span class="dg-summary-value dg-summary-paid">{{ number_format($invoice->paid_amount, 2) }}</span>
                                </div>
                                <div class="dg-summary-item">
                                    <span class="dg-summary-label">Due</span>
                                    <span class="dg-summary-value dg-summary-due">{{ number_format($invoice->due_amount, 2) }}</span>
                                </div>
                            </div>
                        </section>
                    </div>

                    <div class="dg-invoice-signature-block">
                        <div class="dg-invoice-sign">
                            <div class="dg-signature-line"></div>
                            <div class="dg-signature-label">Customer Signature</div>
                        </div>
                        <div class="dg-invoice-sign">
                            <div class="dg-signature-line"></div>
                            <div class="dg-signature-label">Authorized Signature</div>
                        </div>
                    </div>

                </article>
            </div>

        </div>
    </main>

</div>

@push('scripts')
    <script>
        window.addEventListener('load', function () {
            window.print();
        });
    </script>
@endpush

@endsection
