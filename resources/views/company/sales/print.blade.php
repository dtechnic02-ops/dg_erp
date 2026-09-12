@extends('company.layout')

@section('title', 'Sales Invoice Print')

@section('content')

@if(!empty($fiscalPrintLabel))
    <div class="text-center fw-bold mb-2">{{ $fiscalPrintLabel }}</div>
@endif
@if($invoice->cancelled_at)
    <div class="alert alert-danger text-center py-2">
        <strong>CANCELLED</strong> — {{ $invoice->cancellation_reason }}
        ({{ $invoice->cancelled_at->format('Y-m-d H:i:s') }})
    </div>
@endif

@php
    $company = $invoice->company;
    $useFiscalSnapshot = $useFiscalSnapshot ?? false;
    $sellerName = $useFiscalSnapshot ? $invoice->seller_name_snapshot : $company?->company_name;
    $sellerAddress = $useFiscalSnapshot ? $invoice->seller_address_snapshot : $company?->address;
    $sellerVat = $useFiscalSnapshot ? $invoice->seller_vat_snapshot : $company?->vat_number;
    $sellerPan = $useFiscalSnapshot ? $invoice->seller_pan_snapshot : $company?->pan_number;
    $buyerName = $useFiscalSnapshot ? $invoice->buyer_name_snapshot : $invoice->customer?->name;
    $buyerAddress = $useFiscalSnapshot ? $invoice->buyer_address_snapshot : $invoice->customer?->address;
    $buyerTaxNo = $useFiscalSnapshot ? $invoice->buyer_tax_no_snapshot : $invoice->customer?->tax_no;

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
                                @if (!empty($sellerName))
                                    <div class="dg-invoice-field-row">
                                        <span class="dg-invoice-field-label">Company Name</span>
                                        <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                        <span class="dg-invoice-field-value dg-invoice-company-name">{{ $sellerName }}</span>
                                    </div>
                                @endif

                                @if (!empty($sellerAddress))
                                    <div class="dg-invoice-field-row">
                                        <span class="dg-invoice-field-label">Address</span>
                                        <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                        <span class="dg-invoice-field-value">{{ $sellerAddress }}</span>
                                    </div>
                                @endif

                                @if (!$useFiscalSnapshot && !empty($company?->address_line_2))
                                    <div class="dg-invoice-field-row">
                                        <span class="dg-invoice-field-label">Address Line 2</span>
                                        <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                        <span class="dg-invoice-field-value">{{ $company->address_line_2 }}</span>
                                    </div>
                                @endif

                                @if (!$useFiscalSnapshot && !empty($company?->mobile))
                                    <div class="dg-invoice-field-row">
                                        <span class="dg-invoice-field-label">Phone</span>
                                        <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                        <span class="dg-invoice-field-value">{{ $company->mobile }}</span>
                                    </div>
                                @elseif (!$useFiscalSnapshot && !empty($company?->telephone))
                                    <div class="dg-invoice-field-row">
                                        <span class="dg-invoice-field-label">Phone</span>
                                        <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                        <span class="dg-invoice-field-value">{{ $company->telephone }}</span>
                                    </div>
                                @endif

                                @if (!$useFiscalSnapshot && !empty($company?->email))
                                    <div class="dg-invoice-field-row">
                                        <span class="dg-invoice-field-label">Email</span>
                                        <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                        <span class="dg-invoice-field-value">{{ $company->email }}</span>
                                    </div>
                                @endif

                                @if (!empty($sellerVat))
                                    <div class="dg-invoice-field-row">
                                        <span class="dg-invoice-field-label">VAT No</span>
                                        <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                        <span class="dg-invoice-field-value">{{ $sellerVat }}</span>
                                    </div>
                                @endif

                                @if (!empty($sellerPan))
                                    <div class="dg-invoice-field-row">
                                        <span class="dg-invoice-field-label">PAN No</span>
                                        <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                        <span class="dg-invoice-field-value">{{ $sellerPan }}</span>
                                    </div>
                                @endif

                                @if (!$useFiscalSnapshot && !empty($company?->website))
                                    <div class="dg-invoice-field-row">
                                        <span class="dg-invoice-field-label">Website</span>
                                        <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                        <span class="dg-invoice-field-value">{{ $company->website }}</span>
                                    </div>
                                @endif
                            </div>
                        </section>

                        <div class="dg-invoice-print-header-col dg-invoice-print-header-center">
                            @if (!$useFiscalSnapshot && $company?->logo_path)
                                <img
                                    src="{{ asset('companies/' . $company->id . '/' . $company->logo_path) }}"
                                    alt="{{ $company->company_name ?? 'Company' }}"
                                    class="dg-invoice-print-logo-center">
                            @endif
                            <h1 class="dg-invoice-print-title">{{ $isFiscalTaxInvoice ? 'TAX INVOICE' : 'SALES INVOICE' }}</h1>
                        </div>

                        <section class="dg-invoice-print-header-col dg-invoice-print-header-right">
                            <h2 class="dg-invoice-party-title">Customer Information</h2>
                            <div class="dg-invoice-field-list">
                                @if (!empty($buyerName))
                                    <div class="dg-invoice-field-row">
                                        <span class="dg-invoice-field-label">Customer Name</span>
                                        <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                        <span class="dg-invoice-field-value">{{ $buyerName }}</span>
                                    </div>
                                @endif

                                @if (!$useFiscalSnapshot && !empty($invoice->customer?->authority_name))
                                    <div class="dg-invoice-field-row">
                                        <span class="dg-invoice-field-label">Contact Person</span>
                                        <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                        <span class="dg-invoice-field-value">{{ $invoice->customer->authority_name }}</span>
                                    </div>
                                @endif

                                @if (!empty($buyerAddress))
                                    <div class="dg-invoice-field-row">
                                        <span class="dg-invoice-field-label">Address</span>
                                        <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                        <span class="dg-invoice-field-value">{{ $buyerAddress }}</span>
                                    </div>
                                @endif

                                @if (!$useFiscalSnapshot && !empty($invoice->customer?->mobile))
                                    <div class="dg-invoice-field-row">
                                        <span class="dg-invoice-field-label">Phone</span>
                                        <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                        <span class="dg-invoice-field-value">{{ $invoice->customer->mobile }}</span>
                                    </div>
                                @elseif (!$useFiscalSnapshot && !empty($invoice->customer?->telephone))
                                    <div class="dg-invoice-field-row">
                                        <span class="dg-invoice-field-label">Phone</span>
                                        <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                        <span class="dg-invoice-field-value">{{ $invoice->customer->telephone }}</span>
                                    </div>
                                @endif

                                @if (!$useFiscalSnapshot && !empty($invoice->customer?->email))
                                    <div class="dg-invoice-field-row">
                                        <span class="dg-invoice-field-label">Email</span>
                                        <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                        <span class="dg-invoice-field-value">{{ $invoice->customer->email }}</span>
                                    </div>
                                @endif

                                @if ($useFiscalSnapshot)
                                    <div class="dg-invoice-field-row">
                                        <span class="dg-invoice-field-label">Tax No</span>
                                        <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                        <span class="dg-invoice-field-value">{{ !empty($buyerTaxNo) ? $buyerTaxNo : '-' }}</span>
                                    </div>
                                @elseif ($invoice->customer)
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
                                <span class="dg-invoice-field-label">Transaction Date</span>
                                <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                <span class="dg-invoice-field-value">{{ \Illuminate\Support\Carbon::parse($invoice->sale_date)->format('d-m-Y') }}</span>
                            </div>
                        @endif

                        @if ($fiscalIssuedAtNepal !== null)
                            <div class="dg-invoice-field-row">
                                <span class="dg-invoice-field-label">Issue Date/Time</span>
                                <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                <span class="dg-invoice-field-value">{{ $fiscalIssuedAtNepal->format('d-m-Y H:i:s') }} NPT</span>
                            </div>
                            <div class="dg-invoice-field-row">
                                <span class="dg-invoice-field-label">Issue Date (BS)</span>
                                <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                <span class="dg-invoice-field-value">{{ $fiscalIssueDateBs }}</span>
                            </div>
                        @endif

                        @if ($saleDateBs !== null)
                            <div class="dg-invoice-field-row">
                                <span class="dg-invoice-field-label">मिति (BS)</span>
                                <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                <span class="dg-invoice-field-value">{{ $saleDateBs }}</span>
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
                        @if ($useFiscalSnapshot && !empty($fiscalPaymentPresentation))
                            <div class="dg-invoice-field-row">
                                <span class="dg-invoice-field-label">Mode of Payment</span>
                                <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                <span class="dg-invoice-field-value">{{ $fiscalPaymentPresentation['display'] }}</span>
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
                                        @if($isFiscalTaxInvoice)
                                            <th scope="col" class="dg-col-num">Gross</th>
                                            <th scope="col" class="dg-col-num">Discount</th>
                                            <th scope="col" class="dg-col-num">Net</th>
                                            <th scope="col">Tax Class</th>
                                        @endif
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
                                                @if ($useFiscalSnapshot)
                                                    {{ $item->item_name_snapshot }}
                                                    @if ($item->item_type === 'product')
                                                        <div class="small text-muted">
                                                            @if ($item->fiscal_hs_code) H.S.: {{ $item->fiscal_hs_code }} @endif
                                                            @if ($item->fiscal_brand_name) | Brand: {{ $item->fiscal_brand_name }} @endif
                                                            @if ($item->fiscal_product_type) | Type: {{ $item->fiscal_product_type }} @endif
                                                            @if ($item->fiscal_model) | Model: {{ $item->fiscal_model }} @endif
                                                            @if ($item->fiscal_size) | Size: {{ $item->fiscal_size }} @endif
                                                        </div>
                                                    @endif
                                                @elseif ($item->item_type === 'service' && !empty($item->service?->name))
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
                                                @if ($useFiscalSnapshot)
                                                    {{ $item->unit_name_snapshot }}
                                                @elseif ($item->item_type === 'service')
                                                    Service
                                                @elseif ($item->product)
                                                    {{ $item->product->unit?->short_name ?? $item->product->unit?->name ?? 'Unit' }}
                                                @endif
                                            </td>
                                            <td class="dg-col-num">{{ number_format($item->unit_price, 2) }}</td>
                                            @if($isFiscalTaxInvoice)
                                                @php($fiscalLine = $fiscalReconciliation['lines'][$item->id])
                                                <td class="dg-col-num">{{ number_format($fiscalLine['gross_amount'], 2) }}</td>
                                                <td class="dg-col-num">{{ number_format($fiscalLine['discount_amount'], 2) }}</td>
                                                <td class="dg-col-num">{{ number_format($fiscalLine['net_base'], 2) }}</td>
                                                <td>{{ str_replace('_', ' ', strtoupper($fiscalLine['tax_classification'])) }}</td>
                                            @endif
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
                                    <span class="dg-summary-value">{{ number_format($isFiscalTaxInvoice ? $fiscalReconciliation['gross_sales'] : $invoice->subtotal, 2) }}</span>
                                </div>
                                <div class="dg-summary-item">
                                    <span class="dg-summary-label">Discount</span>
                                    <span class="dg-summary-value">{{ number_format($isFiscalTaxInvoice ? $fiscalReconciliation['total_discount'] : $invoice->discount, 2) }}</span>
                                </div>
                                @if($isFiscalTaxInvoice)
                                    <div class="dg-summary-item"><span class="dg-summary-label">Net Sales</span><span class="dg-summary-value">{{ number_format($fiscalReconciliation['net_sales'], 2) }}</span></div>
                                    <div class="dg-summary-item"><span class="dg-summary-label">Exempt Amount</span><span class="dg-summary-value">{{ number_format($fiscalReconciliation['exempt_sales'], 2) }}</span></div>
                                    <div class="dg-summary-item"><span class="dg-summary-label">Zero-rated Amount</span><span class="dg-summary-value">{{ number_format($fiscalReconciliation['zero_rated_sales'], 2) }}</span></div>
                                    <div class="dg-summary-item"><span class="dg-summary-label">Export Amount</span><span class="dg-summary-value">{{ number_format($fiscalReconciliation['export_sales'], 2) }}</span></div>
                                    <div class="dg-summary-item"><span class="dg-summary-label">Out-of-scope Amount</span><span class="dg-summary-value">{{ number_format($fiscalReconciliation['out_of_scope_sales'], 2) }}</span></div>
                                @endif
                                <div class="dg-summary-item">
                                    <span class="dg-summary-label">Taxable Amount</span>
                                    <span class="dg-summary-value">{{ number_format($isFiscalTaxInvoice ? $fiscalReconciliation['taxable_sales'] : invoice_taxable_amount($invoice->items), 2) }}</span>
                                </div>
                                <div class="dg-summary-item">
                                    <span class="dg-summary-label">VAT</span>
                                    <span class="dg-summary-value">{{ number_format($isFiscalTaxInvoice ? $fiscalReconciliation['vat_total'] : $invoice->total_vat, 2) }}</span>
                                </div>

                                <div class="dg-invoice-totals-divider"></div>

                                <div class="dg-summary-item dg-summary-total">
                                    <span class="dg-summary-label">Grand Total</span>
                                    <span class="dg-summary-value">{{ number_format($isFiscalTaxInvoice ? $fiscalReconciliation['grand_total'] : $invoice->grand_total, 2) }}</span>
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
                            <div class="dg-signature-label">{{ $isFiscalTaxInvoice ? 'Seller Signature' : 'Authorized Signature' }}</div>
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
