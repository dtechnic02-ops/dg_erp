@extends('company.layout')

@section('title', 'Edit Expense')

@section('content')

<div class="dg-page">

    <header class="dg-toolbar">
        <div class="container-fluid">
            <div class="row align-items-center g-2">
                <div class="col">
                    <h1 class="h4 mb-0">Edit Expense</h1>
                </div>
                <div class="col-auto">
                    <nav class="btn-group" aria-label="Expense toolbar">
                        <a href="{{ route('company.expense.index') }}" class="btn btn-outline-secondary dg-btn">Expense List</a>
                        <a href="{{ route('company.expense.show', $expense->id) }}" class="btn btn-outline-secondary dg-btn">View Expense</a>
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

            <form method="POST" action="{{ route('company.expense.update', $expense->id) }}" enctype="multipart/form-data" class="dg-form">
                @csrf

                <section class="dg-section">
                    <article class="card dg-card">
                        <header class="card-header dg-card-header">
                            <h2 class="h6 mb-0">Expense Information</h2>
                        </header>
                        <div class="card-body dg-card-body">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label" for="expense_no">Expense No</label>
                                    <input type="text" id="expense_no" class="form-control dg-input" value="{{ $expense->expense_no }}" readonly>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label" for="financial_year">Financial Year</label>
                                    <input type="text" id="financial_year" class="form-control dg-input" value="{{ $expense->financialYear->name ?? '-' }}" readonly>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label" for="status">Status</label>
                                    <input type="text" id="status" class="form-control dg-input" value="{{ $expense->isActive() ? 'Active' : 'Cancelled' }}" readonly>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label" for="expense_category_id">Category <span class="text-danger">*</span></label>
                                    <select name="expense_category_id" id="expense_category_id" class="form-select dg-select" required>
                                        <option value="">Select Category</option>
                                        @foreach ($categories as $category)
                                            <option value="{{ $category->id }}" @selected(old('expense_category_id', $expense->expense_category_id) == $category->id)>{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label" for="reference_no">Reference No</label>
                                    <input type="text" name="reference_no" id="reference_no" class="form-control dg-input" value="{{ old('reference_no', $expense->reference_no) }}">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label" for="account_id">Account <span class="text-danger">*</span></label>
                                    <select name="account_id" id="account_id" class="form-select dg-select" required>
                                        <option value="">Select Account</option>
                                        @foreach ($accounts as $account)
                                            <option value="{{ $account->id }}" @selected(old('account_id', $expense->account_id) == $account->id)>
                                                {{ $account->account_name }} ({{ number_format($account->current_balance, 2) }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label" for="amount">Amount <span class="text-danger">*</span></label>
                                    <input type="number" name="amount" id="amount" step="0.01" min="0.01" class="form-control dg-input" value="{{ old('amount', $expense->amount) }}" required>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label" for="expense_date">Expense Date <span class="text-danger">*</span></label>
                                    <input type="date" name="expense_date" id="expense_date" class="form-control dg-input" value="{{ old('expense_date', $expense->expense_date?->format('Y-m-d')) }}" required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="attachment">Attachment</label>
                                    <input type="file" name="attachment" id="attachment" class="form-control dg-input">
                                    @if ($expense->attachment)
                                        <div class="small mt-1">
                                            <a href="{{ asset($expense->attachment) }}" target="_blank">View current attachment</a>
                                        </div>
                                    @endif
                                </div>

                                <div class="col-12">
                                    <label class="form-label" for="note">Note</label>
                                    <textarea name="note" id="note" rows="3" class="form-control dg-input">{{ old('note', $expense->note) }}</textarea>
                                </div>
                            </div>
                        </div>
                        <footer class="card-footer dg-card-footer">
                            <button type="submit" class="btn btn-primary dg-btn">Update Expense</button>
                            <a href="{{ route('company.expense.show', $expense->id) }}" class="btn btn-outline-secondary dg-btn">Cancel</a>
                        </footer>
                    </article>
                </section>
            </form>

        </div>
    </main>
</div>

@endsection
