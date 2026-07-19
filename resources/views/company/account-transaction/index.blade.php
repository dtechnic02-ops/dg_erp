@extends('company.layout')

@section('content')

<div class="dg-page">

    <header class="dg-toolbar @if (request('print')) d-print-none @endif">
        <div class="container-fluid">
            <div class="d-flex flex-nowrap align-items-center gap-2">

                <div class="flex-shrink-0">
                    <h1 class="h4 mb-0">Account Transactions</h1>
                </div>

                <div class="flex-fill d-flex justify-content-end align-items-center gap-2 flex-wrap flex-md-nowrap">
                    <a href="{{ route('company.account-transaction.index', array_merge(request()->query(), ['print' => 1])) }}" target="_blank" class="btn btn-outline-secondary dg-btn">Print</a>
                </div>

            </div>
        </div>
    </header>

    <main class="dg-container">
        <div class="container-fluid">

            @if ($errors->any())
                <div class="alert alert-danger dg-alert d-print-none" role="alert">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('success'))
                <div class="alert alert-success dg-alert d-print-none" role="alert">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger dg-alert d-print-none" role="alert">
                    {{ session('error') }}
                </div>
            @endif

            <section class="dg-section dg-filter @if (request('print')) d-print-none @endif">
                <article class="card dg-card">
                    <header class="card-header dg-card-header">
                        <h2 class="h6 mb-0">Filter</h2>
                    </header>

                    <div class="card-body dg-card-body">
                        <form method="GET" action="{{ route('company.account-transaction.index') }}">
                            <div class="row g-2 align-items-end">

                                <div class="col-md-2 col-lg-1">
                                    <label for="financial_year_id" class="form-label">Financial Year</label>
                                    <select name="financial_year_id" id="financial_year_id" class="form-select dg-select">
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

                                <div class="col-md-2 col-lg-1">
                                    <label for="start_date" class="form-label">Date From</label>
                                    <input type="date" name="start_date" id="start_date" class="form-control dg-input" value="{{ request('start_date', !empty($startDate) ? \Illuminate\Support\Carbon::parse($startDate)->format('Y-m-d') : '') }}">
                                </div>

                                <div class="col-md-2 col-lg-1">
                                    <label for="end_date" class="form-label">Date To</label>
                                    <input type="date" name="end_date" id="end_date" class="form-control dg-input" value="{{ request('end_date', !empty($endDate) ? \Illuminate\Support\Carbon::parse($endDate)->format('Y-m-d') : '') }}">
                                </div>

                                <div class="col-md-3 col-lg-2">
                                    <label for="account_id" class="form-label">Account</label>
                                    <select name="account_id" id="account_id" class="form-select dg-select">
                                        <option value="">All Accounts</option>
                                        @foreach ($accounts as $account)
                                            <option value="{{ $account->id }}" @selected(request('account_id') == $account->id)>{{ $account->account_name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-2 col-lg-1">
                                    <label for="search" class="form-label">Voucher No</label>
                                    <input type="text" name="search" id="search" class="form-control dg-input" value="{{ request('search') }}" placeholder="Voucher No">
                                </div>

                                <div class="col-md-2 col-lg-1">
                                    <label for="status" class="form-label">Status</label>
                                    <select name="status" id="status" class="form-select dg-select">
                                        <option value="" @selected(request()->has('status') && request('status') === '')>All</option>
                                        <option value="1" @selected(!request()->has('status') || request('status') === '1')>Active</option>
                                        <option value="0" @selected(request('status') === '0')>Cancelled</option>
                                    </select>
                                </div>

                                @if (request('per_page'))
                                    <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                                @endif

                                <div class="col-md-2 col-lg-2 d-flex gap-2">
                                    <button type="submit" class="btn btn-primary dg-btn">Search</button>
                                    <a href="{{ route('company.account-transaction.index') }}" class="btn btn-outline-secondary dg-btn">Reset</a>
                                </div>

                            </div>
                        </form>
                    </div>
                </article>
            </section>

            <section class="dg-section">
                <div class="dg-summary d-flex flex-row flex-wrap justify-content-center align-items-center gap-3 mb-0 w-100">

                    <div class="dg-summary-item mb-0 border-0 p-0">
                        <span>Total Transactions :</span>
                        <span class="fw-bold">{{ number_format($summary['total_transactions']) }}</span>
                    </div>

                    <span>|</span>

                    <div class="dg-summary-item mb-0 border-0 p-0">
                        <span>Total Debit :</span>
                        <span class="fw-bold">{{ number_format($summary['total_debit'], 2) }}</span>
                    </div>

                    <span>|</span>

                    <div class="dg-summary-item mb-0 border-0 p-0">
                        <span>Total Credit :</span>
                        <span class="fw-bold">{{ number_format($summary['total_credit'], 2) }}</span>
                    </div>

                </div>
            </section>

            <section class="dg-section" id="dgAccountTransactionList">
                <article class="card dg-card dg-print">
                    <header class="card-header dg-card-header">
                        <h2 class="h6 mb-0">Account Transaction List</h2>
                    </header>

                    <div class="card-body dg-card-body">
                        <form method="GET" action="{{ route('company.account-transaction.index') }}" class="d-flex justify-content-end align-items-center gap-2 mb-2 @if (request('print')) d-print-none @endif">
                            <input type="hidden" name="financial_year_id" value="{{ request('financial_year_id', $activeFy?->id) }}">
                            <input type="hidden" name="start_date" value="{{ request('start_date', !empty($startDate) ? \Illuminate\Support\Carbon::parse($startDate)->format('Y-m-d') : '') }}">
                            <input type="hidden" name="end_date" value="{{ request('end_date', !empty($endDate) ? \Illuminate\Support\Carbon::parse($endDate)->format('Y-m-d') : '') }}">
                            <input type="hidden" name="account_id" value="{{ request('account_id') }}">
                            <input type="hidden" name="search" value="{{ request('search') }}">
                            <input type="hidden" name="status" value="{{ request()->has('status') ? request('status') : '1' }}">

                            <label for="per_page" class="mb-0 fw-bold">Show</label>
                            <select name="per_page" id="per_page" class="form-select form-select-sm dg-select w-auto" onchange="this.form.submit()">
                                <option value="10" @selected($perPage == 10)>10</option>
                                <option value="50" @selected($perPage == 50)>50</option>
                                <option value="100" @selected($perPage == 100)>100</option>
                            </select>
                        </form>

                        <div class="table-responsive">
                            <table class="table dg-table">
                                <thead class="dg-head">
                                    <tr>
                                        <th scope="col">SN</th>
                                        <th scope="col">Date</th>
                                        <th scope="col">Voucher No</th>
                                        <th scope="col">Account</th>
                                        <th scope="col">Description</th>
                                        <th scope="col" class="text-end">Debit</th>
                                        <th scope="col" class="text-end">Credit</th>
                                        <th scope="col" class="text-end">Balance</th>
                                        <th scope="col" width="170" class="text-center d-print-none">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="dg-body">
                                    @forelse ($transactions as $transaction)
                                        <tr class="dg-row">
                                            <td>{{ $transactions->firstItem() + $loop->index }}</td>
                                            <td>{{ $transaction->transaction_date }}</td>
                                            <td>{{ $transaction->voucher_no }}</td>
                                            <td>{{ $transaction->account->account_name ?? '' }}</td>
                                            <td>{{ $transaction->description }}</td>
                                            <td class="text-end">{{ number_format($transaction->debit, 2) }}</td>
                                            <td class="text-end">{{ number_format($transaction->credit, 2) }}</td>
                                            <td class="text-end">{{ number_format($transaction->balance, 2) }}</td>
                                            <td class="text-center d-print-none">
                                                <div class="btn-group" role="group" aria-label="Transaction actions for {{ $transaction->voucher_no }}">
                                                    <a href="{{ route('company.account-transaction.show', $transaction->id) }}" class="btn btn-sm btn-outline-success dg-btn">Edit</a>
                                                    <a href="{{ route('company.account-transaction.show', $transaction->id) }}" class="btn btn-sm btn-outline-info dg-btn">View</a>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr class="dg-row">
                                            <td colspan="9" class="text-center">No Data Found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2 @if (request('print')) d-print-none @endif">
                            <p class="mb-0 text-muted">
                                Showing {{ $transactions->firstItem() ?? 0 }} to {{ $transactions->lastItem() ?? 0 }} of {{ $transactions->total() }} records
                            </p>

                            <nav aria-label="Account transaction list pagination">
                                {{ $transactions->links() }}
                            </nav>
                        </div>
                    </div>
                </article>
            </section>

        </div>
    </main>

</div>

@if (request('print'))
    @push('scripts')
        <script>
            window.addEventListener('load', function () {
                window.print();
            });
        </script>
    @endpush
@endif

@endsection
