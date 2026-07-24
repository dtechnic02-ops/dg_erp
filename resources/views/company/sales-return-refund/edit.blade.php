@extends('company.layout')

@section('title', 'Edit Sales Return Refund')

@section('content')

<div class="dg-page">

    <header class="dg-toolbar">
        <div class="container-fluid">
            <div class="row align-items-center g-2">
                <div class="col">
                    <h1 class="h4 mb-0">Edit Sales Return Refund</h1>
                </div>
                <div class="col-auto">
                    <nav class="btn-group" aria-label="Sales return refund toolbar">
                        <a href="{{ route('company.sales-return-refund.index') }}" class="btn btn-outline-secondary dg-btn">Refund List</a>
                        <a href="{{ route('company.sales-return-refund.show', $refund->id) }}" class="btn btn-outline-secondary dg-btn">View Refund</a>
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

            <form id="dgForm" method="POST" action="{{ route('company.sales-return-refund.update', $refund->id) }}" class="dg-form">
                @csrf

                <section class="dg-section">
                    <article class="card dg-card">
                        <header class="card-header dg-card-header">
                            <h2 class="h6 mb-0">Refund Information</h2>
                        </header>
                        <div class="card-body dg-card-body">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label" for="refund_no">Refund No</label>
                                    <input type="text" id="refund_no" class="form-control dg-input" value="{{ $refund->refund_no }}" readonly>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="financial_year">Financial Year</label>
                                    <input type="text" id="financial_year" class="form-control dg-input" value="{{ $refund->financialYear->name ?? '-' }}" readonly>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="refund_date">Refund Date</label>
                                    <input type="date" name="refund_date" id="refund_date" class="form-control dg-input" value="{{ old('refund_date', $refund->refund_date?->format('Y-m-d')) }}" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="status">Status</label>
                                    <input type="text" id="status" class="form-control dg-input" value="{{ $refund->isActive() ? 'Active' : 'Cancelled' }}" readonly>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="customer_name">Customer</label>
                                    <input type="text" id="customer_name" class="form-control dg-input" value="{{ $refund->customer->name ?? '-' }}" readonly>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="return_no">Return No</label>
                                    <input type="text" id="return_no" class="form-control dg-input" value="{{ $refund->salesReturn->return_no ?? '-' }}" readonly>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="account_name">Account</label>
                                    <input type="text" id="account_name" class="form-control dg-input" value="{{ $refund->account->account_name ?? '-' }}" readonly>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="refund_amount">Refund Amount</label>
                                    <input type="text" id="refund_amount" class="form-control dg-input text-end" value="{{ number_format($refund->refund_amount, 2) }}" readonly>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="adjust_amount">Adjustment Amount</label>
                                    <input type="text" id="adjust_amount" class="form-control dg-input text-end" value="{{ number_format($refund->adjust_amount, 2) }}" readonly>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="cash_amount">Cash Amount</label>
                                    <input type="text" id="cash_amount" class="form-control dg-input text-end" value="{{ number_format($refund->cash_amount, 2) }}" readonly>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="reference_no">Reference No</label>
                                    <input type="text" id="reference_no" class="form-control dg-input" value="{{ $refund->reference_no ?? '-' }}" readonly>
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="note">Note</label>
                                    <textarea name="note" id="note" rows="3" class="form-control dg-input">{{ old('note', $refund->note) }}</textarea>
                                </div>
                            </div>
                        </div>
                        <footer class="card-footer dg-card-footer">
                            <button type="submit" class="btn btn-primary dg-btn">Update Refund</button>
                            <a href="{{ route('company.sales-return-refund.show', $refund->id) }}" class="btn btn-outline-secondary dg-btn">Cancel</a>
                        </footer>
                    </article>
                </section>
            </form>
        </div>
    </main>
</div>

@endsection
