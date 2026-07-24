@extends('company.layout')

@section('title', 'Edit Purchase Payment')

@section('content')

<div class="dg-page">

    <header class="dg-toolbar">
        <div class="container-fluid">
            <div class="row align-items-center g-2">
                <div class="col">
                    <h1 class="h4 mb-0">Edit Purchase Payment</h1>
                </div>
                <div class="col-auto">
                    <nav class="btn-group" aria-label="Purchase payment toolbar">
                        <a href="{{ route('company.purchase-payments.index') }}" class="btn btn-outline-secondary dg-btn">Payment List</a>
                        <a href="{{ route('company.purchase-payments.show', $payment->id) }}" class="btn btn-outline-secondary dg-btn">View Payment</a>
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
                <div class="alert alert-danger dg-alert" role="alert">{{ session('error') }}</div>
            @endif

            <form id="dgForm" method="POST" action="{{ route('company.purchase-payments.update', $payment->id) }}" class="dg-form">
                @csrf

                <section class="dg-section">
                    <article class="card dg-card">
                        <header class="card-header dg-card-header">
                            <h2 class="h6 mb-0">Payment Information</h2>
                        </header>
                        <div class="card-body dg-card-body">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label" for="payment_no">Payment No</label>
                                    <input type="text" id="payment_no" class="form-control dg-input" value="{{ $payment->payment_no }}" readonly>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="financial_year">Financial Year</label>
                                    <input type="text" id="financial_year" class="form-control dg-input" value="{{ $payment->financialYear->name ?? '-' }}" readonly>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="payment_date">Payment Date</label>
                                    <input type="date" name="payment_date" id="payment_date" class="form-control dg-input" value="{{ old('payment_date', $payment->payment_date?->format('Y-m-d')) }}" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="status">Status</label>
                                    <input type="text" id="status" class="form-control dg-input" value="{{ (int) $payment->status === 1 ? 'Active' : 'Cancelled' }}" readonly>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="supplier_name">Supplier</label>
                                    <input type="text" id="supplier_name" class="form-control dg-input" value="{{ $payment->supplier->name ?? '-' }}" readonly>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="invoice_no">Invoice No</label>
                                    <input type="text" id="invoice_no" class="form-control dg-input" value="{{ $payment->invoice->invoice_no ?? '-' }}" readonly>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="account_name">Account</label>
                                    <input type="text" id="account_name" class="form-control dg-input" value="{{ $payment->account->account_name ?? '-' }}" readonly>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="amount">Paid Amount</label>
                                    <input type="text" id="amount" class="form-control dg-input text-end" value="{{ number_format($payment->amount, 2) }}" readonly>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="payment_method">Payment Method</label>
                                    <input type="text" id="payment_method" class="form-control dg-input" value="{{ $payment->payment_method ?? '-' }}" readonly>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="reference_no">Reference No</label>
                                    <input type="text" id="reference_no" class="form-control dg-input" value="{{ $payment->reference_no ?? '-' }}" readonly>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="receipt_file">Receipt</label>
                                    <input type="text" id="receipt_file" class="form-control dg-input" value="{{ $payment->receipt_file ? 'Attached' : '-' }}" readonly>
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="note">Note</label>
                                    <textarea name="note" id="note" rows="3" class="form-control dg-input">{{ old('note', $payment->note) }}</textarea>
                                </div>
                            </div>
                        </div>
                        <footer class="card-footer dg-card-footer">
                            <button type="submit" class="btn btn-primary dg-btn">Update Payment</button>
                            <a href="{{ route('company.purchase-payments.show', $payment->id) }}" class="btn btn-outline-secondary dg-btn">Cancel</a>
                        </footer>
                    </article>
                </section>
            </form>
        </div>
    </main>
</div>

@endsection
