@extends('company.layout')

@section('title', 'Expense')

@section('content')

@php
    $user = auth()->user();
    $canCreate = $user && ($user->role_id == 2 || $user->hasPermission('create_expense'));
    $canEdit = $user && ($user->role_id == 2 || $user->hasPermission('edit_expense'));
    $canPrint = $user && ($user->role_id == 2 || $user->hasPermission('print_expense'));
    $canManageCategories = $user && ($user->role_id == 2 || $user->hasPermission('manage_expense_categories'));
@endphp

<div class="dg-page">

    <header class="dg-toolbar">
        <div class="container-fluid">
            <div class="d-flex flex-nowrap align-items-center gap-2">

                <div class="flex-shrink-0">
                    <h1 class="h4 mb-0">Expense</h1>
                </div>

                <div class="flex-fill d-flex justify-content-end align-items-center gap-2 flex-nowrap">
                    @if ($canPrint)
                        <a href="{{ route('company.expense.print', request()->query()) }}" target="_blank" class="btn btn-outline-secondary dg-btn">Print</a>
                    @endif

                    @if ($canManageCategories)
                        <a href="{{ route('company.expense-category.index') }}" class="btn btn-outline-secondary dg-btn">Categories</a>
                    @endif

                    @if ($canCreate)
                        <a href="{{ route('company.expense.create') }}" class="btn btn-success dg-btn">Add Expense</a>
                    @endif
                </div>

            </div>
        </div>
    </header>

    <main class="dg-container">
        <div class="container-fluid">

            @if (session('success'))
                <div class="alert alert-success dg-alert" role="alert">{{ session('success') }}</div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger dg-alert" role="alert">{{ session('error') }}</div>
            @endif

            <section class="dg-section">
                <div class="dg-summary d-flex flex-row flex-nowrap justify-content-center align-items-center gap-3 mb-0 w-100">

                    <div class="dg-summary-item mb-0 border-0 p-0">
                        <span>Active Total :</span>
                        <span class="fw-bold">{{ number_format($totalAmount, 2) }}</span>
                    </div>

                    <span>|</span>

                    <div class="dg-summary-item mb-0 border-0 p-0">
                        <span>Active Entries :</span>
                        <span class="fw-bold">{{ $activeCount }}</span>
                    </div>

                    <span>|</span>

                    <div class="dg-summary-item mb-0 border-0 p-0">
                        <span>Listed Records :</span>
                        <span class="fw-bold">{{ $totalCount }}</span>
                    </div>

                </div>
            </section>

            <section class="dg-section dg-filter">
                <article class="card dg-card">
                    <header class="card-header dg-card-header">
                        <h2 class="h6 mb-0">Filter</h2>
                    </header>
                    <div class="card-body dg-card-body dg-filter-card-body">
                        <form method="GET" action="{{ route('company.expense.index') }}" class="dg-filter-form">
                            <div class="dg-filter-grid">
                                <div class="dg-filter-field">
                                    <label for="search" class="dg-filter-label">Search</label>
                                    <input type="text" name="search" id="search" class="form-control dg-input dg-filter-control" value="{{ request('search') }}" placeholder="Expense No / Reference No">
                                </div>

                                <div class="dg-filter-field dg-filter-field-fy">
                                    <label for="financial_year_id" class="dg-filter-label">Financial Year</label>
                                    <select name="financial_year_id" id="financial_year_id" class="form-select dg-select dg-filter-control">
                                        <option value="">All Years</option>
                                        @foreach ($financialYears as $financialYear)
                                            <option value="{{ $financialYear->id }}" @selected(
                                                request()->has('financial_year_id')
                                                    ? request('financial_year_id') == $financialYear->id
                                                    : ($activeFy && $activeFy->id == $financialYear->id)
                                            )>{{ $financialYear->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="dg-filter-field dg-filter-field-date">
                                    <label for="start_date" class="dg-filter-label">Date From</label>
                                    <input type="date" name="start_date" id="start_date" class="form-control dg-input dg-filter-control" value="{{ request('start_date') }}">
                                </div>

                                <div class="dg-filter-field dg-filter-field-date">
                                    <label for="end_date" class="dg-filter-label">Date To</label>
                                    <input type="date" name="end_date" id="end_date" class="form-control dg-input dg-filter-control" value="{{ request('end_date') }}">
                                </div>

                                <div class="dg-filter-field">
                                    <label for="expense_category_id" class="dg-filter-label">Category</label>
                                    <select name="expense_category_id" id="expense_category_id" class="form-select dg-select dg-filter-control">
                                        <option value="">All Categories</option>
                                        @foreach ($categories as $category)
                                            <option value="{{ $category->id }}" @selected(request('expense_category_id') == $category->id)>{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="dg-filter-field dg-filter-field-account">
                                    <label for="account_id" class="dg-filter-label">Account</label>
                                    <select name="account_id" id="account_id" class="form-select dg-select dg-filter-control">
                                        <option value="">All Accounts</option>
                                        @foreach ($accounts as $account)
                                            <option value="{{ $account->id }}" @selected(request('account_id') == $account->id)>{{ $account->account_name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="dg-filter-field dg-filter-field-status">
                                    <label for="status" class="dg-filter-label">Status</label>
                                    <select name="status" id="status" class="form-select dg-select dg-filter-control">
                                        <option value="" @selected(request()->has('status') && request('status') === '')>All</option>
                                        <option value="1" @selected(!request()->has('status') || request('status') === '1')>Active</option>
                                        <option value="0" @selected(request('status') === '0')>Cancelled</option>
                                    </select>
                                </div>

                                @if (request('per_page'))
                                    <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                                @endif

                                <div class="dg-filter-actions">
                                    <button type="submit" class="btn btn-primary dg-btn">Filter</button>
                                    <a href="{{ route('company.expense.index') }}" class="btn btn-outline-secondary dg-btn">Reset</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </article>
            </section>

            <section class="dg-section">
                <article class="card dg-card">
                    <header class="card-header dg-card-header dg-list-card-header">
                        <h2 class="dg-list-card-title">Expense List</h2>

                        <form method="GET" action="{{ route('company.expense.index') }}" class="dg-list-per-page">
                            <input type="hidden" name="search" value="{{ request('search') }}">
                            <input type="hidden" name="financial_year_id" value="{{ request('financial_year_id', $activeFy?->id) }}">
                            <input type="hidden" name="start_date" value="{{ request('start_date') }}">
                            <input type="hidden" name="end_date" value="{{ request('end_date') }}">
                            <input type="hidden" name="expense_category_id" value="{{ request('expense_category_id') }}">
                            <input type="hidden" name="account_id" value="{{ request('account_id') }}">
                            <input type="hidden" name="status" value="{{ request()->has('status') ? request('status') : '1' }}">

                            <label for="per_page" class="dg-list-per-page-label">Show</label>
                            <select name="per_page" id="per_page" class="form-select dg-select dg-list-per-page-select" onchange="this.form.submit()">
                                <option value="10" @selected($perPage == 10)>10</option>
                                <option value="20" @selected($perPage == 20)>20</option>
                                <option value="50" @selected($perPage == 50)>50</option>
                                <option value="100" @selected($perPage == 100)>100</option>
                                <option value="200" @selected($perPage == 200)>200</option>
                            </select>
                        </form>
                    </header>

                    <div class="card-body dg-card-body dg-list-card-body">
                        <div class="table-responsive">
                            <table class="table dg-table">
                                <thead class="dg-head">
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">Expense No</th>
                                        <th scope="col">Reference No</th>
                                        <th scope="col">Category</th>
                                        <th scope="col">Account</th>
                                        <th scope="col">Amount</th>
                                        <th scope="col">Date</th>
                                        <th scope="col">Status</th>
                                        <th scope="col" width="170">Action</th>
                                    </tr>
                                </thead>

                                <tbody class="dg-body">
                                    @forelse ($expenses as $expense)
                                        <tr class="dg-row">
                                            <td>{{ $expenses->firstItem() + $loop->index }}</td>
                                            <td>{{ $expense->expense_no }}</td>
                                            <td>{{ $expense->reference_no ?: '-' }}</td>
                                            <td>{{ $expense->category->name ?? '-' }}</td>
                                            <td>{{ $expense->account->account_name ?? '-' }}</td>
                                            <td>{{ number_format($expense->amount, 2) }}</td>
                                            <td>{{ $expense->expense_date?->format('d-m-Y') ?? '-' }}</td>
                                            <td>
                                                @if ($expense->isActive())
                                                    <span class="badge bg-success">Active</span>
                                                @else
                                                    <span class="badge bg-secondary">Cancelled</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group" aria-label="Expense actions for {{ $expense->expense_no }}">
                                                    <a href="{{ route('company.expense.show', $expense->id) }}" class="btn btn-sm btn-outline-info dg-btn">View</a>
                                                    @if ($canEdit && $expense->isActive())
                                                        <a href="{{ route('company.expense.edit', $expense->id) }}" class="btn btn-sm btn-outline-success dg-btn">Edit</a>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr class="dg-row">
                                            <td colspan="9" class="text-center">No expense records found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="dg-list-footer">
                            <p class="dg-list-meta">
                                Showing {{ $expenses->firstItem() ?? 0 }} to {{ $expenses->lastItem() ?? 0 }} of {{ $expenses->total() }} records
                            </p>

                            <div class="dg-pagination">
                                {{ $expenses->withQueryString()->onEachSide(1)->links('pagination::bootstrap-5') }}
                            </div>
                        </div>
                    </div>
                </article>
            </section>

        </div>
    </main>

</div>

@endsection
