@extends('company.layout')

@section('title', 'Edit Purchase Return')

@section('content')

<div class="dg-page">

    <header class="dg-toolbar">
        <div class="container-fluid">
            <div class="row align-items-center g-2">
                <div class="col">
                    <h1 class="h4 mb-0">Edit Purchase Return</h1>
                </div>
                <div class="col-auto">
                    <nav class="btn-group" aria-label="Purchase return toolbar">
                        <a href="{{ route('company.purchase-return.index') }}" class="btn btn-outline-secondary dg-btn">Return List</a>
                        <a href="{{ route('company.purchase-return.show', $return->id) }}" class="btn btn-outline-secondary dg-btn">View Return</a>
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

            <form id="dgForm" method="POST" action="{{ route('company.purchase-return.update', $return->id) }}" class="dg-form">
                @csrf

                <section class="dg-section">
                    <article class="card dg-card">
                        <header class="card-header dg-card-header">
                            <h2 class="h6 mb-0">Return Information</h2>
                        </header>
                        <div class="card-body dg-card-body">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label" for="return_no">Return No</label>
                                    <input type="text" id="return_no" class="form-control dg-input" value="{{ $return->return_no }}" readonly>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="financial_year">Financial Year</label>
                                    <input type="text" id="financial_year" class="form-control dg-input" value="{{ $return->financialYear->name ?? '-' }}" readonly>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="return_date">Return Date</label>
                                    <input type="date" name="return_date" id="return_date" class="form-control dg-input" value="{{ old('return_date', $return->return_date?->format('Y-m-d')) }}" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="status">Status</label>
                                    <input type="text" id="status" class="form-control dg-input" value="{{ (int) $return->status === 1 ? 'Active' : 'Cancelled' }}" readonly>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="supplier_name">Supplier</label>
                                    <input type="text" id="supplier_name" class="form-control dg-input" value="{{ $return->supplier->name ?? '-' }}" readonly>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="invoice_no">Invoice No</label>
                                    <input type="text" id="invoice_no" class="form-control dg-input" value="{{ $return->invoice->invoice_no ?? '-' }}" readonly>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="grand_total">Grand Total</label>
                                    <input type="text" id="grand_total" class="form-control dg-input text-end" value="{{ number_format($return->grand_total, 2) }}" readonly>
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="note">Note</label>
                                    <textarea name="note" id="note" rows="3" class="form-control dg-input">{{ old('note', $return->note) }}</textarea>
                                </div>
                            </div>
                        </div>
                        <footer class="card-footer dg-card-footer">
                            <button type="submit" class="btn btn-primary dg-btn">Update Return</button>
                            <a href="{{ route('company.purchase-return.show', $return->id) }}" class="btn btn-outline-secondary dg-btn">Cancel</a>
                        </footer>
                    </article>
                </section>
            </form>
        </div>
    </main>
</div>

@endsection
