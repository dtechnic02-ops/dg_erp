@extends('company.layout')



@section('title', 'Sales Return Print')



@section('content')

@if(!empty($fiscalPrintLabel))
    <div class="text-center fw-bold mb-2">{{ $fiscalPrintLabel }}</div>
@endif
@if($return->cancelled_at)
    <div class="alert alert-danger text-center py-2">
        <strong>CANCELLED</strong> — {{ $return->cancellation_reason }}
        ({{ $return->cancelled_at->format('Y-m-d H:i:s') }})
    </div>
@endif
@if(!empty($fiscalPaymentPresentation))
    <div class="text-center small mb-2">Original Invoice Mode of Payment: {{ $fiscalPaymentPresentation['display'] }}</div>
@endif



@php

    $company = auth()->user()->company;

    $invoice = $return->invoice;

    $sellerName = $isFiscalCreditNote ? $fiscalCreditNote['seller_name'] : $company?->company_name;
    $sellerAddress = $isFiscalCreditNote ? $fiscalCreditNote['seller_address'] : $company?->address;
    $sellerPan = $isFiscalCreditNote ? $fiscalCreditNote['seller_pan'] : ($company?->pan_number ?: $company?->vat_number);
    $buyerName = $isFiscalCreditNote ? $fiscalCreditNote['buyer_name'] : $return->customer?->name;
    $buyerAddress = $isFiscalCreditNote ? $fiscalCreditNote['buyer_address'] : $return->customer?->address;
    $buyerPan = $isFiscalCreditNote ? $fiscalCreditNote['buyer_pan'] : ($return->customer?->pan_number ?? null);



    $returnAmount = (float) $return->grand_total;

    $amountRupees = (int) floor($returnAmount);

    $amountPaisa = (int) round(($returnAmount - $amountRupees) * 100);

    $rupeeWords = trim(preg_replace('/\s+only$/i', '', preg_replace('/\s+only\s+thousand\s+/i', ' Thousand ', numberToWords($amountRupees))));

    if ($amountPaisa > 0) {

        $paisaWords = trim(preg_replace('/\s+only$/i', '', preg_replace('/\s+only\s+thousand\s+/i', ' Thousand ', numberToWords($amountPaisa))));

        $amountInWords = $rupeeWords . ' Rupees and ' . $paisaWords . ' Paisa Only';

    } else {

        $amountInWords = $rupeeWords . ' Rupees Only';

    }



    $companyPhone = $company?->mobile ?: ($company?->telephone ?? null);

    $customerPhone = $return->customer?->mobile ?: ($return->customer?->telephone ?? null);



    $invoiceTotal = (float) ($invoice->grand_total ?? 0);

    $refundedAmount = (float) $return->refunded_amount;

    $remainingRefund = (float) $return->remaining_amount;

@endphp



<div class="dg-page dg-payment-print">



    <main class="dg-container">

        <div class="container-fluid">



            <div id="printArea">

                <article class="card dg-card dg-payment">



                    <header class="text-center border-bottom pb-2 mb-2">

                        @if ($company?->logo_path)

                            <img

                                src="{{ asset('companies/' . $company->id . '/' . $company->logo_path) }}"

                                alt="{{ $company->company_name ?? 'Company' }}"

                                class="dg-print-logo d-block mx-auto mb-1">

                        @endif

                        <h1 class="dg-print-title mb-0">{{ $isFiscalCreditNote ? 'CREDIT NOTE' : 'SALES RETURN' }}</h1>

                    </header>



                    <div class="card-body dg-card-body py-2 px-3">



                        <div class="row g-2 mb-2">

                            <div class="col-6">

                                <section class="card dg-card h-100 mb-0">

                                    <header class="card-header dg-card-header py-2">

                                        <h2 class="h6 mb-0">Company Information</h2>

                                    </header>

                                    <div class="card-body dg-card-body py-2 px-3">

                                        <div class="dg-summary-bar-item">

                                            <span class="dg-summary-bar-label text-muted">Company Name</span>

                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>

                                            <span class="dg-summary-bar-value">{{ $sellerName ?: '-' }}</span>

                                        </div>

                                        <div class="dg-summary-bar-item">

                                            <span class="dg-summary-bar-label text-muted">Address</span>

                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>

                                            <span class="dg-summary-bar-value">

                                                @if (!empty($sellerAddress))

                                                    {{ $sellerAddress }}

                                                @else

                                                    -

                                                @endif

                                            </span>

                                        </div>

                                        <div class="dg-summary-bar-item">

                                            <span class="dg-summary-bar-label text-muted">Phone</span>

                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>

                                            <span class="dg-summary-bar-value">{{ $companyPhone ?: '-' }}</span>

                                        </div>

                                        <div class="dg-summary-bar-item">

                                            <span class="dg-summary-bar-label text-muted">Email</span>

                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>

                                            <span class="dg-summary-bar-value">{{ $company->email ?? '-' }}</span>

                                        </div>

                                        <div class="dg-summary-bar-item">

                                            <span class="dg-summary-bar-label text-muted">VAT No</span>

                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>

                                            <span class="dg-summary-bar-value">{{ $company->vat_number ?? '-' }}</span>

                                        </div>

                                        <div class="dg-summary-bar-item">

                                            <span class="dg-summary-bar-label text-muted">PAN No</span>

                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>

                                            <span class="dg-summary-bar-value">{{ $sellerPan ?: '-' }}</span>

                                        </div>

                                        <div class="dg-summary-bar-item">

                                            <span class="dg-summary-bar-label text-muted">Website</span>

                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>

                                            <span class="dg-summary-bar-value">{{ $company->website ?? '-' }}</span>

                                        </div>

                                    </div>

                                </section>

                            </div>



                            <div class="col-6">

                                <section class="card dg-card h-100 mb-0">

                                    <header class="card-header dg-card-header py-2">

                                        <h2 class="h6 mb-0">Customer Information</h2>

                                    </header>

                                    <div class="card-body dg-card-body py-2 px-3">

                                        <div class="dg-summary-bar-item">

                                            <span class="dg-summary-bar-label text-muted">Customer Name</span>

                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>

                                            <span class="dg-summary-bar-value">{{ $buyerName ?: '-' }}</span>

                                        </div>

                                        <div class="dg-summary-bar-item">
                                            <span class="dg-summary-bar-label text-muted">Address</span>
                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                            <span class="dg-summary-bar-value">{{ $buyerAddress ?: '-' }}</span>
                                        </div>

                                        <div class="dg-summary-bar-item">

                                            <span class="dg-summary-bar-label text-muted">Contact Person</span>

                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>

                                            <span class="dg-summary-bar-value">{{ $return->customer->authority_name ?? '-' }}</span>

                                        </div>

                                        <div class="dg-summary-bar-item">

                                            <span class="dg-summary-bar-label text-muted">Phone</span>

                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>

                                            <span class="dg-summary-bar-value">{{ $customerPhone ?: '-' }}</span>

                                        </div>

                                        <div class="dg-summary-bar-item">

                                            <span class="dg-summary-bar-label text-muted">Email</span>

                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>

                                            <span class="dg-summary-bar-value">{{ $return->customer->email ?? '-' }}</span>

                                        </div>

                                        <div class="dg-summary-bar-item">

                                            <span class="dg-summary-bar-label text-muted">VAT No</span>

                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>

                                            <span class="dg-summary-bar-value">{{ $buyerPan ?: '-' }}</span>

                                        </div>

                                        <div class="dg-summary-bar-item">

                                            <span class="dg-summary-bar-label text-muted">PAN No</span>

                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>

                                            <span class="dg-summary-bar-value">{{ $buyerPan ?: '-' }}</span>

                                        </div>

                                    </div>

                                </section>

                            </div>

                        </div>



                        <section class="card dg-card mb-2">

                            <header class="card-header dg-card-header py-2">

                                <h2 class="h6 mb-0">Return Information</h2>

                            </header>

                            <div class="card-body dg-card-body py-2 px-3">

                                <div class="row g-2">

                                    <div class="col-6">

                                        <div class="dg-summary-bar-item">

                                            <span class="dg-summary-bar-label text-muted">{{ $isFiscalCreditNote ? 'Credit Note No.' : 'Return No' }}</span>

                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>

                                            <span class="dg-summary-bar-value">{{ $return->return_no ?? '-' }}</span>

                                        </div>

                                        <div class="dg-summary-bar-item">

                                            <span class="dg-summary-bar-label text-muted">Financial Year</span>

                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>

                                            <span class="dg-summary-bar-value">{{ $return->financialYear->name ?? '-' }}</span>

                                        </div>

                                        <div class="dg-summary-bar-item">

                                            <span class="dg-summary-bar-label text-muted">Original Invoice Date</span>

                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>

                                            <span class="dg-summary-bar-value">

                                                @if (!empty($invoice?->sale_date))

                                                    {{ \Illuminate\Support\Carbon::parse($invoice->sale_date)->format('d-m-Y') }}

                                                @else

                                                    -

                                                @endif

                                            </span>

                                        </div>

                                        <div class="dg-summary-bar-item">

                                            <span class="dg-summary-bar-label text-muted">Refunded Amount</span>

                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>

                                            <span class="dg-summary-bar-value">{{ number_format($refundedAmount, 2) }}</span>

                                        </div>

                                        <div class="dg-summary-bar-item">

                                            <span class="dg-summary-bar-label text-muted">Remaining Refund</span>

                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>

                                            <span class="dg-summary-bar-value text-warning">{{ number_format($remainingRefund, 2) }}</span>

                                        </div>

                                    </div>

                                    <div class="col-6">

                                        @if($isFiscalCreditNote)
                                            <div class="dg-summary-bar-item">
                                                <span class="dg-summary-bar-label text-muted">Issue Date</span>
                                                <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                                <span class="dg-summary-bar-value">{{ $return->fiscal_issued_at?->timezone('Asia/Kathmandu')->format('d-m-Y H:i:s') ?? '-' }}</span>
                                            </div>
                                        @endif

                                        <div class="dg-summary-bar-item">

                                            <span class="dg-summary-bar-label text-muted">Return Date</span>

                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>

                                            <span class="dg-summary-bar-value">{{ $return->return_date?->format('d-m-Y') ?? '-' }} @include('company.components.nepali-date-display', ['adDate' => $return->return_date])</span>

                                        </div>

                                        <div class="dg-summary-bar-item">

                                            <span class="dg-summary-bar-label text-muted">Original Invoice No</span>

                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>

                                            <span class="dg-summary-bar-value">{{ $invoice->invoice_no ?? '-' }}</span>

                                        </div>

                                        @if($isFiscalCreditNote && $fiscalCreditNote['original_invoice_issued_at'])
                                            <div class="dg-summary-bar-item">
                                                <span class="dg-summary-bar-label text-muted">Original Invoice Issue Date</span>
                                                <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                                <span class="dg-summary-bar-value">{{ $fiscalCreditNote['original_invoice_issued_at']->timezone('Asia/Kathmandu')->format('d-m-Y H:i:s') }}</span>
                                            </div>
                                        @endif

                                        <div class="dg-summary-bar-item">

                                            <span class="dg-summary-bar-label text-muted">Original Invoice Total</span>

                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>

                                            <span class="dg-summary-bar-value">{{ number_format($invoiceTotal, 2) }}</span>

                                        </div>

                                        <div class="dg-summary-bar-item">

                                            <span class="dg-summary-bar-label text-muted">Return Total</span>

                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>

                                            <span class="dg-summary-bar-value text-success">Rs. {{ number_format($returnAmount, 2) }}</span>

                                        </div>

                                        <div class="dg-summary-bar-item">

                                            <span class="dg-summary-bar-label text-muted">Refund Status</span>

                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>

                                            <span class="dg-summary-bar-value">

                                                @if ($return->refund_status === 'Paid')

                                                    <span class="dg-badge dg-badge-status dg-badge-success">Paid</span>

                                                @elseif ($return->refund_status === 'Partial')

                                                    <span class="dg-badge dg-badge-status dg-badge-warning">Partial</span>

                                                @else

                                                    <span class="dg-badge dg-badge-status dg-badge-danger">Unpaid</span>

                                                @endif

                                            </span>

                                        </div>

                                        <div class="dg-summary-bar-item">

                                            <span class="dg-summary-bar-label text-muted">Return Status</span>

                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>

                                            <span class="dg-summary-bar-value">

                                                @if ((int) $return->status === 1)

                                                    <span class="dg-badge dg-badge-status dg-badge-success">Active</span>

                                                @else

                                                    <span class="dg-badge dg-badge-status dg-badge-secondary">Cancelled</span>

                                                @endif

                                            </span>

                                        </div>

                                    </div>

                                    <div class="col-12 border-top pt-2 mt-1">

                                        <div class="dg-summary-bar-item">

                                            <span class="dg-summary-bar-label text-muted">Amount In Words</span>

                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>

                                            <span class="dg-summary-bar-value">{{ $amountInWords }}</span>

                                        </div>

                                        @if (!empty($return->note))

                                            <div class="dg-note dg-summary-bar-item">

                                                <span class="dg-summary-bar-label text-muted">{{ $isFiscalCreditNote ? 'Reason' : 'Note' }}</span>

                                                <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>

                                                <span class="dg-summary-bar-value">{{ $return->note }}</span>

                                            </div>

                                        @endif

                                    </div>

                                </div>

                            </div>

                        </section>



                        <section class="card dg-card mb-2">

                            <header class="card-header dg-card-header py-2">

                                <h2 class="h6 mb-0">Return Items</h2>

                            </header>

                            <div class="card-body dg-card-body py-2 px-3">

                                <div class="dg-table-scroll">

                                    <table class="table dg-table dg-table-compact mb-0">

                                        <thead class="dg-head">

                                            <tr>

                                                <th scope="col">#</th>

                                                <th scope="col">Item</th>

                                                <th scope="col" class="dg-col-num">Qty</th>

                                                <th scope="col" class="dg-col-num">Unit Price</th>

                                                @if($isFiscalCreditNote)
                                                    <th scope="col" class="dg-col-num">Gross</th>
                                                    <th scope="col" class="dg-col-num">Discount</th>
                                                    <th scope="col" class="dg-col-num">Net</th>
                                                    <th scope="col">Tax Class</th>
                                                @endif
                                                <th scope="col" class="dg-col-num">VAT</th>

                                                <th scope="col" class="dg-col-num">Total</th>

                                            </tr>

                                        </thead>

                                        <tbody class="dg-body">

                                            @foreach ($return->items as $item)

                                                <tr class="dg-row">

                                                    <td>{{ $loop->iteration }}</td>

                                                    <td>
                                                        {{ $item->salesItem?->item_name_snapshot ?? ($item->product->name ?? ($item->salesItem?->service?->name ?? 'Deleted Item')) }}
                                                        @if ($item->salesItem?->item_type === 'product')
                                                            <div class="small text-muted">
                                                                @if ($item->salesItem->fiscal_hs_code) H.S.: {{ $item->salesItem->fiscal_hs_code }} @endif
                                                                @if ($item->salesItem->fiscal_brand_name) | Brand: {{ $item->salesItem->fiscal_brand_name }} @endif
                                                                @if ($item->salesItem->fiscal_product_type) | Type: {{ $item->salesItem->fiscal_product_type }} @endif
                                                                @if ($item->salesItem->fiscal_model) | Model: {{ $item->salesItem->fiscal_model }} @endif
                                                                @if ($item->salesItem->fiscal_size) | Size: {{ $item->salesItem->fiscal_size }} @endif
                                                            </div>
                                                        @endif
                                                    </td>

                                                    <td class="dg-col-num">{{ number_format($item->quantity, 2) }}</td>

                                                    <td class="dg-col-num">{{ number_format($item->unit_price, 2) }}</td>

                                                    @if($isFiscalCreditNote)
                                                        <td class="dg-col-num">{{ number_format((float) $item->fiscal_net_base + (float) $item->fiscal_discount_amount, 2) }}</td>
                                                        <td class="dg-col-num">{{ number_format($item->fiscal_discount_amount, 2) }}</td>
                                                        <td class="dg-col-num">{{ number_format($item->fiscal_net_base, 2) }}</td>
                                                        <td>{{ str_replace('_', ' ', strtoupper($item->tax_classification)) }}</td>
                                                    @endif

                                                    <td class="dg-col-num">{{ number_format($item->vat_amount, 2) }}</td>

                                                    <td class="dg-col-num">{{ number_format($item->total_price, 2) }}</td>

                                                </tr>

                                            @endforeach

                                        </tbody>

                                        <tfoot>

                                            <tr>

                                                <th colspan="{{ $isFiscalCreditNote ? 9 : 5 }}" class="text-end">Subtotal</th>

                                                <td class="dg-col-num">{{ number_format($return->subtotal, 2) }}</td>

                                            </tr>

                                            <tr>

                                                <th colspan="{{ $isFiscalCreditNote ? 9 : 5 }}" class="text-end">VAT</th>

                                                <td class="dg-col-num">{{ number_format($return->total_vat, 2) }}</td>

                                            </tr>

                                            <tr>

                                                <th colspan="{{ $isFiscalCreditNote ? 9 : 5 }}" class="text-end">{{ $isFiscalCreditNote ? 'Credit Note Total' : 'Return Total' }}</th>

                                                <td class="dg-col-num fw-bold">{{ number_format($return->grand_total, 2) }}</td>

                                            </tr>

                                        </tfoot>

                                    </table>

                                    @if($isFiscalCreditNote)
                                        @php($fiscalTotals = $fiscalCreditNote['reconciliation'])
                                        <div class="row g-2 mt-2 small">
                                            <div class="col-4">Gross: {{ number_format($fiscalTotals['gross_sales'], 2) }}</div>
                                            <div class="col-4">Discount: {{ number_format($fiscalTotals['total_discount'], 2) }}</div>
                                            <div class="col-4">Net: {{ number_format($fiscalTotals['net_sales'], 2) }}</div>
                                            <div class="col-4">Taxable: {{ number_format($fiscalTotals['taxable_sales'], 2) }}</div>
                                            <div class="col-4">Exempt: {{ number_format($fiscalTotals['exempt_sales'], 2) }}</div>
                                            <div class="col-4">Zero-rated: {{ number_format($fiscalTotals['zero_rated_sales'], 2) }}</div>
                                            <div class="col-4">Export: {{ number_format($fiscalTotals['export_sales'], 2) }}</div>
                                            <div class="col-4">Out-of-scope: {{ number_format($fiscalTotals['out_of_scope_sales'], 2) }}</div>
                                            <div class="col-4">VAT reversal: {{ number_format($fiscalTotals['vat_total'], 2) }}</div>
                                        </div>
                                    @endif

                                </div>

                            </div>

                        </section>



                        <footer>

                            <div class="row g-2 mt-1">

                                <div class="col-6 text-center">

                                    <div class="dg-signature-line"></div>

                                    <div class="dg-signature-label">Customer Signature</div>

                                </div>

                                <div class="col-6 text-center">

                                    <div class="dg-signature-line"></div>

                                    <div class="dg-signature-label">Authorized Signature</div>

                                </div>

                            </div>

                        </footer>



                    </div>

                </article>

            </div>



        </div>

    </main>



</div>



@push('scripts')

    <script>

        document.body.classList.add('dg-payment-print');

        window.addEventListener('load', function () {

            window.print();

        });

    </script>

@endpush



@endsection
