@extends('company.layout')

@section('content')

<div class="dg-page">

    <header class="dg-toolbar">
        <div class="container-fluid">
            <div class="row align-items-center g-2">
                <div class="col">
                    <h1 class="h4 mb-0">Receive Sales Payment</h1>
                    <p class="text-muted small mb-0">Receive customer invoice payment</p>
                </div>
                <div class="col-auto">
                    <nav class="btn-group" aria-label="Sales payment create toolbar">
                        <a href="{{ route('company.sales-payment.index') }}" class="btn btn-outline-secondary dg-btn">Back</a>
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

            <form method="POST" action="{{ route('company.sales-payment.store') }}" enctype="multipart/form-data" id="dgSalesPaymentForm">
                @csrf

                <input type="hidden" name="sales_invoice_id" value="{{ $invoice->id }}">

                <div class="row g-3">
                    <div class="col-lg-8">
                        <section class="dg-section">
                            <article class="card dg-card">
                                <header class="card-header dg-card-header">
                                    <h2 class="h6 mb-0">Payment Details</h2>
                                </header>
                                <div class="card-body dg-card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="payment_no_display" class="form-label">Payment No</label>
                                            <input type="text" id="payment_no_display" class="form-control dg-input" value="{{ $paymentNo }}" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="payment_date" class="form-label">Payment Date</label>
                                            <input type="date" name="payment_date" id="payment_date" class="form-control dg-input" value="{{ old('payment_date', date('Y-m-d')) }}" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="customer_display" class="form-label">Customer</label>
                                            <input type="text" id="customer_display" class="form-control dg-input" value="{{ $invoice->customer->name ?? '-' }}" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="invoice_display" class="form-label">Invoice No</label>
                                            <input type="text" id="invoice_display" class="form-control dg-input" value="{{ $invoice->invoice_no }}" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="account_id" class="form-label">Receive Account</label>
                                            <select name="account_id" id="account_id" class="form-select dg-select" required>
                                                <option value="">Select Account</option>
                                                @foreach ($accounts as $account)
                                                    <option value="{{ $account->id }}" @selected(old('account_id') == $account->id)>
                                                        {{ $account->account_name }} - {{ number_format($account->current_balance, 2) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="paid_amount" class="form-label">Payment Amount</label>
                                            <input type="number" step="0.01" name="paid_amount" id="paid_amount" class="form-control dg-input" value="{{ old('paid_amount', $remainingAmount) }}" max="{{ $remainingAmount }}" data-remaining="{{ $remainingAmount }}" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="reference_no" class="form-label">Reference No</label>
                                            <input type="text" name="reference_no" id="reference_no" class="form-control dg-input" value="{{ old('reference_no') }}" maxlength="100">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="receipt_file" class="form-label">Receipt File</label>
                                            <input type="file" name="receipt_file" id="receipt_file" class="form-control dg-input" accept=".pdf,.jpg,.jpeg,.png">
                                        </div>
                                        <div class="col-12">
                                            <label for="note" class="form-label">Note</label>
                                            <textarea name="note" id="note" class="form-control dg-input" rows="4">{{ old('note') }}</textarea>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        </section>
                    </div>

                    <div class="col-lg-4">
                        <section class="dg-section dg-summary">
                            <article class="card dg-card">
                                <header class="card-header dg-card-header">
                                    <h2 class="h6 mb-0">Payment Summary</h2>
                                </header>
                                <div class="card-body dg-card-body">
                                    <table class="table table-sm mb-3">
                                        <tbody>
                                            <tr>
                                                <th scope="row">Invoice Total</th>
                                                <td class="text-end">{{ number_format($invoice->grand_total, 2) }}</td>
                                            </tr>
                                            <tr>
                                                <th scope="row">Already Paid</th>
                                                <td class="text-end text-success">{{ number_format($totalPaid, 2) }}</td>
                                            </tr>
                                            <tr>
                                                <th scope="row">Remaining Due</th>
                                                <td class="text-end text-danger" id="summary_remaining_due">{{ number_format($remainingAmount, 2) }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <button type="submit" class="btn btn-success w-100 dg-btn">Save Payment</button>
                                </div>
                            </article>
                        </section>
                    </div>
                </div>
            </form>

        </div>
    </main>

</div>

@push('scripts')
    <script src="{{ asset('assets/company/js/dg.js') }}"></script>
    <script>
        if (window.DG && DG.salesPayment) {
            DG.salesPayment.init();
        }
    </script>
@endpush

@endsection
