@extends('company.layout')

@section('title', 'Sales Return Refund Print')

@section('content')

@php
    $company = auth()->user()->company;
    $salesReturn = $refund->salesReturn;

    $refundTotalAmount = (float) $refund->refund_amount;
    $amountRupees = (int) floor($refundTotalAmount);
    $amountPaisa = (int) round(($refundTotalAmount - $amountRupees) * 100);
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
                            <h1 class="dg-invoice-print-title">SALES RETURN REFUND</h1>
                        </div>

                        <section class="dg-invoice-print-header-col dg-invoice-print-header-right">
                            <h2 class="dg-invoice-party-title">Customer Information</h2>
                            <div class="dg-invoice-field-list">
                                @if (!empty($refund->customer?->name))
                                    <div class="dg-invoice-field-row">
                                        <span class="dg-invoice-field-label">Customer Name</span>
                                        <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                        <span class="dg-invoice-field-value">{{ $refund->customer->name }}</span>
                                    </div>
                                @endif

                                @if (!empty($refund->customer?->authority_name))
                                    <div class="dg-invoice-field-row">
                                        <span class="dg-invoice-field-label">Contact Person</span>
                                        <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                        <span class="dg-invoice-field-value">{{ $refund->customer->authority_name }}</span>
                                    </div>
                                @endif

                                @if (!empty($refund->customer?->address))
                                    <div class="dg-invoice-field-row">
                                        <span class="dg-invoice-field-label">Address</span>
                                        <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                        <span class="dg-invoice-field-value">{{ $refund->customer->address }}</span>
                                    </div>
                                @endif

                                @if (!empty($refund->customer?->mobile))
                                    <div class="dg-invoice-field-row">
                                        <span class="dg-invoice-field-label">Phone</span>
                                        <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                        <span class="dg-invoice-field-value">{{ $refund->customer->mobile }}</span>
                                    </div>
                                @elseif (!empty($refund->customer?->telephone))
                                    <div class="dg-invoice-field-row">
                                        <span class="dg-invoice-field-label">Phone</span>
                                        <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                        <span class="dg-invoice-field-value">{{ $refund->customer->telephone }}</span>
                                    </div>
                                @endif

                                @if (!empty($refund->customer?->email))
                                    <div class="dg-invoice-field-row">
                                        <span class="dg-invoice-field-label">Email</span>
                                        <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                        <span class="dg-invoice-field-value">{{ $refund->customer->email }}</span>
                                    </div>
                                @endif

                                @if ($refund->customer)
                                    <div class="dg-invoice-field-row">
                                        <span class="dg-invoice-field-label">VAT No</span>
                                        <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                        <span class="dg-invoice-field-value">{{ !empty($refund->customer->tax_no) ? $refund->customer->tax_no : '-' }}</span>
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
                        @if (!empty($refund->refund_no))
                            <div class="dg-invoice-field-row">
                                <span class="dg-invoice-field-label">Refund No</span>
                                <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                <span class="dg-invoice-field-value">{{ $refund->refund_no }}</span>
                            </div>
                        @endif

                        @if (!empty($refund->refund_date))
                            <div class="dg-invoice-field-row">
                                <span class="dg-invoice-field-label">Refund Date</span>
                                <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                <span class="dg-invoice-field-value">{{ $refund->refund_date->format('d-m-Y') }}</span>
                            </div>
                        @endif

                        <div class="dg-invoice-field-row">
                            <span class="dg-invoice-field-label">Refund Status</span>
                            <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                            <span class="dg-invoice-field-value">
                                @if ((int) $refund->status === 0)
                                    <span class="dg-badge dg-badge-status dg-badge-secondary">Cancelled</span>
                                @else
                                    <span class="dg-badge dg-badge-status dg-badge-success">Active</span>
                                @endif
                            </span>
                        </div>

                        @if (!empty($salesReturn?->return_no))
                            <div class="dg-invoice-field-row">
                                <span class="dg-invoice-field-label">Original Return No</span>
                                <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                <span class="dg-invoice-field-value">{{ $salesReturn->return_no }}</span>
                            </div>
                        @endif
                    </div>

                    <section class="dg-invoice-lines">
                        <h2 class="dg-invoice-lines-title">Refund Details</h2>
                        <div class="dg-table-scroll">
                            <table class="table dg-table dg-invoice-table">
                                <thead class="dg-head">
                                    <tr>
                                        <th scope="col" class="dg-col-num">#</th>
                                        <th scope="col">Description</th>
                                        <th scope="col">Reference</th>
                                        <th scope="col" class="dg-col-date">Date</th>
                                        <th scope="col" class="dg-col-num">Amount</th>
                                    </tr>
                                </thead>
                                <tbody class="dg-body">
                                    @php $lineNo = 0; @endphp
                                    @foreach ($refund->adjustments as $adjustment)
                                        @php $lineNo++; @endphp
                                        <tr class="dg-row">
                                            <td class="dg-col-num">{{ $lineNo }}</td>
                                            <td class="dg-invoice-item-name">Original Invoice Adjustment</td>
                                            <td>{{ $adjustment->invoice->invoice_no ?? '-' }}</td>
                                            <td class="dg-col-date">
                                                @if (!empty($adjustment->invoice?->sale_date))
                                                    {{ \Illuminate\Support\Carbon::parse($adjustment->invoice->sale_date)->format('d-m-Y') }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td class="dg-col-num">{{ number_format($adjustment->adjust_amount, 2) }}</td>
                                        </tr>
                                    @endforeach
                                    @if ((float) $refund->cash_amount > 0)
                                        @php $lineNo++; @endphp
                                        <tr class="dg-row">
                                            <td class="dg-col-num">{{ $lineNo }}</td>
                                            <td class="dg-invoice-item-name">Cash Refund To Customer</td>
                                            <td>{{ $refund->account->account_name ?? '-' }}</td>
                                            <td class="dg-col-date">{{ $refund->refund_date?->format('d-m-Y') ?? '-' }}</td>
                                            <td class="dg-col-num">{{ number_format($refund->cash_amount, 2) }}</td>
                                        </tr>
                                    @endif
                                    @if ($lineNo === 0)
                                        <tr class="dg-row">
                                            <td colspan="5" class="text-center">No refund details found.</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <div class="dg-invoice-footer dg-invoice-print-footer">
                        <section class="dg-invoice-payment">
                            <h2 class="dg-invoice-party-title">Settlement Information</h2>
                            <dl class="dg-invoice-dl">
                                <div class="dg-invoice-dl-row">
                                    <dt class="dg-invoice-dl-label">Adjustment Amount</dt>
                                    <dd class="dg-invoice-dl-value">{{ number_format($refund->adjust_amount, 2) }}</dd>
                                </div>
                                <div class="dg-invoice-dl-row">
                                    <dt class="dg-invoice-dl-label">Cash Refund Amount</dt>
                                    <dd class="dg-invoice-dl-value dg-summary-paid">{{ number_format($refund->cash_amount, 2) }}</dd>
                                </div>
                            </dl>

                            @if (!empty($refund->note))
                                <div class="dg-invoice-note-block">
                                    <h3 class="dg-invoice-note-title">Note</h3>
                                    <div class="dg-invoice-note-body">{{ $refund->note }}</div>
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
                                    <span class="dg-summary-label">Adjustment</span>
                                    <span class="dg-summary-value">{{ number_format($refund->adjust_amount, 2) }}</span>
                                </div>
                                <div class="dg-summary-item">
                                    <span class="dg-summary-label">Cash Refund</span>
                                    <span class="dg-summary-value">{{ number_format($refund->cash_amount, 2) }}</span>
                                </div>

                                <div class="dg-invoice-totals-divider"></div>

                                <div class="dg-summary-item dg-summary-total">
                                    <span class="dg-summary-label">Refund Total</span>
                                    <span class="dg-summary-value">{{ number_format($refund->refund_amount, 2) }}</span>
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
