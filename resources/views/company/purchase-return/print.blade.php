@extends('company.layout')



@section('title', 'Purchase Return Print')



@section('content')



@php

    $company = auth()->user()->company;

    $invoice = $return->invoice;



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

    $supplierPhone = $return->supplier?->mobile ?: ($return->supplier?->telephone ?? null);



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

                        <h1 class="dg-print-title mb-0">PURCHASE RETURN</h1>

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

                                            <span class="dg-summary-bar-value">{{ $company->company_name ?? '-' }}</span>

                                        </div>

                                        <div class="dg-summary-bar-item">

                                            <span class="dg-summary-bar-label text-muted">Address</span>

                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>

                                            <span class="dg-summary-bar-value">

                                                @if (!empty($company?->address))

                                                    {{ $company->address }}@if (!empty($company?->address_line_2)), {{ $company->address_line_2 }}@endif

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

                                            <span class="dg-summary-bar-value">{{ $company->pan_number ?? '-' }}</span>

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

                                        <h2 class="h6 mb-0">Supplier Information</h2>

                                    </header>

                                    <div class="card-body dg-card-body py-2 px-3">

                                        <div class="dg-summary-bar-item">

                                            <span class="dg-summary-bar-label text-muted">Supplier Name</span>

                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>

                                            <span class="dg-summary-bar-value">{{ $return->supplier->name ?? '-' }}</span>

                                        </div>

                                        <div class="dg-summary-bar-item">

                                            <span class="dg-summary-bar-label text-muted">Contact Person</span>

                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>

                                            <span class="dg-summary-bar-value">{{ $return->supplier->authority_name ?? '-' }}</span>

                                        </div>

                                        <div class="dg-summary-bar-item">

                                            <span class="dg-summary-bar-label text-muted">Phone</span>

                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>

                                            <span class="dg-summary-bar-value">{{ $supplierPhone ?: '-' }}</span>

                                        </div>

                                        <div class="dg-summary-bar-item">

                                            <span class="dg-summary-bar-label text-muted">Email</span>

                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>

                                            <span class="dg-summary-bar-value">{{ $return->supplier->email ?? '-' }}</span>

                                        </div>

                                        <div class="dg-summary-bar-item">

                                            <span class="dg-summary-bar-label text-muted">VAT No</span>

                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>

                                            <span class="dg-summary-bar-value">{{ !empty($return->supplier?->tax_no) ? $return->supplier->tax_no : '-' }}</span>

                                        </div>

                                        <div class="dg-summary-bar-item">

                                            <span class="dg-summary-bar-label text-muted">PAN No</span>

                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>

                                            <span class="dg-summary-bar-value">-</span>

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

                                            <span class="dg-summary-bar-label text-muted">Return No</span>

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

                                        <div class="dg-summary-bar-item">

                                            <span class="dg-summary-bar-label text-muted">Return Date</span>

                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>

                                            <span class="dg-summary-bar-value">{{ $return->return_date?->format('d-m-Y') ?? '-' }}</span>

                                        </div>

                                        <div class="dg-summary-bar-item">

                                            <span class="dg-summary-bar-label text-muted">Original Invoice No</span>

                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>

                                            <span class="dg-summary-bar-value">{{ $invoice->invoice_no ?? '-' }}</span>

                                        </div>

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

                                                <span class="dg-summary-bar-label text-muted">Note</span>

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

                                                <th scope="col" class="dg-col-num">VAT</th>

                                                <th scope="col" class="dg-col-num">Total</th>

                                            </tr>

                                        </thead>

                                        <tbody class="dg-body">

                                            @foreach ($return->items as $item)

                                                <tr class="dg-row">

                                                    <td>{{ $loop->iteration }}</td>

                                                    <td>{{ $item->product->name ?? ($item->purchaseItem->service->name ?? 'Deleted Item') }}</td>

                                                    <td class="dg-col-num">{{ number_format($item->quantity, 2) }}</td>

                                                    <td class="dg-col-num">{{ number_format($item->unit_price, 2) }}</td>

                                                    <td class="dg-col-num">{{ number_format($item->vat_amount, 2) }}</td>

                                                    <td class="dg-col-num">{{ number_format($item->total_price, 2) }}</td>

                                                </tr>

                                            @endforeach

                                        </tbody>

                                        <tfoot>

                                            <tr>

                                                <th colspan="5" class="text-end">Subtotal</th>

                                                <td class="dg-col-num">{{ number_format($return->subtotal, 2) }}</td>

                                            </tr>

                                            <tr>

                                                <th colspan="5" class="text-end">VAT</th>

                                                <td class="dg-col-num">{{ number_format($return->total_vat, 2) }}</td>

                                            </tr>

                                            <tr>

                                                <th colspan="5" class="text-end">Return Total</th>

                                                <td class="dg-col-num fw-bold">{{ number_format($return->grand_total, 2) }}</td>

                                            </tr>

                                        </tfoot>

                                    </table>

                                </div>

                            </div>

                        </section>



                        <footer>

                            <div class="row g-2 mt-1">

                                <div class="col-6 text-center">

                                    <div class="dg-signature-line"></div>

                                    <div class="dg-signature-label">Supplier Signature</div>

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

